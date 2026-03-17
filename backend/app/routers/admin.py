from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Order, OrderEvent
from ..schemas import OrderOut

router = APIRouter(prefix="/admin", tags=["admin"], dependencies=[Depends(require_role("ADMIN"))])


@router.get("/users")
def users(db: Session = Depends(get_db)):
    rows = db.query(User).order_by(User.id.desc()).limit(500).all()
    return [{"id": u.id, "email": u.email, "role": u.role, "created_at": u.created_at} for u in rows]


@router.get("/vendors")
def vendors(db: Session = Depends(get_db)):
    rows = db.query(Vendor).order_by(Vendor.id.desc()).limit(500).all()
    return [
        {
            "id": v.id,
            "seller_id": v.seller_id,
            "name": v.name,
            "delivery_radius_km": v.delivery_radius_km,
            "is_open": v.is_open,
        }
        for v in rows
    ]


@router.get("/orders", response_model=list[OrderOut])
def orders(db: Session = Depends(get_db)):
    return db.query(Order).order_by(Order.id.desc()).limit(500).all()


@router.post("/orders/{order_id}/status", response_model=OrderOut)
def override_status(order_id: int, status: str, note: str = "", db: Session = Depends(get_db), admin: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note or "Admin override", actor_user_id=admin.id))
    db.commit()
    db.refresh(order)
    return order
