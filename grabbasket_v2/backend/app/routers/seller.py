from datetime import time
from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..db import get_db
from ..deps import get_current_user
from ..models import Vendor, Order, Product, User
from ..schemas import VendorSettingsIn, ProductOut, ProductUpsert, OrderOut, OrderItemOut

router = APIRouter(prefix="/seller", tags=["seller"])


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


@router.post("/vendor")
def create_or_attach_vendor(
    name: str,
    description: str = "",
    address: str = "",
    db: Session = Depends(get_db),
    user=Depends(get_current_user),
):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")

    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if v:
        # Update basic fields
        v.name = name or v.name
        v.description = description
        v.address = address
        db.commit()
        return {"ok": True, "vendor_id": v.id}

    v = Vendor(
        seller_id=user.id,
        name=name,
        description=description,
        address=address,
        delivery_radius_km=5.0,
        is_open=True,
    )
    db.add(v)
    db.commit()
    db.refresh(v)
    return {"ok": True, "vendor_id": v.id}


@router.post("/vendor/settings")
def vendor_settings(
    payload: VendorSettingsIn,
    db: Session = Depends(get_db),
    user=Depends(get_current_user),
):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")

    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")

    v.delivery_radius_km = float(payload.delivery_radius_km)
    v.is_open = bool(payload.is_open)

    if payload.lat is not None:
        v.lat = float(payload.lat)
    if payload.lng is not None:
        v.lng = float(payload.lng)

    def parse_hhmm(x: str | None) -> time | None:
        if not x:
            return None
        hh, mm = x.split(":")
        return time(int(hh), int(mm))

    v.open_time = parse_hhmm(payload.open_time)
    v.close_time = parse_hhmm(payload.close_time)

    db.commit()
    return {"ok": True}


# Catalog CRUD
@router.get("/products", response_model=list[ProductOut])
def list_products(db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")
    rows = db.query(Product).filter(Product.vendor_id == v.id).order_by(Product.id.desc()).all()
    return [
        ProductOut(
            id=p.id,
            vendor_id=p.vendor_id,
            name=p.name,
            description=p.description,
            price=float(p.price),
            is_available=p.is_available,
        )
        for p in rows
    ]


@router.post("/products", response_model=ProductOut)
def create_product(payload: ProductUpsert, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")

    p = Product(
        vendor_id=v.id,
        name=payload.name,
        description=payload.description,
        price=float(payload.price),
        is_available=bool(payload.is_available),
    )
    db.add(p)
    db.commit()
    db.refresh(p)
    return ProductOut(id=p.id, vendor_id=p.vendor_id, name=p.name, description=p.description, price=float(p.price), is_available=p.is_available)


@router.put("/products/{product_id}", response_model=ProductOut)
def update_product(product_id: int, payload: ProductUpsert, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")

    p = db.query(Product).filter(Product.id == product_id, Product.vendor_id == v.id).first()
    if not p:
        raise HTTPException(404, "Product not found")

    p.name = payload.name
    p.description = payload.description
    p.price = float(payload.price)
    p.is_available = bool(payload.is_available)
    db.commit()
    db.refresh(p)
    return ProductOut(id=p.id, vendor_id=p.vendor_id, name=p.name, description=p.description, price=float(p.price), is_available=p.is_available)


@router.get("/orders", response_model=list[OrderOut])
def seller_orders(db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")

    rows = db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()
    return [_order_out(o) for o in rows]


@router.post("/orders/{order_id}/accept", response_model=OrderOut)
def accept_order(order_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "SELLER":
        raise HTTPException(403, "Only SELLER")

    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(400, "Create vendor first")

    o = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not o:
        raise HTTPException(404, "Order not found")

    if o.status != "CREATED":
        return _order_out(o)

    o.status = "ACCEPTED_BY_SELLER"

    # Assign first available partner (MVP)
    partner = (
        db.query(User)
        .filter(User.role == "PARTNER", User.is_partner_available == True)
        .order_by(User.id.asc())
        .first()
    )
    if partner:
        o.partner_id = partner.id
        o.status = "ASSIGNED_TO_PARTNER"

    db.commit()
    db.refresh(o)
    return _order_out(o)
