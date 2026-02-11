from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Product, Order, FcmToken
from ..schemas import VendorUpdateIn, ProductCreateIn, ProductUpdateIn, ProductOut, OrderOut
from ..notifications import send_push

router = APIRouter(prefix="/seller", tags=["seller"])


@router.get("/vendor", dependencies=[Depends(require_role("SELLER"))])
def my_vendor(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    return v


@router.post("/vendor", dependencies=[Depends(require_role("SELLER"))])
def create_or_attach_vendor(
    name: str,
    description: str = "",
    address: str = "",
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if v:
        return {"ok": True, "vendor_id": v.id}

    v = Vendor(seller_id=user.id, name=name, description=description, address=address)
    db.add(v)
    db.commit()
    db.refresh(v)
    return {"ok": True, "vendor_id": v.id}


@router.patch("/vendor", dependencies=[Depends(require_role("SELLER"))])
def update_vendor(payload: VendorUpdateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


# Alias endpoint used by the Flutter client.
@router.post("/vendor/settings", dependencies=[Depends(require_role("SELLER"))])
def vendor_settings(payload: VendorUpdateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    """Update vendor settings (location, delivery radius, open/close, etc.)."""

    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


@router.post("/products", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
def create_product(payload: ProductCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    p = Product(vendor_id=v.id, **payload.model_dump())
    db.add(p)
    db.commit()
    db.refresh(p)
    return p


@router.get("/products", response_model=list[ProductOut], dependencies=[Depends(require_role("SELLER"))])
def list_products(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        return []
    return db.query(Product).filter(Product.vendor_id == v.id).order_by(Product.id.desc()).all()


@router.put("/products/{product_id}", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
@router.patch("/products/{product_id}", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
def update_product(
    product_id: int,
    payload: ProductUpdateIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    p = db.query(Product).filter(Product.id == product_id, Product.vendor_id == v.id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(p, k, val)

    db.commit()
    db.refresh(p)
    return p


@router.delete("/products/{product_id}", dependencies=[Depends(require_role("SELLER"))])
def delete_product(product_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    p = db.query(Product).filter(Product.id == product_id, Product.vendor_id == v.id).first()
    if not p:
        raise HTTPException(status_code=404, detail="Product not found")

    db.delete(p)
    db.commit()
    return {"ok": True}


@router.get("/orders", response_model=list[OrderOut], dependencies=[Depends(require_role("SELLER"))])
def seller_orders(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        return []
    return db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()


@router.post("/orders/{order_id}/accept", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def accept_order(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != "CREATED":
        raise HTTPException(status_code=400, detail="Order cannot be accepted")

    order.status = "ACCEPTED_BY_SELLER"
    from ..models import OrderEvent
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Accepted by seller", actor_user_id=user.id))

    # auto assign partner if available
    from ..models import User as U
    partner = (
        db.query(U)
        .filter(U.role == "PARTNER")
        .filter(U.is_partner_available == True)  # noqa
        .first()
    )
    if partner:
        order.partner_id = partner.id
        order.status = "ASSIGNED_TO_PARTNER"
        db.add(OrderEvent(order_id=order.id, status=order.status, note="Partner assigned", actor_user_id=None))

        ptokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == partner.id).all()]
        send_push(ptokens, "Pickup assigned", f"Order #{order.id} ready for pickup", data={"order_id": str(order.id)})

    db.commit()
    db.refresh(order)
    return order
