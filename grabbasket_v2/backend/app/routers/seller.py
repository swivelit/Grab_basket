from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Product, Order, OrderEvent, FcmToken
from ..schemas import VendorUpdateIn, ProductCreateIn, ProductUpdateIn, ProductOut, OrderOut
from ..notifications import send_push

router = APIRouter(prefix="/seller", tags=["seller"])


def _my_vendor(db: Session, user: User) -> Vendor | None:
    return db.query(Vendor).filter(Vendor.seller_id == user.id).first()


def _user_tokens(db: Session, user_id: int) -> list[str]:
    return [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == user_id).all()]


def _add_event(db: Session, order: Order, status: str, note: str, actor_user_id: int | None):
    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note, actor_user_id=actor_user_id))


def _try_assign_partner(db: Session, order: Order) -> None:
    if order.partner_id:
        return
    partner = (
        db.query(User)
        .filter(User.role == "PARTNER")
        .filter(User.is_partner_available == True)  # noqa
        .first()
    )
    if not partner:
        return
    order.partner_id = partner.id
    _add_event(db, order, "ASSIGNED_TO_PARTNER", "Partner assigned", actor_user_id=None)
    send_push(_user_tokens(db, partner.id), "New pickup", f"Order #{order.id} assigned", data={"order_id": str(order.id)})


@router.get("/vendor", dependencies=[Depends(require_role("SELLER"))])
def my_vendor(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    return _my_vendor(db, user)


@router.post("/vendor", dependencies=[Depends(require_role("SELLER"))])
def create_or_attach_vendor(
    name: str,
    description: str = "",
    address: str = "",
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    v = _my_vendor(db, user)
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
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


@router.post("/vendor/settings", dependencies=[Depends(require_role("SELLER"))])
def vendor_settings(payload: VendorUpdateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    for k, val in payload.model_dump(exclude_unset=True).items():
        setattr(v, k, val)

    db.commit()
    return {"ok": True}


@router.post("/products", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
def create_product(payload: ProductCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    p = Product(vendor_id=v.id, **payload.model_dump())
    db.add(p)
    db.commit()
    db.refresh(p)
    return p


@router.get("/products", response_model=list[ProductOut], dependencies=[Depends(require_role("SELLER"))])
def list_products(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        return []
    return db.query(Product).filter(Product.vendor_id == v.id).order_by(Product.id.desc()).all()


@router.put("/products/{product_id}", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
@router.patch("/products/{product_id}", response_model=ProductOut, dependencies=[Depends(require_role("SELLER"))])
def update_product(product_id: int, payload: ProductUpdateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
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
    v = _my_vendor(db, user)
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
    v = _my_vendor(db, user)
    if not v:
        return []
    return db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()


@router.post("/orders/{order_id}/accept", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def accept_order(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status != "CREATED":
        raise HTTPException(status_code=400, detail="Order cannot be accepted")

    _add_event(db, order, "ACCEPTED_BY_SELLER", "Accepted by seller", actor_user_id=user.id)
    _try_assign_partner(db, order)

    db.commit()
    db.refresh(order)

    send_push(_user_tokens(db, order.customer_id), "Order accepted", f"Order #{order.id} accepted", data={"order_id": str(order.id)})
    return order


@router.post("/orders/{order_id}/reject", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def reject_order(order_id: int, reason: str = "", db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status in {"PICKED_UP", "DELIVERED"}:
        raise HTTPException(status_code=400, detail="Order cannot be rejected now")
    if order.status.startswith("REJECTED"):
        return order

    partner_id = order.partner_id
    order.partner_id = None

    _add_event(db, order, "REJECTED_BY_SELLER", reason or "Rejected", actor_user_id=user.id)
    db.commit()
    db.refresh(order)

    send_push(_user_tokens(db, order.customer_id), "Order rejected", f"Order #{order.id} rejected", data={"order_id": str(order.id)})
    if partner_id:
        send_push(_user_tokens(db, partner_id), "Order unassigned", f"Order #{order.id} was unassigned", data={"order_id": str(order.id)})

    return order


@router.post("/orders/{order_id}/ready", response_model=OrderOut, dependencies=[Depends(require_role("SELLER"))])
def mark_ready(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    v = _my_vendor(db, user)
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")

    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == v.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if order.status not in {"ACCEPTED_BY_SELLER", "ASSIGNED_TO_PARTNER"}:
        raise HTTPException(status_code=400, detail="Order not ready at this stage")

    _try_assign_partner(db, order)
    _add_event(db, order, "READY_FOR_PICKUP", "Ready for pickup", actor_user_id=user.id)

    db.commit()
    db.refresh(order)

    send_push(_user_tokens(db, order.customer_id), "Order ready", f"Order #{order.id} is ready", data={"order_id": str(order.id)})
    if order.partner_id:
        send_push(_user_tokens(db, order.partner_id), "Pickup ready", f"Order #{order.id} ready for pickup", data={"order_id": str(order.id)})

    return order
