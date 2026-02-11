from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import User, Role, Order, OrderStatus, PartnerLocation, DeviceToken
from ..schemas import PartnerAvailabilityIn, PartnerLocationIn, OrderOut
from ..security import require_role
from ..notifications import send_push

router = APIRouter(prefix="/partner", tags=["partner"])


@router.post("/availability")
def set_availability(data: PartnerAvailabilityIn, user: User = Depends(require_role(Role.PARTNER)), db: Session = Depends(get_db)):
    user.is_partner_available = data.is_available
    db.commit()
    return {"ok": True, "is_available": user.is_partner_available}


@router.post("/location")
def update_location(data: PartnerLocationIn, user: User = Depends(require_role(Role.PARTNER)), db: Session = Depends(get_db)):
    loc = PartnerLocation(
        partner_id=user.id,
        lat=data.lat,
        lng=data.lng,
        heading=data.heading,
        speed=data.speed,
    )
    db.add(loc)
    db.commit()
    return {"ok": True}


@router.get("/orders", response_model=list[OrderOut])
def my_assigned_orders(user: User = Depends(require_role(Role.PARTNER)), db: Session = Depends(get_db)):
    return (
        db.query(Order)
        .filter(Order.partner_id == user.id)
        .order_by(Order.id.desc())
        .all()
    )


@router.post("/orders/{order_id}/pickup", response_model=OrderOut)
def pickup(order_id: int, user: User = Depends(require_role(Role.PARTNER)), db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != OrderStatus.ASSIGNED_TO_PARTNER:
        raise HTTPException(status_code=400, detail=f"Cannot pickup in status {order.status}")

    order.status = OrderStatus.PICKED_UP
    db.commit()
    db.refresh(order)

    # notify customer
    from ..models import User as U
    cust = db.query(U).filter(U.id == order.customer_id).first()
    tokens = [t.token for t in (cust.device_tokens if cust else [])]
    send_push(tokens, "Order picked up", f"Partner picked up order #{order.id}", {"order_id": order.id, "status": order.status.value})

    return order


@router.post("/orders/{order_id}/deliver", response_model=OrderOut)
def deliver(order_id: int, user: User = Depends(require_role(Role.PARTNER)), db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != OrderStatus.PICKED_UP:
        raise HTTPException(status_code=400, detail=f"Cannot deliver in status {order.status}")

    order.status = OrderStatus.DELIVERED
    db.commit()
    db.refresh(order)

    # notify customer
    from ..models import User as U
    cust = db.query(U).filter(U.id == order.customer_id).first()
    tokens = [t.token for t in (cust.device_tokens if cust else [])]
    send_push(tokens, "Delivered!", f"Order #{order.id} delivered", {"order_id": order.id, "status": order.status.value})

    return order
