from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Order, PartnerLocation, OrderEvent, FcmToken
from ..schemas import PartnerLocationIn, OrderOut
from ..notifications import send_push

router = APIRouter(prefix="/partner", tags=["partner"])


@router.post("/availability", dependencies=[Depends(require_role("PARTNER"))])
def set_availability(is_available: bool, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    user.is_partner_available = is_available
    db.commit()
    return {"ok": True, "is_available": user.is_partner_available}


@router.post("/location", dependencies=[Depends(require_role("PARTNER"))])
def update_location(payload: PartnerLocationIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    db.add(PartnerLocation(partner_id=user.id, **payload.model_dump()))
    db.commit()
    return {"ok": True}


@router.get("/orders", response_model=list[OrderOut], dependencies=[Depends(require_role("PARTNER"))])
def my_assigned_orders(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    return db.query(Order).filter(Order.partner_id == user.id).order_by(Order.id.desc()).all()


@router.post("/orders/{order_id}/pickup", response_model=OrderOut, dependencies=[Depends(require_role("PARTNER"))])
def pickup(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    # Backward compatible: allow pickup when ASSIGNED or READY
    if order.status not in {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"}:
        raise HTTPException(status_code=400, detail="Cannot pickup at this stage")

    order.status = "PICKED_UP"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Picked up by partner", actor_user_id=user.id))
    db.commit()
    db.refresh(order)

    # notify customer
    ctokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.customer_id).all()]
    send_push(ctokens, "Order picked up", f"Order #{order.id} is on the way", data={"order_id": str(order.id)})

    return order


@router.post("/orders/{order_id}/deliver", response_model=OrderOut, dependencies=[Depends(require_role("PARTNER"))])
def deliver(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != "PICKED_UP":
        raise HTTPException(status_code=400, detail="Cannot deliver at this stage")

    order.status = "DELIVERED"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Delivered", actor_user_id=user.id))

    # COD becomes paid on delivery
    if order.payment_method == "COD":
        order.payment_status = "PAID"

    db.commit()
    db.refresh(order)

    ctokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.customer_id).all()]
    send_push(ctokens, "Delivered", f"Order #{order.id} delivered", data={"order_id": str(order.id)})

    return order
