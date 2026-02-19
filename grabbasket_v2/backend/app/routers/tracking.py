from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user
from ..db import get_db
from ..models import Order, PartnerLocation, Vendor

router = APIRouter(prefix="/tracking", tags=["tracking"])


def _can_view_order(user, order: Order, db: Session) -> bool:
    role = getattr(user, "role", None)
    uid = getattr(user, "id", None)

    if role == "ADMIN":
        return True
    if role == "CUSTOMER" and order.customer_id == uid:
        return True
    if role == "PARTNER" and order.partner_id == uid:
        return True
    if role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == uid).first()
        return bool(v and v.id == order.vendor_id)
    return False


@router.get("/order/{order_id}/partner_latest")
def partner_latest(order_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    """Return the latest delivery-partner location for an order.

    Response is intentionally flat to keep mobile parsing simple:
    - If no partner assigned: {"assigned": False, "order_status": "..."}
    - If assigned but no location: {"assigned": True, "has_location": False, "order_status": "..."}
    - If location available: {"assigned": True, "has_location": True, "lat": ..., "lng": ..., "ts": "...", ...}
    """
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if not _can_view_order(user, order, db):
        raise HTTPException(status_code=403, detail="Forbidden")

    if not order.partner_id:
        return {"assigned": False, "order_status": order.status}

    row = (
        db.query(PartnerLocation)
        .filter(PartnerLocation.partner_id == order.partner_id)
        .order_by(PartnerLocation.id.desc())
        .first()
    )
    if not row:
        return {"assigned": True, "has_location": False, "order_status": order.status, "partner_id": order.partner_id}

    return {
        "assigned": True,
        "has_location": True,
        "order_status": order.status,
        "partner_id": order.partner_id,
        "lat": row.lat,
        "lng": row.lng,
        "heading": row.heading,
        "speed": row.speed,
        "ts": row.created_at.isoformat(),
    }
