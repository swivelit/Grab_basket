from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import func
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import FcmToken, Order, OrderEvent, PartnerLocation, User
from ..notifications import send_push
from ..schemas import OrderOut, PartnerLocationIn

router = APIRouter(prefix="/partner", tags=["partner"])

ACTIVE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}
MAX_LOCATION_POINTS_PER_PARTNER = 500
DEFAULT_ORDER_LIMIT = 100


def _user_tokens(db: Session, user_id: int) -> list[str]:
    return [row.token for row in db.query(FcmToken).filter(FcmToken.user_id == user_id).all()]


def _partner_order_query(db: Session, partner_id: int):
    return db.query(Order).filter(Order.partner_id == partner_id)


def _active_partner_order_query(db: Session, partner_id: int):
    return _partner_order_query(db, partner_id).filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))


def _has_active_order(db: Session, partner_id: int) -> bool:
    return _active_partner_order_query(db, partner_id).first() is not None


def _latest_location(db: Session, partner_id: int) -> PartnerLocation | None:
    return (
        db.query(PartnerLocation)
        .filter(PartnerLocation.partner_id == partner_id)
        .order_by(PartnerLocation.id.desc())
        .first()
    )


def _serialize_location(row: PartnerLocation | None) -> dict | None:
    if not row:
        return None

    return {
        "lat": row.lat,
        "lng": row.lng,
        "heading": row.heading,
        "speed": row.speed,
        "created_at": row.created_at,
    }


def _add_order_event(db: Session, order: Order, status: str, note: str, actor_user_id: int | None) -> None:
    order.status = status
    db.add(
        OrderEvent(
            order_id=order.id,
            status=status,
            note=note,
            actor_user_id=actor_user_id,
        )
    )


def _maybe_set_available(db: Session, partner: User) -> None:
    partner.is_partner_available = not _has_active_order(db, partner.id)


def _order_summary(db: Session, partner_id: int) -> dict:
    base = _partner_order_query(db, partner_id)

    total_orders = base.count()
    active_order_count = base.filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES)).count()
    delivered_order_count = base.filter(Order.status == "DELIVERED").count()
    assigned_order_count = base.filter(
        Order.status.in_({"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"})
    ).count()
    picked_up_count = base.filter(Order.status == "PICKED_UP").count()
    cancelled_or_rejected_count = base.filter(
        (Order.status.like("CANCELLED%")) | (Order.status.like("REJECTED%"))
    ).count()

    cod_cash_collected = (
        base.filter(Order.status == "DELIVERED")
        .filter(func.upper(Order.payment_method) == "COD")
        .with_entities(func.coalesce(func.sum(Order.total_amount), 0.0))
        .scalar()
        or 0.0
    )

    latest_active = (
        base.filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))
        .order_by(Order.id.desc())
        .first()
    )

    return {
        "total_orders": total_orders,
        "active_order_count": active_order_count,
        "assigned_order_count": assigned_order_count,
        "picked_up_count": picked_up_count,
        "delivered_order_count": delivered_order_count,
        "cancelled_or_rejected_count": cancelled_or_rejected_count,
        "cod_cash_collected": round(float(cod_cash_collected), 2),
        "current_active_order_id": latest_active.id if latest_active else None,
    }


@router.get("/status", dependencies=[Depends(require_role("PARTNER"))])
def partner_status(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    latest_loc = _latest_location(db, user.id)

    return {
        "partner": {
            "id": user.id,
            "email": user.email,
            "role": user.role,
            "is_available": bool(user.is_partner_available),
            "created_at": user.created_at,
        },
        "summary": _order_summary(db, user.id),
        "latest_location": _serialize_location(latest_loc),
        "availability_locked": _has_active_order(db, user.id),
    }


@router.get("/location/latest", dependencies=[Depends(require_role("PARTNER"))])
def partner_latest_location(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    latest_loc = _latest_location(db, user.id)

    return {
        "ok": True,
        "has_location": latest_loc is not None,
        "location": _serialize_location(latest_loc),
    }


@router.post("/availability", dependencies=[Depends(require_role("PARTNER"))])
def set_availability(
    is_available: bool,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    if is_available and _has_active_order(db, user.id):
        user.is_partner_available = False
        db.commit()
        return {
          "ok": True,
          "is_available": False,
          "reason": "You already have an active order and cannot go online again right now.",
        }

    user.is_partner_available = bool(is_available)
    db.commit()
    db.refresh(user)

    return {
        "ok": True,
        "is_available": bool(user.is_partner_available),
    }


@router.post("/location", dependencies=[Depends(require_role("PARTNER"))])
def update_location(
    payload: PartnerLocationIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    row = PartnerLocation(partner_id=user.id, **payload.model_dump())
    db.add(row)
    db.flush()

    old_ids = (
        db.query(PartnerLocation.id)
        .filter(PartnerLocation.partner_id == user.id)
        .order_by(PartnerLocation.id.desc())
        .offset(MAX_LOCATION_POINTS_PER_PARTNER)
        .all()
    )
    if old_ids:
        db.query(PartnerLocation).filter(
            PartnerLocation.id.in_([item[0] for item in old_ids])
        ).delete(synchronize_session=False)

    db.commit()
    db.refresh(row)

    return {
        "ok": True,
        "location": _serialize_location(row),
    }


@router.get("/orders", response_model=list[OrderOut], dependencies=[Depends(require_role("PARTNER"))])
def my_assigned_orders(
    status: str | None = None,
    active_only: bool = False,
    limit: int = Query(default=DEFAULT_ORDER_LIMIT, ge=1, le=500),
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    query = _partner_order_query(db, user.id)

    if active_only:
        query = query.filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))

    if status:
        statuses = [value.strip().upper() for value in status.split(",") if value.strip()]
        if statuses:
            query = query.filter(func.upper(Order.status).in_(statuses))

    return query.order_by(Order.id.desc()).limit(limit).all()


@router.post("/orders/{order_id}/pickup", response_model=OrderOut, dependencies=[Depends(require_role("PARTNER"))])
def pickup(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status not in {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"}:
        raise HTTPException(status_code=400, detail="This order cannot be picked up right now")

    user.is_partner_available = False
    _add_order_event(db, order, "PICKED_UP", "Picked up by delivery partner", actor_user_id=user.id)

    db.commit()
    db.refresh(order)

    send_push(
        _user_tokens(db, order.customer_id),
        "Order picked up",
        f"Order #{order.id} is on the way",
        data={"order_id": str(order.id)},
    )

    return order


@router.post("/orders/{order_id}/deliver", response_model=OrderOut, dependencies=[Depends(require_role("PARTNER"))])
def deliver(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != "PICKED_UP":
        raise HTTPException(status_code=400, detail="This order cannot be delivered right now")

    _add_order_event(db, order, "DELIVERED", "Delivered by partner", actor_user_id=user.id)

    if str(order.payment_method or "").upper() == "COD":
        order.payment_status = "PAID"

    db.flush()
    _maybe_set_available(db, user)

    db.commit()
    db.refresh(order)

    send_push(
        _user_tokens(db, order.customer_id),
        "Delivered",
        f"Order #{order.id} has been delivered",
        data={"order_id": str(order.id)},
    )

    return order