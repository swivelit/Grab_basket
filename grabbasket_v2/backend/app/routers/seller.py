from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import User, Role, Vendor, Product, Order, OrderStatus
from ..schemas import (
    SellerVendorUpsertIn, VendorOut,
    ProductCreateIn, ProductUpdateIn, ProductOut,
    OrderOut,
)
from ..security import require_role
from ..notifications import send_push

router = APIRouter(prefix="/seller", tags=["seller"])


@router.get("/vendor", response_model=VendorOut)
def my_vendor(user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="No vendor yet")
    return v


@router.post("/vendor", response_model=VendorOut)
def upsert_vendor(data: SellerVendorUpsertIn, user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        v = Vendor(
            seller_id=user.id,
            name=data.name,
            description=data.description,
            address=data.address,
            lat=data.lat,
            lng=data.lng,
            delivery_radius_km=data.delivery_radius_km,
            is_open=data.is_open,
            open_time=data.open_time,
            close_time=data.close_time,
        )
        db.add(v)
        db.commit()
        db.refresh(v)
        return v

    v.name = data.name
    v.description = data.description
    v.address = data.address
    v.lat = data.lat
    v.lng = data.lng
    v.delivery_radius_km = data.delivery_radius_km
    v.is_open = data.is_open
    v.open_time = data.open_time
    v.close_time = data.close_time

    db.commit()
    db.refresh(v)
    return v


@router.get("/products", response_model=list[ProductOut])
def my_products(user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=400, detail="Create vendor first")
    return db.query(Product).filter(Product.vendor_id == v.id).order_by(Product.id.desc()).all()


@router.post("/products", response_model=ProductOut)
def create_product(data: ProductCreateIn, user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=400, detail="Create vendor first")
    p = Product(
        vendor_id=v.id,
        name=data.name,
        description=data.description,
        price=data.price,
        is_available=data.is_available,
    )
    db.add(p)
    db.commit()
    db.refresh(p)
    return p


@router.patch("/products/{product_id}", response_model=ProductOut)
def update_product(product_id: int, data: ProductUpdateIn, user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=400, detail="Create vendor first")

    p = db.query(Product).filter(Product.id == product_id, Product.vendor_id == v.id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")

    if data.name is not None:
        p.name = data.name
    if data.description is not None:
        p.description = data.description
    if data.price is not None:
        p.price = data.price
    if data.is_available is not None:
        p.is_available = data.is_available

    db.commit()
    db.refresh(p)
    return p


@router.get("/orders", response_model=list[OrderOut])
def seller_orders(user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=400, detail="Create vendor first")

    return db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()


@router.post("/orders/{order_id}/accept", response_model=OrderOut)
def accept_order(order_id: int, user: User = Depends(require_role(Role.SELLER)), db: Session = Depends(get_db)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=400, detail="Create vendor first")

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != OrderStatus.CREATED:
        raise HTTPException(status_code=400, detail=f"Cannot accept in status {order.status}")

    order.status = OrderStatus.ACCEPTED_BY_SELLER
    db.commit()
    db.refresh(order)

    # Notify customer (if token exists)
    from ..models import DeviceToken, User as U
    cust = db.query(U).filter(U.id == order.customer_id).first()
    tokens = [t.token for t in (cust.device_tokens if cust else [])]
    send_push(tokens, "Order accepted", f"Your order #{order.id} was accepted", {"order_id": order.id, "status": order.status.value})

    return order
