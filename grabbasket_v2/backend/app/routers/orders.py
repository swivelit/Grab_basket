from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..db import get_db
from ..deps import get_current_user
from ..geo import haversine_km
from ..models import Vendor, Product, Order, OrderItem, CustomerAddress, User
from ..schemas import OrderCreateIn, OrderOut, OrderItemOut

router = APIRouter(prefix="/orders", tags=["orders"])


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


@router.post("", response_model=OrderOut)
def create_order(payload: OrderCreateIn, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "CUSTOMER":
        raise HTTPException(403, "Only CUSTOMER can create orders")

    vendor = db.query(Vendor).filter(Vendor.id == payload.vendor_id).first()
    if not vendor or not vendor.is_open:
        raise HTTPException(404, "Vendor not found or closed")

    addr = db.query(CustomerAddress).filter(
        CustomerAddress.id == payload.delivery_address_id,
        CustomerAddress.customer_id == user.id
    ).first()
    if not addr:
        raise HTTPException(400, "Delivery address not found")

    # Delivery radius check (if vendor geo exists)
    if vendor.lat is not None and vendor.lng is not None:
        dist = haversine_km(addr.lat, addr.lng, vendor.lat, vendor.lng)
        if dist > float(vendor.delivery_radius_km):
            raise HTTPException(400, "Address is outside vendor delivery radius")

    # Build items
    subtotal = 0.0
    items: list[OrderItem] = []
    for it in payload.items:
        p = db.query(Product).filter(Product.id == it.product_id, Product.vendor_id == vendor.id).first()
        if not p or not p.is_available:
            raise HTTPException(400, f"Invalid product: {it.product_id}")
        subtotal += float(p.price) * int(it.qty)
        items.append(
            OrderItem(
                product_id=p.id,
                name_snapshot=p.name,
                price_snapshot=float(p.price),
                qty=int(it.qty),
            )
        )

    # Basic fee logic (replace later)
    delivery_fee = 30.0 if subtotal < 300 else 0.0
    total = subtotal + delivery_fee

    payment_method = payload.payment_method.upper()
    if payment_method not in ("COD", "UPI"):
        raise HTTPException(400, "payment_method must be COD or UPI")

    o = Order(
        vendor_id=vendor.id,
        customer_id=user.id,
        status="CREATED",
        delivery_address_id=addr.id,
        delivery_lat=addr.lat,
        delivery_lng=addr.lng,
        subtotal_amount=subtotal,
        delivery_fee=delivery_fee,
        total_amount=total,
        payment_method=payment_method,
        payment_status="PENDING",
    )
    db.add(o)
    db.flush()  # obtain order id

    for i in items:
        i.order_id = o.id
        db.add(i)

    db.commit()
    db.refresh(o)
    return _order_out(o)


@router.get("/me", response_model=list[OrderOut])
def my_orders(db: Session = Depends(get_db), user=Depends(get_current_user)):
    q = db.query(Order).filter(Order.customer_id == user.id).order_by(Order.id.desc()).all()
    return [_order_out(o) for o in q]
