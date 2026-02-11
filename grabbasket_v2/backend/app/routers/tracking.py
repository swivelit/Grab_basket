from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user
from ..db import get_db
from ..models import Order, PartnerLocation

router = APIRouter(prefix="/tracking", tags=["tracking"])


@router.get("/order/{order_id}/partner_latest")
def partner_latest(order_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    # Customer can only track own orders
    if getattr(user, "role", None) != "CUSTOMER":
        raise HTTPException(status_code=403, detail="Only customers can track orders")

    o = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not o:
        raise HTTPException(404, "Order not found")
    if not o.partner_id:
        return {"assigned": False}

    row = (
        db.query(PartnerLocation)
        .filter(PartnerLocation.partner_id == o.partner_id)
        .order_by(PartnerLocation.id.desc())
        .first()
    )
    if not row:
        return {"assigned": True, "has_location": False}

    return {
        "assigned": True,
        "has_location": True,
        "lat": row.lat,
        "lng": row.lng,
        "heading": row.heading,
        "speed": row.speed,
        "ts": row.created_at.isoformat(),
    }
