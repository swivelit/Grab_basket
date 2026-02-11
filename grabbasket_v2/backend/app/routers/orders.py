from __future__ import annotations

from datetime import datetime
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import (
    User,
    Vendor,
    Product,
    Order,
    OrderItem,
    OrderEvent,
    CustomerAddress,
    PartnerLocation,
    FcmToken,
)
from ..schemas import OrderCreateIn, OrderOut, OrderTrackingOut
from ..utils.geo import haversine_km
from ..notifications import send_push

router = APIRouter(prefix="/orders", tags=["orders"])


def add_event(db: Session, order: Order, status: str, note: str = "", actor_user_id: int | None = None):
    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note, actor_user_id=actor_user_id))


def _assign_partner(db: Session, order: Order) -> None:
    # Minimal: pick first available partner
    partner = (
        db.query(User)
        .filter(User.role == "PARTNER")
        .filter(User.is_partner_available == True)  # noqa
        .first()
    )
    if partner:
        order.partner_id = partner.id
        add_event(db, order, "ASSIGNED_TO_PARTNER", "Auto-assigned partner", actor_user_id=None)

        tokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == partner.id).all()]
        send_push(tokens, "New delivery", f"Order #{order.id} assigned to you", data={"order_id": str(order.id)})


@router.post("", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def create_order(payload: OrderCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    vendor = db.query(Vendor).filter(Vendor.id == payload.vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    # Delivery address
    delivery_lat = None
    delivery_lng = None
    if payload.delivery_address_id is not None:
        addr = (
            db.query(CustomerAddress)
            .filter(CustomerAddress.id == payload.delivery_address_id)
            .filter(CustomerAddress.customer_id == user.id)
            .first()
        )
        if not addr:
            raise HTTPException(status_code=400, detail="Invalid address")
        delivery_lat, delivery_lng = addr.lat, addr.lng

        # enforce delivery radius if vendor has coords
        if vendor.lat is not None and vendor.lng is not None:
            dist = haversine_km(delivery_lat, delivery_lng, vendor.lat, vendor.lng)
            if dist > float(vendor.delivery_radius_km):
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
    db.flush()  # get order.id

    subtotal = 0.0
    for it in payload.items:
        p = db.query(Product).filter(Product.id == it.product_id, Product.vendor_id == vendor.id).first()
        if not p or not p.is_available:
            raise HTTPException(status_code=400, detail=f"Invalid/unavailable product {it.product_id}")

        line = float(p.price) * int(it.qty)
        subtotal += line
        db.add(
            OrderItem(
                order_id=order.id,
                product_id=p.id,
                name_snapshot=p.name,
                price_snapshot=p.price,
                qty=it.qty,
            )
        )

    order.subtotal_amount = round(subtotal, 2)

    # delivery fee stub: later dynamic by distance
    order.delivery_fee = 20.0 if delivery_lat is not None else 0.0
    order.total_amount = round(order.subtotal_amount + order.delivery_fee, 2)

    add_event(db, order, "CREATED", "Order created", actor_user_id=user.id)

    # For UPI: generate a ref now (client can show QR / intent later)
    if order.payment_method == "UPI":
        order.payment_ref = f"UPI-DEMO-{order.id}-{int(datetime.utcnow().timestamp())}"

    db.commit()
    db.refresh(order)

    # Notify seller (if seller has FCM)
    seller_tokens = []
    if vendor.seller_id:
        seller_tokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == vendor.seller_id).all()]
    send_push(seller_tokens, "New order", f"Order #{order.id} placed", data={"order_id": str(order.id)})

    return order


@router.post("/{order_id}/cancel", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def cancel_order(
    order_id: int,
    reason: str = "",
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    non_cancellable = {"PICKED_UP", "DELIVERED", "CANCELLED_BY_CUSTOMER", "REJECTED_BY_SELLER"}
    if order.status in non_cancellable:
        raise HTTPException(status_code=400, detail="Order cannot be cancelled at this stage")

    order.partner_id = None  # release any assignment (MVP)
    add_event(
        db,
        order,
        "CANCELLED_BY_CUSTOMER",
        (reason or "Cancelled by customer")[:300],
        actor_user_id=user.id,
    )

    # Payment handling (MVP placeholders)
    if order.payment_status == "PAID":
        order.payment_status = "REFUND_PENDING"
    elif order.payment_status == "PENDING" and order.payment_method != "COD":
        order.payment_status = "CANCELLED"

    db.commit()
    db.refresh(order)

    # Notify seller and partner (if any)
    # Seller
    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    if vendor and vendor.seller_id:
        stokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == vendor.seller_id).all()]
        send_push(stokens, "Order cancelled", f"Order #{order.id} was cancelled by customer", data={"order_id": str(order.id)})

    return order


@router.get("/me", response_model=list[OrderOut])
def my_orders(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if user.role == "CUSTOMER":
        return db.query(Order).filter(Order.customer_id == user.id).order_by(Order.id.desc()).all()
    if user.role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
        if not v:
            return []
        return db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()
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


@router.get("/{order_id}/tracking", response_model=OrderTrackingOut)
def tracking(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    # access checks
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
