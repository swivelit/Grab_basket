from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Order, PartnerLocation, OrderEvent, FcmToken
from ..schemas import PartnerLocationIn, OrderOut
from ..notifications import send_push

router = APIRouter(prefix="/partner", tags=["partner"])

# Partner order states that mean the partner is currently busy.
ACTIVE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}


def _has_active_order(db: Session, partner_id: int) -> bool:
    row = (
        db.query(Order.id)
        .filter(Order.partner_id == partner_id)
        .filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))
        .first()
    )
    return row is not None


@router.post("/availability", dependencies=[Depends(require_role("PARTNER"))])
def set_availability(is_available: bool, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    # If the partner is currently handling an order, do not allow them to toggle "available" to True.
    if is_available and _has_active_order(db, user.id):
        user.is_partner_available = False
        db.commit()
        return {"ok": True, "is_available": False, "reason": "You have an active order"}

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

    if order.status not in {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"}:
        raise HTTPException(status_code=400, detail="Cannot pickup at this stage")

    # Partner is now busy.
    user.is_partner_available = False

    order.status = "PICKED_UP"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Picked up by partner", actor_user_id=user.id))
    db.commit()
    db.refresh(order)

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

    if order.payment_method == "COD":
        order.payment_status = "PAID"

    # Mark partner free again (simple MVP rule: one active order at a time).
    user.is_partner_available = True

    db.commit()
    db.refresh(order)

    ctokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.customer_id).all()]
    send_push(ctokens, "Delivered", f"Order #{order.id} delivered", data={"order_id": str(order.id)})

    return order
