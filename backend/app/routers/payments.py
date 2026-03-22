from __future__ import annotations

import hashlib
import hmac
import json
from datetime import datetime, timedelta, timezone
from typing import Any
from urllib.parse import parse_qsl, quote, urlencode, urlparse, urlunparse

import requests
from fastapi import APIRouter, Depends, HTTPException, Query, Request, status
from fastapi.responses import HTMLResponse, RedirectResponse
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..config import settings
from ..db import get_db
from ..models import FcmToken, Order, OrderEvent, User, Vendor
from ..notifications import send_push
from ..schemas import (
    PaymentCheckoutSessionIn,
    PaymentCheckoutSessionOut,
    PaymentStatusOut,
    PaymentVerifyIn,
    PaymentVerifyOut,
)

router = APIRouter(prefix="/payments", tags=["payments"])

ONLINE_PAYMENT_METHODS = {"UPI", "CARD"}
RAZORPAY_API_BASE = "https://api.razorpay.com/v1"
LEGACY_PAYMENT_VERIFICATION_ALLOWED = not settings.razorpay_enabled and not settings.is_prod


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


def _require_razorpay_enabled() -> None:
    if not settings.razorpay_enabled:
        raise HTTPException(
            status_code=503,
            detail="Razorpay is not configured on the backend. Set RAZORPAY_KEY_ID and RAZORPAY_KEY_SECRET.",
        )


def _gateway_auth() -> tuple[str, str]:
    _require_razorpay_enabled()
    return settings.RAZORPAY_KEY_ID or "", settings.RAZORPAY_KEY_SECRET or ""


def _razorpay_request(method: str, path: str, *, payload: dict[str, Any] | None = None) -> dict[str, Any]:
    try:
        response = requests.request(
            method,
            f"{RAZORPAY_API_BASE}{path}",
            auth=_gateway_auth(),
            json=payload,
            timeout=20,
        )
    except requests.RequestException as exc:
        raise HTTPException(status_code=502, detail=f"Gateway request failed: {exc}") from exc

    try:
        data = response.json()
    except ValueError:
        data = None

    if response.status_code >= 400:
        detail = None
        if isinstance(data, dict):
            detail = data.get("error", {}).get("description") or data.get("error", {}).get("reason")
        raise HTTPException(status_code=502, detail=detail or f"Gateway returned HTTP {response.status_code}")

    if not isinstance(data, dict):
        raise HTTPException(status_code=502, detail="Gateway returned an invalid response")

    return data


def _to_paise(amount: float | int | None) -> int:
    return int(round(float(amount or 0) * 100))


def _now_utc() -> datetime:
    return datetime.now(timezone.utc)


def _build_payment_link_reference(order: Order) -> str:
    return f"GB-ORD-{order.id}-{int(_now_utc().timestamp())}"[:40]


def _find_ref_segment(order: Order | None, prefix: str) -> str | None:
    raw = str(getattr(order, "payment_ref", "") or "")
    for part in raw.split("|"):
        cleaned = part.strip()
        if cleaned.startswith(prefix):
            return cleaned[len(prefix):]
    return None


def _get_payment_link_id(order: Order | None) -> str | None:
    return _find_ref_segment(order, "RZP_LINK:")


def _get_provider_payment_id(order: Order | None) -> str | None:
    return _find_ref_segment(order, "RZP_PAY:")


def _set_payment_ref(order: Order, *, link_id: str | None = None, payment_id: str | None = None) -> None:
    current_link_id = link_id or _get_payment_link_id(order)
    current_payment_id = payment_id or _get_provider_payment_id(order)
    parts = []
    if current_link_id:
        parts.append(f"RZP_LINK:{current_link_id}")
    if current_payment_id:
        parts.append(f"RZP_PAY:{current_payment_id}")
    order.payment_ref = "|".join(parts) if parts else None


def _is_payment_link_ref(ref: str | None) -> bool:
    return str(ref or "").startswith("plink_")


def _build_redirect_url(base: str, extra_query: dict[str, str | int | None]) -> str:
    parsed = urlparse(base)
    if not parsed.scheme:
        raise HTTPException(status_code=400, detail="return_url must be an absolute URL or app deep link")
    if parsed.scheme.lower() in {"javascript", "data", "file"}:
        raise HTTPException(status_code=400, detail="Unsupported return_url scheme")

    existing = dict(parse_qsl(parsed.query, keep_blank_values=True))
    for key, value in extra_query.items():
        if value is not None:
            existing[key] = str(value)

    return urlunparse(parsed._replace(query=urlencode(existing, doseq=True)))


def _resolve_public_base_url(request: Request) -> str:
    configured = str(settings.PUBLIC_BASE_URL or "").strip().rstrip("/")
    if configured:
        return configured
    return str(request.base_url).rstrip("/")


def _build_callback_url(request: Request, order: Order, return_url: str | None = None) -> str:
    base = _resolve_public_base_url(request)
    url = f"{base}/payments/razorpay/callback?order_id={order.id}"
    if return_url:
        url += f"&return_url={quote(return_url, safe='')}"
    return url


def _payment_methods_config(method: str) -> dict[str, Any]:
    normalized = _normalize_payment_method(method)
    if normalized == "UPI":
        return {
            "checkout": {
                "method": {
                    "upi": True,
                    "card": False,
                    "netbanking": False,
                    "wallet": False,
                }
            }
        }

    return {
        "checkout": {
            "method": {
                "upi": False,
                "card": True,
                "netbanking": False,
                "wallet": False,
            }
        }
    }


def _serialize_gateway_status(link: dict[str, Any]) -> str:
    return str(link.get("status") or "created").strip().lower() or "created"


def _is_reusable_link_status(status_value: str) -> bool:
    return status_value in {"created"}


def _is_retryable_status(status_value: str) -> bool:
    return status_value in {"created", "cancelled", "expired"}


def _status_from_payment_link(order: Order, link: dict[str, Any]) -> dict[str, Any]:
    checkout_status = _serialize_gateway_status(link)
    payment_status = str(order.payment_status or "PENDING_VERIFICATION").upper()
    provider_payment_id = _get_provider_payment_id(order)

    payments = link.get("payments")
    first_payment = payments[0] if isinstance(payments, list) and payments else {}
    fetched_payment_id = str(first_payment.get("payment_id") or first_payment.get("id") or "").strip() or None
    if fetched_payment_id:
        provider_payment_id = fetched_payment_id

    if checkout_status == "paid":
        payment_status = "PAID"
    elif checkout_status in {"cancelled", "expired"} and payment_status != "PAID":
        payment_status = "FAILED"

    return {
        "payment_status": payment_status,
        "checkout_status": checkout_status,
        "provider_reference": str(link.get("id") or "") or None,
        "provider_payment_id": provider_payment_id,
        "expires_at": datetime.fromtimestamp(link.get("expire_by"), tz=timezone.utc) if link.get("expire_by") else None,
        "should_retry": _is_retryable_status(checkout_status) and payment_status != "PAID",
    }


def _add_event_once(db: Session, order: Order, status_value: str, note: str, actor_user_id: int | None = None) -> None:
    latest = order.events[-1] if order.events else None
    if latest and latest.status == status_value and latest.note == note:
        return
    db.add(OrderEvent(order_id=order.id, status=status_value, note=note, actor_user_id=actor_user_id))


def _mark_order_paid(
    db: Session,
    order: Order,
    *,
    link_id: str,
    payment_id: str | None,
    note: str,
    actor_user_id: int | None = None,
) -> None:
    already_paid = str(order.payment_status or "").upper() == "PAID"
    order.payment_status = "PAID"
    _set_payment_ref(order, link_id=link_id, payment_id=payment_id)
    if str(order.status or "").upper() == "PAYMENT_PENDING":
        order.status = "CREATED"
    if not already_paid:
        _add_event_once(db, order, "PAYMENT_VERIFIED", note, actor_user_id=actor_user_id)
        vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
        send_push(
            _seller_tokens(db, vendor),
            "New paid order",
            f"Order #{order.id} is ready for seller action",
            data={"order_id": str(order.id)},
        )


def _mark_order_payment_failed(db: Session, order: Order, *, gateway_status: str, actor_user_id: int | None = None) -> None:
    if str(order.payment_status or "").upper() == "PAID":
        return
    if str(order.payment_status or "").upper() != "FAILED":
        order.payment_status = "FAILED"
    _add_event_once(
        db,
        order,
        "PAYMENT_FAILED",
        f"Gateway reports payment link status: {gateway_status}",
        actor_user_id=actor_user_id,
    )


def _fetch_payment_link(payment_link_id: str) -> dict[str, Any]:
    return _razorpay_request("GET", f"/payment_links/{payment_link_id}")


def _sync_order_from_payment_link(db: Session, order: Order, *, payment_link_id: str) -> dict[str, Any]:
    link = _fetch_payment_link(payment_link_id)
    link_state = _status_from_payment_link(order, link)

    if link_state["checkout_status"] == "paid":
        payment_id = link_state["provider_payment_id"]
        _mark_order_paid(
            db,
            order,
            link_id=payment_link_id,
            payment_id=payment_id,
            note=f"Gateway verified captured payment via payment link {payment_link_id}" + (f" · payment {payment_id}" if payment_id else ""),
        )
    elif link_state["checkout_status"] in {"cancelled", "expired"}:
        _mark_order_payment_failed(db, order, gateway_status=link_state["checkout_status"])
    else:
        if str(order.payment_status or "").upper() == "FAILED":
            order.payment_status = "PENDING_VERIFICATION"

    db.commit()
    db.refresh(order)
    link_state["order"] = order
    return link_state


def _build_checkout_payload(order: Order, user: User, request: Request, return_url: str | None) -> dict[str, Any]:
    description = f"Grab Basket order #{order.id}"
    expire_by = int((_now_utc() + timedelta(minutes=settings.PAYMENT_LINK_EXPIRE_MINUTES)).timestamp())
    reference_id = _build_payment_link_reference(order)

    payload: dict[str, Any] = {
        "amount": _to_paise(order.total_amount),
        "currency": "INR",
        "accept_partial": False,
        "description": description,
        "reference_id": reference_id,
        "expire_by": expire_by,
        "notify": {"sms": False, "email": False},
        "reminder_enable": False,
        "callback_url": _build_callback_url(request, order, return_url),
        "callback_method": "get",
        "options": _payment_methods_config(order.payment_method),
        "notes": {
            "order_id": str(order.id),
            "customer_id": str(order.customer_id),
            "payment_method": _normalize_payment_method(order.payment_method),
        },
    }

    if user.email:
        payload["customer"] = {"email": user.email}

    return payload


def _build_checkout_response(order: Order, link_state: dict[str, Any], checkout_url: str, link_id: str) -> dict[str, Any]:
    return {
        "ok": True,
        "provider": "razorpay_payment_link",
        "checkout_url": checkout_url,
        "provider_reference": link_id,
        "payment_status": link_state["payment_status"],
        "checkout_status": link_state["checkout_status"],
        "expires_at": link_state.get("expires_at"),
        "order": order,
    }


def _lookup_customer_order(db: Session, order_id: int, user: User) -> Order:
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    return order


def _verify_payment_link_signature(*, payment_link_id: str, reference_id: str, link_status: str, payment_id: str, signature: str) -> bool:
    secret = settings.RAZORPAY_KEY_SECRET or ""
    payload = "|".join([payment_link_id, reference_id, link_status, payment_id])
    expected = hmac.new(secret.encode("utf-8"), payload.encode("utf-8"), hashlib.sha256).hexdigest()
    return hmac.compare_digest(expected, signature)


@router.post("/{order_id}/checkout-session", response_model=PaymentCheckoutSessionOut, dependencies=[Depends(require_role("CUSTOMER"))])
def create_checkout_session(
    order_id: int,
    payload: PaymentCheckoutSessionIn,
    request: Request,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    _require_razorpay_enabled()
    order = _lookup_customer_order(db, order_id, user)

    if not _is_online_payment(order):
        raise HTTPException(status_code=400, detail="Cash on delivery orders do not require a payment session")
    if str(order.status or "").startswith("CANCELLED"):
        raise HTTPException(status_code=400, detail="Cancelled orders cannot be paid")
    if str(order.payment_status or "").upper() == "PAID":
        raise HTTPException(status_code=400, detail="Order is already paid")

    existing_link_id = _get_payment_link_id(order)
    if existing_link_id:
        link = _fetch_payment_link(existing_link_id)
        state = _status_from_payment_link(order, link)
        if state["checkout_status"] == "paid":
            state = _sync_order_from_payment_link(db, order, payment_link_id=existing_link_id)
            return _build_checkout_response(order, state, str(link.get("short_url") or ""), existing_link_id)
        if _is_reusable_link_status(state["checkout_status"]):
            return _build_checkout_response(order, state, str(link.get("short_url") or ""), existing_link_id)

    gateway_payload = _build_checkout_payload(order, user, request, payload.return_url)
    link = _razorpay_request("POST", "/payment_links", payload=gateway_payload)
    link_id = str(link.get("id") or "").strip()
    short_url = str(link.get("short_url") or "").strip()
    if not link_id or not short_url:
        raise HTTPException(status_code=502, detail="Gateway did not return a usable checkout link")

    _set_payment_ref(order, link_id=link_id, payment_id=None)
    order.payment_status = "PENDING_VERIFICATION"
    _add_event_once(
        db,
        order,
        "PAYMENT_SESSION_CREATED",
        f"Hosted payment session created via Razorpay payment link {link_id}",
        actor_user_id=user.id,
    )
    db.commit()
    db.refresh(order)

    state = _status_from_payment_link(order, link)
    return _build_checkout_response(order, state, short_url, link_id)


@router.get("/{order_id}/status", response_model=PaymentStatusOut, dependencies=[Depends(require_role("CUSTOMER"))])
def get_payment_status(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = _lookup_customer_order(db, order_id, user)

    provider_reference = _get_payment_link_id(order)
    provider_payment_id = _get_provider_payment_id(order)

    if not _is_online_payment(order):
        return {
            "ok": True,
            "provider": "none",
            "payment_status": str(order.payment_status or "PENDING").upper(),
            "checkout_status": "not_required",
            "should_retry": False,
            "provider_reference": provider_reference,
            "provider_payment_id": provider_payment_id,
            "order": order,
        }

    if str(order.payment_status or "").upper() == "PAID":
        return {
            "ok": True,
            "provider": "razorpay_payment_link" if provider_reference else "legacy",
            "payment_status": "PAID",
            "checkout_status": "paid",
            "should_retry": False,
            "provider_reference": provider_reference,
            "provider_payment_id": provider_payment_id,
            "order": order,
        }

    if provider_reference and settings.razorpay_enabled:
        state = _sync_order_from_payment_link(db, order, payment_link_id=provider_reference)
        return {
            "ok": True,
            "provider": "razorpay_payment_link",
            "payment_status": state["payment_status"],
            "checkout_status": state["checkout_status"],
            "should_retry": state["should_retry"],
            "provider_reference": state["provider_reference"],
            "provider_payment_id": state["provider_payment_id"],
            "order": state["order"],
        }

    return {
        "ok": True,
        "provider": "razorpay_payment_link" if settings.razorpay_enabled else "legacy",
        "payment_status": str(order.payment_status or "PENDING_VERIFICATION").upper(),
        "checkout_status": "not_started",
        "should_retry": True,
        "provider_reference": provider_reference,
        "provider_payment_id": provider_payment_id,
        "order": order,
    }


@router.get("/razorpay/callback", response_class=HTMLResponse)
def razorpay_callback(
    request: Request,
    order_id: int | None = Query(default=None),
    return_url: str | None = Query(default=None),
    db: Session = Depends(get_db),
):
    payment_id = str(request.query_params.get("razorpay_payment_id") or "").strip()
    payment_link_id = str(request.query_params.get("razorpay_payment_link_id") or "").strip()
    reference_id = str(request.query_params.get("razorpay_payment_link_reference_id") or "").strip()
    payment_link_status = str(request.query_params.get("razorpay_payment_link_status") or "").strip().lower()
    signature = str(request.query_params.get("razorpay_signature") or "").strip()

    order = None
    if order_id is not None:
        order = db.query(Order).filter(Order.id == order_id).first()
    if not order and payment_link_id:
        order = db.query(Order).filter(Order.payment_ref.like(f"RZP_LINK:{payment_link_id}%")).first()

    verified = False
    if settings.razorpay_enabled and payment_id and payment_link_id and reference_id and payment_link_status and signature:
        verified = _verify_payment_link_signature(
            payment_link_id=payment_link_id,
            reference_id=reference_id,
            link_status=payment_link_status,
            payment_id=payment_id,
            signature=signature,
        )

    if order and payment_link_id and settings.razorpay_enabled:
        try:
            _sync_order_from_payment_link(db, order, payment_link_id=payment_link_id)
        except HTTPException:
            pass

    redirect_target = None
    if return_url:
        try:
            redirect_target = _build_redirect_url(
                return_url,
                {
                    "payment_status": "paid" if verified else payment_link_status or "unknown",
                    "order_id": order.id if order else order_id,
                    "gateway": "razorpay",
                },
            )
        except HTTPException:
            redirect_target = None

    if redirect_target:
        return RedirectResponse(url=redirect_target, status_code=status.HTTP_302_FOUND)

    status_text = "Payment confirmed" if verified else "Payment received"
    body = f"""
    <html>
      <head>
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <title>Grab Basket payment status</title>
        <style>
          body {{ font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #fff9f3; color: #2f241c; padding: 24px; }}
          .card {{ max-width: 520px; margin: 60px auto; background: white; border: 1px solid #f2ddc7; border-radius: 20px; padding: 24px; }}
          h1 {{ margin-top: 0; font-size: 24px; }}
          p {{ line-height: 1.5; color: #6f5f52; }}
        </style>
      </head>
      <body>
        <div class="card">
          <h1>{status_text}</h1>
          <p>Order #{order.id if order else order_id or '—'} has been sent back to Grab Basket. You can now return to the app.</p>
          <p>Gateway status: {payment_link_status or 'unknown'}</p>
        </div>
      </body>
    </html>
    """
    return HTMLResponse(content=body)


@router.post("/webhooks/razorpay")
async def razorpay_webhook(request: Request, db: Session = Depends(get_db)):
    if not settings.RAZORPAY_WEBHOOK_SECRET:
        raise HTTPException(status_code=503, detail="RAZORPAY_WEBHOOK_SECRET is not configured")

    signature = str(request.headers.get("x-razorpay-signature") or "").strip()
    if not signature:
        raise HTTPException(status_code=400, detail="Missing Razorpay webhook signature")

    raw_body = await request.body()
    expected_signature = hmac.new(
        settings.RAZORPAY_WEBHOOK_SECRET.encode("utf-8"),
        raw_body,
        hashlib.sha256,
    ).hexdigest()

    if not hmac.compare_digest(expected_signature, signature):
        raise HTTPException(status_code=403, detail="Invalid Razorpay webhook signature")

    try:
        payload = json.loads(raw_body.decode("utf-8") or "{}")
    except json.JSONDecodeError as exc:
        raise HTTPException(status_code=400, detail="Invalid webhook payload") from exc

    event_name = str(payload.get("event") or "").strip()
    payment_link_entity = (((payload.get("payload") or {}).get("payment_link") or {}).get("entity") or {})
    payment_entity = (((payload.get("payload") or {}).get("payment") or {}).get("entity") or {})

    payment_link_id = str(payment_link_entity.get("id") or "").strip()
    payment_id = str(payment_entity.get("id") or "").strip() or None

    if event_name == "payment_link.paid" and payment_link_id:
        order = db.query(Order).filter(Order.payment_ref.like(f"RZP_LINK:{payment_link_id}%")).first()
        if order:
            _mark_order_paid(
                db,
                order,
                link_id=payment_link_id,
                payment_id=payment_id,
                note=f"Razorpay webhook confirmed payment link {payment_link_id}" + (f" · payment {payment_id}" if payment_id else ""),
            )
            db.commit()
            db.refresh(order)

    if event_name in {"payment_link.expired", "payment_link.cancelled"} and payment_link_id:
        order = db.query(Order).filter(Order.payment_ref.like(f"RZP_LINK:{payment_link_id}%")).first()
        if order:
            _mark_order_payment_failed(db, order, gateway_status=event_name.split(".")[-1])
            db.commit()
            db.refresh(order)

    return {"ok": True}


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

    if not LEGACY_PAYMENT_VERIFICATION_ALLOWED:
        raise HTTPException(
            status_code=400,
            detail="Legacy manual payment verification is disabled. Create a hosted checkout session and verify payment through the gateway callback/webhook flow.",
        )

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