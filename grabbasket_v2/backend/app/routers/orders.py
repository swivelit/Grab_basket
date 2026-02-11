from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import (
    User, Role, Vendor, Product, Order, OrderItem,
    CustomerAddress, OrderStatus, PaymentMethod, PaymentStatus,
)
from ..schemas import OrderCreateIn, OrderOut, PartnerLocationOut
from ..security import require_role
from ..settings import settings
from ..utils_geo import haversine_km

router = APIRouter(prefix="/orders", tags=["orders"])


def _get_delivery_point(data: OrderCreateIn, customer_id: int, db: Session):
    # Prefer address_id; fallback lat/lng for quick testing
    if data.delivery_address_id:
        addr = db.query(CustomerAddress).filter(
            CustomerAddress.id == data.delivery_address_id,
            CustomerAddress.customer_id == customer_id,
        ).first()
        if not addr:
            raise HTTPException(status_code=400, detail="Invalid delivery address")
        return addr.id, addr.lat, addr.lng

    if data.delivery_lat is None or data.delivery_lng is None:
        raise HTTPException(status_code=400, detail="Provide delivery_address_id or delivery_lat/lng")
    return None, data.delivery_lat, data.delivery_lng


@router.post("", response_model=OrderOut)
def create_order(
    data: OrderCreateIn,
    user: User = Depends(require_role(Role.CUSTOMER)),
    db: Session = Depends(get_db),
):
    vendor = db.query(Vendor).filter(Vendor.id == data.vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    addr_id, dlat, dlng = _get_delivery_point(data, user.id, db)

    # Validate delivery radius (if vendor has geo)
    if vendor.lat is not None and vendor.lng is not None:
        dist = haversine_km(vendor.lat, vendor.lng, dlat, dlng)
        if dist > vendor.delivery_radius_km:
            raise HTTPException(
                status_code=400,
                detail=f"Delivery out of range. Distance {dist:.2f} km > {vendor.delivery_radius_km:.2f} km",
            )

    # Build items
    if not data.items:
        raise HTTPException(status_code=400, detail="No items")

    product_ids = [x.product_id for x in data.items]
    products = db.query(Product).filter(Product.id.in_(product_ids), Product.vendor_id == vendor.id).all()
    prod_map = {p.id: p for p in products}

    subtotal = 0.0
    items_rows: list[OrderItem] = []
    for it in data.items:
        p = prod_map.get(it.product_id)
        if not p:
            raise HTTPException(status_code=400, detail=f"Invalid product {it.product_id}")
        if not p.is_available:
            raise HTTPException(status_code=400, detail=f"Product not available: {p.name}")
        subtotal += float(p.price) * it.qty
        items_rows.append(
            OrderItem(
                product_id=p.id,
                name_snapshot=p.name,
                price_snapshot=float(p.price),
                qty=it.qty,
            )
        )

    delivery_fee = float(settings.base_delivery_fee)
    total = subtotal + delivery_fee

    order = Order(
        vendor_id=vendor.id,
        customer_id=user.id,
        status=OrderStatus.CREATED,
        delivery_address_id=addr_id,
        delivery_lat=dlat,
        delivery_lng=dlng,
        subtotal_amount=subtotal,
        delivery_fee=delivery_fee,
        total_amount=total,
        payment_method=data.payment_method,
        payment_status=PaymentStatus.PENDING if data.payment_method != PaymentMethod.COD else PaymentStatus.PENDING,
    )
    db.add(order)
    db.flush()  # get order.id
    for row in items_rows:
        row.order_id = order.id
        db.add(row)
    db.commit()
    db.refresh(order)
    return order


@router.get("/me", response_model=list[OrderOut])
def my_orders(user: User = Depends(require_role(Role.CUSTOMER)), db: Session = Depends(get_db)):
    return (
        db.query(Order)
        .filter(Order.customer_id == user.id)
        .order_by(Order.id.desc())
        .all()
    )


@router.get("/{order_id}", response_model=OrderOut)
def get_order(order_id: int, user: User = Depends(require_role(Role.CUSTOMER)), db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    return order


@router.get("/{order_id}/track")
def track_order(order_id: int, user: User = Depends(require_role(Role.CUSTOMER)), db: Session = Depends(get_db)):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    partner_loc = None
    if order.partner_id:
        loc = (
            db.query("dummy")  # placeholder to keep mypy away
        )

        # latest partner location
        from ..models import PartnerLocation
        partner_loc = (
            db.query(PartnerLocation)
            .filter(PartnerLocation.partner_id == order.partner_id)
            .order_by(PartnerLocation.id.desc())
            .first()
        )

    return {
        "order_id": order.id,
        "status": order.status,
        "partner_id": order.partner_id,
        "partner_location": None
        if not partner_loc
        else PartnerLocationOut(
            lat=partner_loc.lat,
            lng=partner_loc.lng,
            heading=partner_loc.heading,
            speed=partner_loc.speed,
            created_at=partner_loc.created_at,
        ).model_dump(),
    }
