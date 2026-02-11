from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..db import get_db
from ..deps import get_current_user
from ..models import User, Order, PartnerLocation
from ..schemas import OrderOut, OrderItemOut, PartnerLocationIn, PartnerLocationOut

router = APIRouter(prefix="/partner", tags=["partner"])


def _order_out(o: Order) -> OrderOut:
    return OrderOut(
        id=o.id,
        vendor_id=o.vendor_id,
        customer_id=o.customer_id,
        partner_id=o.partner_id,
        status=o.status,
        subtotal_amount=float(o.subtotal_amount),
        delivery_fee=float(o.delivery_fee),
        total_amount=float(o.total_amount),
        payment_method=o.payment_method,
        payment_status=o.payment_status,
        payment_ref=o.payment_ref,
        delivery_lat=o.delivery_lat,
        delivery_lng=o.delivery_lng,
        items=[
            OrderItemOut(
                product_id=i.product_id,
                name_snapshot=i.name_snapshot,
                price_snapshot=float(i.price_snapshot),
                qty=i.qty,
            )
            for i in o.items
        ],
    )


@router.post("/availability")
def set_availability(is_available: bool, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    u = db.query(User).filter(User.id == user.id).first()
    u.is_partner_available = bool(is_available)
    db.commit()
    return {"ok": True}


@router.get("/orders", response_model=list[OrderOut])
def partner_orders(db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    rows = db.query(Order).filter(Order.partner_id == user.id).order_by(Order.id.desc()).all()
    return [_order_out(o) for o in rows]


@router.post("/orders/{order_id}/pickup", response_model=OrderOut)
def pickup(order_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    o = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not o:
        raise HTTPException(404, "Order not found")
    if o.status == "ASSIGNED_TO_PARTNER":
        o.status = "PICKED_UP"
        db.commit()
        db.refresh(o)
    return _order_out(o)


@router.post("/orders/{order_id}/deliver", response_model=OrderOut)
def deliver(order_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    o = db.query(Order).filter(Order.id == order_id, Order.partner_id == user.id).first()
    if not o:
        raise HTTPException(404, "Order not found")
    if o.status == "PICKED_UP":
        o.status = "DELIVERED"
        # mark payment paid for COD at delivery (simple)
        if o.payment_method == "COD":
            o.payment_status = "PAID"
        db.commit()
        db.refresh(o)
    return _order_out(o)


@router.post("/location")
def partner_location(payload: PartnerLocationIn, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    row = PartnerLocation(
        partner_id=user.id,
        lat=float(payload.lat),
        lng=float(payload.lng),
        heading=payload.heading,
        speed=payload.speed,
    )
    db.add(row)
    db.commit()
    return {"ok": True}


@router.get("/location/latest", response_model=PartnerLocationOut)
def partner_location_latest(db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "PARTNER":
        raise HTTPException(403, "Only PARTNER")
    row = (
        db.query(PartnerLocation)
        .filter(PartnerLocation.partner_id == user.id)
        .order_by(PartnerLocation.id.desc())
        .first()
    )
    if not row:
        raise HTTPException(404, "No location yet")
    return PartnerLocationOut(
        lat=row.lat, lng=row.lng, heading=row.heading, speed=row.speed,
        created_at=row.created_at.isoformat(),
    )
