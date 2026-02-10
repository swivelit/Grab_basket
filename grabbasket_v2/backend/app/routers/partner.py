from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from ..database import get_db
from ..deps import require_role
from ..models import Order, OrderStatus, Role, PartnerProfile
from ..schemas import OrderOut

router = APIRouter(prefix="/partner", tags=["partner"])

@router.post("/availability")
def set_availability(is_available: bool, db: Session = Depends(get_db), user=Depends(require_role(Role.PARTNER))):
    profile = db.query(PartnerProfile).filter(PartnerProfile.user_id == user.id).first()
    if not profile:
        profile = PartnerProfile(user_id=user.id, is_available=is_available)
        db.add(profile)
    else:
        profile.is_available = is_available
    db.commit()
    return {"ok": True, "is_available": is_available}

@router.get("/orders", response_model=list[OrderOut])
def my_assigned_orders(db: Session = Depends(get_db), user=Depends(require_role(Role.PARTNER))):
    return (
        db.query(Order)
        .filter(Order.partner_id == user.id, Order.status.in_([OrderStatus.ASSIGNED_TO_PARTNER, OrderStatus.PICKED_UP]))
        .order_by(Order.id.desc())
        .all()
    )

@router.post("/orders/{order_id}/pickup", response_model=OrderOut)
def pickup(order_id: int, db: Session = Depends(get_db), user=Depends(require_role(Role.PARTNER))):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.status != OrderStatus.ASSIGNED_TO_PARTNER:
        raise HTTPException(status_code=400, detail="Order not in assignable state")
    order.status = OrderStatus.PICKED_UP
    db.commit()
    db.refresh(order)
    return order

@router.post("/orders/{order_id}/deliver", response_model=OrderOut)
def deliver(order_id: int, db: Session = Depends(get_db), user=Depends(require_role(Role.PARTNER))):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.status != OrderStatus.PICKED_UP:
        raise HTTPException(status_code=400, detail="Order not picked up")
    order.status = OrderStatus.DELIVERED

    profile = db.query(PartnerProfile).filter(PartnerProfile.user_id == user.id).first()
    if profile:
        profile.is_available = True

    db.commit()
    db.refresh(order)
    return order
