from __future__ import annotations

import json
import logging
import time
from datetime import datetime, timedelta

from sqlalchemy.orm import Session

from .db import SessionLocal
from .models import AsyncJob, MoneyLedgerEntry, OrderEvent, PaymentReconciliationReport, PayoutRecord, RefundCase

logger = logging.getLogger("grabbasket.worker")


class RetryableJobError(RuntimeError):
    pass


def _load_payload(job: AsyncJob) -> dict:
    try:
        return json.loads(job.payload_json or "{}")
    except json.JSONDecodeError:
        return {}


def _run_handler(db: Session, job: AsyncJob) -> None:
    payload = _load_payload(job)
    jt = job.job_type

    if jt == "partner_reassignment":
        db.add(OrderEvent(order_id=int(payload.get("order_id", 0)), status="REASSIGNMENT_QUEUED", note="Partner reassignment requested"))
        return
    if jt == "push_notification":
        return
    if jt == "refund_retry":
        rc = db.query(RefundCase).filter(RefundCase.id == int(payload.get("refund_case_id", 0))).first()
        if not rc:
            raise RetryableJobError("refund case not found")
        rc.attempts += 1
        if rc.attempts >= 2:
            rc.status = "COMPLETED"
        else:
            raise RetryableJobError("provider timeout")
        return
    if jt == "payment_reconciliation_import":
        report = PaymentReconciliationReport(
            provider=str(payload.get("provider") or "razorpay"),
            report_date=datetime.utcnow(),
            status="PROCESSED",
            file_uri=str(payload.get("file_uri") or ""),
            processed_at=datetime.utcnow(),
        )
        db.add(report)
        return
    if jt == "crm_nudge":
        return
    if jt == "stale_assignment_cleanup":
        db.add(OrderEvent(order_id=int(payload.get("order_id", 0)), status="STALE_ASSIGNMENT_CLEANUP", note="Cleanup job executed"))
        return
    raise RuntimeError(f"Unknown job_type={jt}")


def process_due_jobs(*, db: Session, queue_name: str | None = None, batch_size: int = 50) -> int:
    now = datetime.utcnow()
    query = db.query(AsyncJob).filter(AsyncJob.status.in_(["QUEUED", "RETRY"]), AsyncJob.run_after <= now)
    if queue_name:
        query = query.filter(AsyncJob.queue_name == queue_name)
    jobs = query.order_by(AsyncJob.id.asc()).limit(batch_size).all()

    processed = 0
    for job in jobs:
        job.status = "RUNNING"
        job.attempts += 1
        try:
            _run_handler(db, job)
            if job.job_type == "payment_reconciliation_import":
                db.add(
                    MoneyLedgerEntry(
                        event_type="RECONCILIATION_IMPORT",
                        flow_direction="CREDIT",
                        amount=0,
                        provider_ref=str(job.id),
                        idempotency_key=f"job:{job.id}",
                    )
                )
            if job.job_type == "refund_retry":
                db.add(
                    PayoutRecord(
                        beneficiary_user_id=1,
                        beneficiary_type="SELLER",
                        period_start=datetime.utcnow() - timedelta(days=1),
                        period_end=datetime.utcnow(),
                        gross_amount=0,
                        net_amount=0,
                        status="PENDING",
                    )
                )
            job.status = "DONE"
            job.last_error = ""
        except RetryableJobError as exc:
            if job.attempts >= job.max_attempts:
                job.status = "DEAD_LETTER"
                job.dead_letter_reason = str(exc)
            else:
                backoff_seconds = min(600, 2 ** job.attempts)
                job.status = "RETRY"
                job.run_after = datetime.utcnow() + timedelta(seconds=backoff_seconds)
                job.last_error = str(exc)
        except Exception as exc:  # noqa: BLE001
            if job.attempts >= job.max_attempts:
                job.status = "DEAD_LETTER"
                job.dead_letter_reason = str(exc)
            else:
                backoff_seconds = min(600, 2 ** job.attempts)
                job.status = "RETRY"
                job.run_after = datetime.utcnow() + timedelta(seconds=backoff_seconds)
                job.last_error = str(exc)
        processed += 1

    db.commit()
    return processed


def run_worker_loop(poll_seconds: float = 1.5) -> None:
    logger.info("Starting async job worker")
    while True:
        with SessionLocal() as db:
            processed = process_due_jobs(db=db)
            if processed == 0:
                time.sleep(poll_seconds)


if __name__ == "__main__":
    run_worker_loop()
