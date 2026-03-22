from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import func
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Product, Order, OrderEvent, FcmToken, PartnerLocation
from ..schemas import VendorUpdateIn, ProductCreateIn, ProductUpdateIn, ProductOut, OrderOut
from ..notifications import build_order_notification_data, send_push
from ..utils.geo import haversine_km

router = APIRouter(prefix="/seller", tags=["seller"])

# Partner order states that mean the partner is currently busy.
ACTIVE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}


def _my_vendor(db: Session, user: User) -> Vendor | None:
    return db.query(Vendor).filter(Vendor.seller_id == user.id).first()


def _user_tokens(db: Session, user_id: int) -> list[str]:
    return [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == user_id).all()]


def _add_event(db: Session, order: Order, status: str, note: str, actor_user_id: int | None):
    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note, actor_user_id=actor_user_id))


def _partner_has_active_order(db: Session, partner_id: int) -> bool:
    row = (
        db.query(Order.id)
        .filter(Order.partner_id == partner_id)
        .filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))
        .first()
    )
    return row is not None


def _maybe_mark_partner_available(db: Session, partner_id: int) -> None:
    partner = db.query(User).filter(User.id == partner_id, User.role == "PARTNER").first()
    if not partner:
        return
    if _partner_has_active_order(db, partner_id):
        return
    partner.is_partner_available = True


def _latest_partner_locations(db: Session, partner_ids: list[int]) -> dict[int, PartnerLocation]:
    """Fetch the latest PartnerLocation per partner_id using a single query."""
    if not partner_ids:
        return {}

    sub = (
        db.query(PartnerLocation.partner_id, func.max(PartnerLocation.id).label("max_id"))
        .filter(PartnerLocation.partner_id.in_(partner_ids))
        .group_by(PartnerLocation.partner_id)
        .subquery()
    )
    rows = db.query(PartnerLocation).join(sub, PartnerLocation.id == sub.c.max_id).all()
    return {r.partner_id: r for r in rows}


def _candidate_partners(db: Session, vendor: Vendor | None) -> tuple[list[tuple[float | None, int]], dict[int, User]]:
    partners = (
        db.query(User)
        .filter(User.role == "PARTNER")
        .filter(User.is_partner_available == True)  # noqa: E712
        .all()
    )
    if not partners:
        return [], {}

    partner_by_id = {p.id: p for p in partners}
    loc_map = _latest_partner_locations(db, list(partner_by_id.keys()))

    vendor_lat = getattr(vendor, "lat", None)
    vendor_lng = getattr(vendor, "lng", None)

    candidates: list[tuple[float | None, int]] = []
    for pid, p in partner_by_id.items():
        # Skip if they already have another active order (safety net).
        if _partner_has_active_order(db, pid):
            continue

        dist: float | None = None
        if vendor_lat is not None and vendor_lng is not None:
            loc = loc_map.get(pid)
            if loc:
                dist = haversine_km(loc.lat, loc.lng, vendor_lat, vendor_lng)

        candidates.append((dist, pid))

    # Prefer known distance; then nearest; then stable id.
    candidates.sort(key=lambda x: (x[0] is None, x[0] if x[0] is not None else 10**12, x[1]))
    return candidates, partner_by_id


def _claim_partner_row(db: Session, partner_id: int) -> bool:
    """Atomically claim a partner by flipping is_partner_available from True -> False.

    This prevents double-assignment under concurrency.
    """
    updated = (
        db.query(User)
        .filter(User.id == partner_id)
        .filter(User.role == "PARTNER")
        .filter(User.is_partner_available == True)  # noqa: E712
        .update({User.is_partner_available: False}, synchronize_session=False)
    )
    db.flush()
    return updated == 1


def _release_partner_row(db: Session, partner_id: int) -> None:
    db.query(User).filter(User.id == partner_id, User.role == "PARTNER").update(
        {User.is_partner_available: True}, synchronize_session=False
    )
    db.flush()


def _try_assign_partner(db: Session, order: Order, vendor: Vendor | None) -> User | None:
    if order.partner_id:
        return None

    candidates, partner_by_id = _candidate_partners(db, vendor)
    for _dist, pid in candidates:
        if not _claim_partner_row(db, pid):
            continue

        # Re-check after claiming: if partner still has an active order, release and continue.
        if _partner_has_active_order(db, pid):
            _release_partner_row(db, pid)
            continue

        order.partner_id = pid
        _add_event(db, order, "ASSIGNED_TO_PARTNER", "Partner assigned", actor_user_id=None)
        return partner_by_id.get(pid) or db.query(User).filter(User.id == pid).first()

    return None


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
    assigned_partner = _try_assign_partner(db, order, v)

    db.commit()
    db.refresh(order)

    send_push(
        _user_tokens(db, order.customer_id),
        "Order accepted",
        f"Order #{order.id} accepted",
        data=build_order_notification_data(order.id, status="ACCEPTED_BY_SELLER", target_app="consumer"),
    )
    if assigned_partner:
        send_push(
            _user_tokens(db, assigned_partner.id),
            "New pickup",
            f"Order #{order.id} assigned",
            data=build_order_notification_data(order.id, status="ASSIGNED_TO_PARTNER", target_app="delivery"),
        )

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
    if partner_id:
        _maybe_mark_partner_available(db, partner_id)

    db.commit()
    db.refresh(order)

    send_push(
        _user_tokens(db, order.customer_id),
        "Order rejected",
        f"Order #{order.id} rejected",
        data=build_order_notification_data(order.id, status="REJECTED_BY_SELLER", target_app="consumer"),
    )
    if partner_id:
        send_push(
            _user_tokens(db, partner_id),
            "Order unassigned",
            f"Order #{order.id} was unassigned",
            data=build_order_notification_data(order.id, status="REJECTED_BY_SELLER", target_app="delivery"),
        )

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

    assigned_partner = _try_assign_partner(db, order, v)
    _add_event(db, order, "READY_FOR_PICKUP", "Ready for pickup", actor_user_id=user.id)

    db.commit()
    db.refresh(order)

    send_push(
        _user_tokens(db, order.customer_id),
        "Order ready",
        f"Order #{order.id} is ready",
        data=build_order_notification_data(order.id, status="READY_FOR_PICKUP", target_app="consumer"),
    )
    if order.partner_id:
        send_push(
            _user_tokens(db, order.partner_id),
            "Pickup ready",
            f"Order #{order.id} ready for pickup",
            data=build_order_notification_data(order.id, status="READY_FOR_PICKUP", target_app="delivery"),
        )
    if assigned_partner:
        send_push(
            _user_tokens(db, assigned_partner.id),
            "New pickup",
            f"Order #{order.id} assigned",
            data=build_order_notification_data(order.id, status="ASSIGNED_TO_PARTNER", target_app="delivery"),
        )

    return order