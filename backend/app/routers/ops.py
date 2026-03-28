from __future__ import annotations

from fastapi import APIRouter
from sqlalchemy import text
from sqlalchemy.orm import Session

from ..config import settings
from ..db import engine
from ..models import Order, PartnerLocation

router = APIRouter(tags=["ops"])


def run_synthetic_probes() -> dict:
    probes: dict[str, dict] = {}

    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        probes["database_roundtrip"] = {"ok": True}
    except Exception as exc:
        probes["database_roundtrip"] = {"ok": False, "error": str(exc)}

    try:
        probes["payments_config"] = {
            "ok": bool(settings.razorpay_enabled and settings.payment_webhook_ready),
            "details": {
                "razorpay_enabled": settings.razorpay_enabled,
                "webhook_ready": settings.payment_webhook_ready,
            },
        }
    except Exception as exc:
        probes["payments_config"] = {"ok": False, "error": str(exc)}

    try:
        with Session(engine) as session:
            active_order = (
                session.query(Order.id)
                .filter(Order.status.in_(["ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"]))
                .first()
            )
            latest_partner_ping = session.query(PartnerLocation.id).order_by(PartnerLocation.created_at.desc()).first()

        probes["tracking_pipeline"] = {
            "ok": True,
            "details": {
                "has_active_partner_order": bool(active_order),
                "has_partner_location_ping": bool(latest_partner_ping),
            },
        }
    except Exception as exc:
        probes["tracking_pipeline"] = {"ok": False, "error": str(exc)}

    overall_ok = all(item.get("ok") for item in probes.values())
    return {"ok": overall_ok, "probes": probes}


@router.get("/health/synthetic")
def health_synthetic():
    result = run_synthetic_probes()
    readiness = settings.release_readiness_report
    return {
        "ok": result["ok"] and not readiness["errors"],
        "synthetic": result,
        "release_readiness": readiness,
    }
