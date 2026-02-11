from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Product, Order, FcmToken, OrderEvent
from ..schemas import VendorUpdateIn, ProductCreateIn, ProductUpdateIn, ProductOut, OrderOut
from ..notifications import send_push

router = APIRouter(prefix="/seller", tags=["seller"])


def _my_vendor(db: Session, user: User) -> Vendor:
    v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")
    return v


def _assign_partner_if_possible(db: Session, order: Order) -> None:
    # Minimal: pick first available partner
    partner = (
        db.query(User)
        .filter(User.role == "PARTNER")
        .filter(User.is_partner_available == True)  # noqa
        .first()
    )
    if not partner:
        return

    order.partner_id = partner.id
    order.status = "ASSIGNED_TO_PARTNER"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Partner assigned", actor_user_id=None))

    ptokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == partner.id).all()]
    send_push(ptokens, "Pickup assigned", f"Order #{order.id} assigned to you", data={"order_id": str(order.id)})


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
    v = _my_vendor(db, user)

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


# Alias endpoint used by the Flutter client.
@router.post("/vendor/settings", dependencies=[Depends(require_role("SELLER"))])
def vendor_settings(payload: VendorUpdateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    """Update vendor settings (location, delivery radius, open/close, etc.)."""

    v = _my_vendor(db, user)

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


@router.post("/products", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
def create_product(payload: ProductCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)

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
    v = _my_vendor(db, user)

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
    v = _my_vendor(db, user)

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
    v = _my_vendor(db, user)

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != "CREATED":
        raise HTTPException(status_code=400, detail="Order cannot be accepted")

    order.status = "ACCEPTED_BY_SELLER"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Accepted by seller", actor_user_id=user.id))

    # auto assign partner if available
    _assign_partner_if_possible(db, order)

    db.commit()
    db.refresh(order)

    # Notify customer
    ctokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.customer_id).all()]
    send_push(ctokens, "Order accepted", f"Order #{order.id} accepted by seller", data={"order_id": str(order.id)})

    return order


@router.post("/orders/{order_id}/reject", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def reject_order(
    order_id: int,
    reason: str = "",
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    v = _my_vendor(db, user)

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status not in {"CREATED", "ACCEPTED_BY_SELLER", "ASSIGNED_TO_PARTNER"}:
        raise HTTPException(status_code=400, detail="Order cannot be rejected at this stage")

    order.partner_id = None
    order.status = "REJECTED_BY_SELLER"
    db.add(OrderEvent(order_id=order.id, status=order.status, note=(reason or "Rejected by seller")[:300], actor_user_id=user.id))
    db.commit()
    db.refresh(order)

    ctokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.customer_id).all()]
    send_push(ctokens, "Order rejected", f"Order #{order.id} was rejected", data={"order_id": str(order.id)})

    return order


@router.post("/orders/{order_id}/ready", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def mark_ready_for_pickup(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    """Mark order as READY_FOR_PICKUP.

    This is closer to Swiggy's flow: partner picks up only when restaurant marks it ready.
    """
    v = _my_vendor(db, user)

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status not in {"ACCEPTED_BY_SELLER", "ASSIGNED_TO_PARTNER"}:
        raise HTTPException(status_code=400, detail="Order cannot be marked ready at this stage")

    if not order.partner_id:
        _assign_partner_if_possible(db, order)

    if not order.partner_id:
        raise HTTPException(status_code=400, detail="No partner available right now")

    order.status = "READY_FOR_PICKUP"
    db.add(OrderEvent(order_id=order.id, status=order.status, note="Ready for pickup", actor_user_id=user.id))

    db.commit()
    db.refresh(order)

    ptokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == order.partner_id).all()]
    send_push(ptokens, "Ready for pickup", f"Order #{order.id} is ready for pickup", data={"order_id": str(order.id)})

    return order
