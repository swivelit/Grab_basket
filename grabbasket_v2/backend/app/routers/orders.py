from __future__ import annotations

from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import (
    User, Vendor, Product, Order, OrderItem, OrderEvent,
    CustomerAddress, PartnerLocation, FcmToken
)
from ..schemas import OrderCreateIn, OrderOut, OrderTrackingOut
from ..utils.geo import haversine_km
from ..notifications import send_push

router = APIRouter(prefix="/orders", tags=["orders"])


def add_event(db: Session, order: Order, status: str, note: str = "", actor_user_id: int | None = None) -> None:
    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note, actor_user_id=actor_user_id))


def _seller_tokens(db: Session, vendor: Vendor) -> list[str]:
    if not vendor.seller_id:
        return []
    return [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == vendor.seller_id).all()]


@router.post("", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def create_order(payload: OrderCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    vendor = db.query(Vendor).filter(Vendor.id == payload.vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    delivery_lat = None
    delivery_lng = None
    distance_km = None

    if payload.delivery_address_id is not None:
        addr = (
            db.query(CustomerAddress)
            .filter(CustomerAddress.id == payload.delivery_address_id, CustomerAddress.customer_id == user.id)
            .first()
        )
        if not addr:
            raise HTTPException(status_code=400, detail="Invalid address")

        delivery_lat, delivery_lng = addr.lat, addr.lng

        if vendor.lat is not None and vendor.lng is not None:
            distance_km = haversine_km(delivery_lat, delivery_lng, vendor.lat, vendor.lng)
            if distance_km > float(vendor.delivery_radius_km):
                raise HTTPException(status_code=400, detail="Address outside delivery radius")

    order = Order(
        vendor_id=vendor.id,
        customer_id=user.id,
        status="CREATED",
        delivery_address_id=payload.delivery_address_id,
        delivery_lat=delivery_lat,
        delivery_lng=delivery_lng,
        payment_method=payload.payment_method,
        payment_status="PENDING",
    )
    db.add(order)
    db.flush()

    subtotal = 0.0
    for it in payload.items:
        p = db.query(Product).filter(Product.id == it.product_id, Product.vendor_id == vendor.id).first()
        if not p or not p.is_available:
            raise HTTPException(status_code=400, detail=f"Invalid/unavailable product {it.product_id}")
        subtotal += float(p.price) * int(it.qty)
        db.add(OrderItem(
            order_id=order.id,
            product_id=p.id,
            name_snapshot=p.name,
            price_snapshot=p.price,
            qty=it.qty,
        ))

    order.subtotal_amount = round(subtotal, 2)

    if distance_km is None:
        order.delivery_fee = 0.0
    else:
        order.delivery_fee = round(max(20.0, 10.0 + (distance_km * 5.0)), 2)

    order.total_amount = round(order.subtotal_amount + order.delivery_fee, 2)
    add_event(db, order, "CREATED", "Order created", actor_user_id=user.id)

    if order.payment_method == "UPI":
        order.payment_ref = f"UPI-DEMO-{order.id}-{int(datetime.utcnow().timestamp())}"

    db.commit()
    db.refresh(order)

    send_push(_seller_tokens(db, vendor), "New order", f"Order #{order.id} placed", data={"order_id": str(order.id)})
    return order


@router.get("/me", response_model=list[OrderOut])
def my_orders(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if user.role == "CUSTOMER":
        return db.query(Order).filter(Order.customer_id == user.id).order_by(Order.id.desc()).all()
    if user.role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
        return [] if not v else db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()
    if user.role == "PARTNER":
        return db.query(Order).filter(Order.partner_id == user.id).order_by(Order.id.desc()).all()
    if user.role == "ADMIN":
        return db.query(Order).order_by(Order.id.desc()).limit(200).all()
    return []


@router.get("/{order_id}", response_model=OrderOut)
def get_order(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if user.role == "CUSTOMER" and order.customer_id != user.id:
        raise HTTPException(status_code=403, detail="Forbidden")
    if user.role == "PARTNER" and order.partner_id != user.id:
        raise HTTPException(status_code=403, detail="Forbidden")
    if user.role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
        if not v or order.vendor_id != v.id:
            raise HTTPException(status_code=403, detail="Forbidden")

    return order


@router.post("/{order_id}/cancel", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def cancel_order(order_id: int, reason: str = "", db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status in {"PICKED_UP", "DELIVERED"}:
        raise HTTPException(status_code=400, detail="Order cannot be cancelled now")
    if order.status.startswith("CANCELLED"):
        return order

    add_event(db, order, "CANCELLED_BY_CUSTOMER", reason or "Cancelled", actor_user_id=user.id)
    db.commit()
    db.refresh(order)

    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    if vendor:
        send_push(_seller_tokens(db, vendor), "Order cancelled", f"Order #{order.id} cancelled", data={"order_id": str(order.id)})
    if order.partner_id:
        ptokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.partner_id).all()]
        send_push(ptokens, "Order cancelled", f"Order #{order.id} cancelled", data={"order_id": str(order.id)})

    return order


@router.get("/{order_id}/tracking", response_model=OrderTrackingOut)
def tracking(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    allowed = False
    if user.role == "ADMIN":
        allowed = True
    elif user.role == "CUSTOMER" and order.customer_id == user.id:
        allowed = True
    elif user.role == "PARTNER" and order.partner_id == user.id:
        allowed = True
    elif user.role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
        allowed = bool(v and order.vendor_id == v.id)

    if not allowed:
        raise HTTPException(status_code=403, detail="Forbidden")

    latest_loc = None
    if order.partner_id:
        loc = (
            db.query(PartnerLocation)
            .filter(PartnerLocation.partner_id == order.partner_id)
            .order_by(PartnerLocation.created_at.desc())
            .first()
        )
        if loc:
            latest_loc = {
                "lat": loc.lat,
                "lng": loc.lng,
                "heading": loc.heading,
                "speed": loc.speed,
                "created_at": loc.created_at,
            }

    return {"order": order, "partner_latest_location": latest_loc}
