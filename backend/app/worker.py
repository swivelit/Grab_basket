from __future__ import annotations

import csv
import io
import json
import logging
import time
from datetime import timedelta

from sqlalchemy.orm import Session

from .db import SessionLocal
from .metrics import metrics
from .models import (
    AsyncJob,
    FcmToken,
    MoneyLedgerEntry,
    Order,
    OrderEvent,
    PaymentReconciliationReport,
    PayoutRecord,
    RefundCase,
)
from .notifications import build_order_notification_data, send_push
from .routers.seller import _maybe_mark_partner_available, _try_assign_partner
from .time import utc_now

logger = logging.getLogger("grabbasket.worker")


class RetryableJobError(RuntimeError):
    pass


def _load_payload(job: AsyncJob) -> dict:
    try:
        return json.loads(job.payload_json or "{}")
    except json.JSONDecodeError:
        return {}


def _record_event(db: Session, order_id: int, status: str, note: str, *, metadata: dict | None = None) -> None:
    db.add(
        OrderEvent(
            order_id=order_id,
            status=status,
            note=note,
            metadata_json=json.dumps(metadata or {}),
        )
    )


def _handle_partner_reassignment(db: Session, payload: dict) -> None:
    order_id = int(payload.get("order_id", 0) or 0)
    if order_id <= 0:
        raise RetryableJobError("missing order_id")

    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise RetryableJobError("order not found")

    if order.status not in {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"}:
        _record_event(db, order.id, "REASSIGNMENT_SKIPPED", f"Order status {order.status} is not eligible")
        return

    previous_partner_id = order.partner_id
    if previous_partner_id:
        order.partner_id = None
        order.assigned_at = None
        _maybe_mark_partner_available(db, previous_partner_id)

    replacement = _try_assign_partner(db, order, order.vendor)
    if not replacement:
        if previous_partner_id:
            order.partner_id = previous_partner_id
            order.assigned_at = utc_now()
        raise RetryableJobError("no replacement partner available")

    _record_event(
        db,
        order.id,
        "PARTNER_REASSIGNED",
        f"Partner reassigned to {replacement.id}",
        metadata={"previous_partner_id": previous_partner_id, "new_partner_id": replacement.id},
    )

    tokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == replacement.id).all()]
    if tokens:
        send_push(
            tokens,
            "New order assigned",
            f"Order #{order.id} is now assigned to you.",
            build_order_notification_data(order.id, status=order.status, target_app="delivery"),
        )


def _handle_push_notification(payload: dict) -> None:
    tokens = payload.get("tokens") or []
    title = str(payload.get("title") or "Grab Basket")
    body = str(payload.get("body") or "Notification")
    data = payload.get("data") or {}
    send_push(tokens, title, body, data)


def _handle_refund_retry(db: Session, payload: dict) -> None:
    rc = db.query(RefundCase).filter(RefundCase.id == int(payload.get("refund_case_id", 0))).first()
    if not rc:
        raise RetryableJobError("refund case not found")

    rc.attempts += 1
    if rc.status == "COMPLETED":
        return

    provider_state = str(payload.get("provider_state") or "timeout").lower()
    if provider_state in {"timeout", "temporary_failure"}:
        rc.status = "RETRYING"
        if rc.attempts >= 5:
            rc.status = "FAILED"
            raise RuntimeError("refund provider permanently failed")
        raise RetryableJobError("refund provider temporary failure")

    if provider_state in {"rejected", "failed"}:
        rc.status = "FAILED"
        return

    rc.status = "COMPLETED"


def _handle_refund_settlement(db: Session, payload: dict) -> None:
    order_id = int(payload.get("order_id", 0) or 0)
    if order_id <= 0:
        raise RetryableJobError("missing order_id")

    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise RetryableJobError("order not found")

    refund_case_id = int(payload.get("refund_case_id", 0) or 0)
    refund_case = None
    if refund_case_id > 0:
        refund_case = db.query(RefundCase).filter(RefundCase.id == refund_case_id).first()
        if not refund_case:
            raise RetryableJobError("refund case not found")
    else:
        refund_case = (
            db.query(RefundCase)
            .filter(RefundCase.order_id == order.id)
            .order_by(RefundCase.id.desc())
            .first()
        )

    refund_status = str(order.refund_status or "").upper()
    if refund_status == "COMPLETED":
        if refund_case and str(refund_case.status or "").upper() != "COMPLETED":
            refund_case.status = "COMPLETED"
            refund_case.next_retry_at = None
        return

    if refund_status not in {"REQUESTED", "PENDING", "PROCESSING", "INITIATED", "RETRYING"}:
        raise RuntimeError(f"order refund is not eligible for completion (status={refund_status or 'NONE'})")

    if not order.refund_ref:
        order.refund_ref = str(payload.get("refund_ref") or f"RFND-{order.id}-{int(utc_now().timestamp())}")
    order.refund_status = "COMPLETED"

    if refund_case:
        refund_case.status = "COMPLETED"
        refund_case.next_retry_at = None

    _record_event(
        db,
        order.id,
        "REFUND_COMPLETED",
        "Refund credited back to the original payment source",
        metadata={"refund_ref": order.refund_ref},
    )


def _handle_reconciliation_import(db: Session, payload: dict) -> None:
    raw_csv = str(payload.get("csv_data") or "").strip()
    file_uri = str(payload.get("file_uri") or "")
    provider = str(payload.get("provider") or "razorpay")

    rows = []
    if raw_csv:
        rows = list(csv.DictReader(io.StringIO(raw_csv)))

    mismatch_count = 0
    processed_count = 0
    for row in rows:
        processed_count += 1
        expected = str(row.get("expected_status") or "").upper()
        actual = str(row.get("actual_status") or "").upper()
        if expected and actual and expected != actual:
            mismatch_count += 1

    report = PaymentReconciliationReport(
        provider=provider,
        report_date=utc_now(),
        status="PROCESSED",
        file_uri=file_uri,
        summary_json=json.dumps(
            {
                "rows_processed": processed_count,
                "mismatch_count": mismatch_count,
            }
        ),
        processed_at=utc_now(),
    )
    db.add(report)


def _handle_crm_nudge(db: Session, payload: dict) -> None:
    order_id = int(payload.get("order_id") or 0)
    if order_id > 0:
        _record_event(db, order_id, "CRM_NUDGE_SENT", "CRM nudge queued and recorded", metadata=payload)


def _handle_stale_assignment_cleanup(db: Session, payload: dict) -> None:
    stale_before = utc_now() - timedelta(minutes=int(payload.get("stale_minutes") or 15))
    stale_orders = (
        db.query(Order)
        .filter(Order.status == "ASSIGNED_TO_PARTNER")
        .filter(Order.assigned_at.isnot(None))
        .filter(Order.assigned_at < stale_before)
        .all()
    )
    for order in stale_orders:
        old_partner_id = order.partner_id
        order.partner_id = None
        order.assigned_at = None
        _record_event(db, order.id, "STALE_ASSIGNMENT_CLEANUP", "Cleared stale partner assignment")
        if old_partner_id:
            _maybe_mark_partner_available(db, old_partner_id)


def _run_handler(db: Session, job: AsyncJob) -> None:
    payload = _load_payload(job)
    jt = job.job_type

    if jt == "partner_reassignment":
        _handle_partner_reassignment(db, payload)
        return
    if jt == "push_notification":
        _handle_push_notification(payload)
        return
    if jt == "refund_retry":
        _handle_refund_retry(db, payload)
        return
    if jt == "refund_settlement":
        _handle_refund_settlement(db, payload)
        return
    if jt == "payment_reconciliation_import":
        _handle_reconciliation_import(db, payload)
        return
    if jt == "crm_nudge":
        _handle_crm_nudge(db, payload)
        return
    if jt == "stale_assignment_cleanup":
        _handle_stale_assignment_cleanup(db, payload)
        return
    raise RuntimeError(f"Unknown job_type={jt}")


def process_due_jobs(*, db: Session, queue_name: str | None = None, batch_size: int = 50) -> int:
    now = utc_now()
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
                        period_start=utc_now() - timedelta(days=1),
                        period_end=utc_now(),
                        gross_amount=0,
                        net_amount=0,
                        status="PENDING",
                    )
                )
            job.status = "DONE"
            job.last_error = ""
            metrics.incr("jobs.processed_total")
        except RetryableJobError as exc:
            metrics.incr("jobs.retried_total")
            if job.attempts >= job.max_attempts:
                job.status = "DEAD_LETTER"
                job.dead_letter_reason = str(exc)
                metrics.incr("jobs.dead_letter_total")
            else:
                backoff_seconds = min(600, 2 ** job.attempts)
                job.status = "RETRY"
                job.run_after = utc_now() + timedelta(seconds=backoff_seconds)
                job.last_error = str(exc)
        except Exception as exc:  # noqa: BLE001
            metrics.incr("jobs.retried_total")
            if job.attempts >= job.max_attempts:
                job.status = "DEAD_LETTER"
                job.dead_letter_reason = str(exc)
                metrics.incr("jobs.dead_letter_total")
            else:
                backoff_seconds = min(600, 2 ** job.attempts)
                job.status = "RETRY"
                job.run_after = utc_now() + timedelta(seconds=backoff_seconds)
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