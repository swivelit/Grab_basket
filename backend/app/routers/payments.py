from __future__ import annotations

import hmac
import hashlib
from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..config import settings
from ..db import get_db
from ..models import Order, OrderEvent, User, Vendor, FcmToken
from ..schemas import PaymentVerifyIn, PaymentVerifyOut
from ..notifications import send_push

router = APIRouter(prefix="/payments", tags=["payments"])

ONLINE_PAYMENT_METHODS = {"UPI", "CARD"}


def _seller_tokens(db: Session, vendor: Vendor | None) -> list[str]:
    if not vendor or not vendor.seller_id:
        return []
    return [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == vendor.seller_id).all()]


def _normalize_payment_method(value: str = "") -> str:
    return str(value or "").strip().upper()


def _is_online_payment(order: Order) -> bool:
    return _normalize_payment_method(order.payment_method) in ONLINE_PAYMENT_METHODS


def _mask_upi(value: str) -> str:
    raw = str(value or "").strip().lower()
    if "@" not in raw:
        return raw
    name, handle = raw.split("@", 1)
    visible = name[:2]
    masked = "*" * max(0, len(name) - len(visible))
    return f"{visible}{masked}@{handle}"


def _build_verification_token(order: Order, reference: str) -> str:
    payload = "|".join([
        str(order.id),
        _normalize_payment_method(order.payment_method),
        f"{float(order.total_amount or 0):.2f}",
        str(reference or "").strip().upper(),
    ])
    return hmac.new(
        settings.JWT_SECRET.encode("utf-8"),
        payload.encode("utf-8"),
        hashlib.sha256,
    ).hexdigest()


@router.post("/{order_id}/verify", response_model=PaymentVerifyOut, dependencies=[Depends(require_role("CUSTOMER"))])
def verify_payment(order_id: int, payload: PaymentVerifyIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status.startswith("CANCELLED"):
        raise HTTPException(status_code=400, detail="Cancelled orders cannot be paid")

    if order.status == "DELIVERED" and _normalize_payment_method(order.payment_method) != "COD":
        raise HTTPException(status_code=400, detail="This order has already been completed")

    order_payment_method = _normalize_payment_method(order.payment_method)
    payload_payment_method = _normalize_payment_method(payload.payment_method)

    if not _is_online_payment(order):
        raise HTTPException(status_code=400, detail="This order does not require online payment verification")

    if order_payment_method != payload_payment_method:
        raise HTTPException(status_code=400, detail="Payment method does not match this order")

    if str(order.payment_status or "").upper() == "PAID":
        token = _build_verification_token(order, order.payment_ref or payload.reference)
        return {
            "ok": True,
            "payment_status": order.payment_status,
            "payment_ref": order.payment_ref,
            "verification_token": token,
            "order": order,
        }

    if payload.amount is not None and abs(float(payload.amount) - float(order.total_amount or 0)) > 0.01:
        raise HTTPException(status_code=400, detail="Payment amount mismatch")

    reference = str(payload.reference or "").strip().upper()
    if not reference:
        raise HTTPException(status_code=400, detail="Payment reference is required")

    verification_note_parts = []
    if order_payment_method == "UPI":
        if not payload.upi_id or "@" not in payload.upi_id:
            raise HTTPException(status_code=400, detail="A valid UPI ID is required")
        verification_note_parts.append(f"UPI {_mask_upi(payload.upi_id)}")
    elif order_payment_method == "CARD":
        if not payload.card_holder_name:
            raise HTTPException(status_code=400, detail="Card holder name is required")
        if not payload.card_last4 or not str(payload.card_last4).isdigit() or len(str(payload.card_last4)) != 4:
            raise HTTPException(status_code=400, detail="Card last 4 digits are required")
        verification_note_parts.append(
            f"CARD **** {payload.card_last4} ({payload.card_holder_name.strip()})"
        )

    verification_token = _build_verification_token(order, reference)

    order.payment_status = "PAID"
    order.payment_ref = reference
    if str(order.status or "").upper() == "PAYMENT_PENDING":
        order.status = "CREATED"
    db.add(
        OrderEvent(
            order_id=order.id,
            status="PAYMENT_VERIFIED",
            note=f"Payment verified on server · {' · '.join(verification_note_parts)} · token {verification_token[:12]} · {datetime.utcnow().isoformat()}Z",
            actor_user_id=user.id,
        )
    )

    db.commit()
    db.refresh(order)

    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    send_push(_seller_tokens(db, vendor), "New paid order", f"Order #{order.id} is ready for seller action", data={"order_id": str(order.id)})

    return {
        "ok": True,
        "payment_status": order.payment_status,
        "payment_ref": order.payment_ref,
        "verification_token": verification_token,
        "order": order,
    }