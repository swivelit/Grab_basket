from __future__ import annotations

from datetime import datetime, timedelta

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import func
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, Vendor, Product, Order, OrderEvent, FcmToken, PartnerLocation
from ..schemas import VendorUpdateIn, ProductCreateIn, ProductUpdateIn, ProductOut, OrderOut
from ..notifications import build_order_notification_data, send_push
from ..utils.geo import haversine_km
from ..utils.inventory import (
    InventoryReservationError,
    release_inventory_for_order,
    reserve_inventory_for_order,
)

router = APIRouter(prefix="/seller", tags=["seller"])

# Partner order states that mean the partner is currently busy.
ACTIVE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}
STACKABLE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP"}
KITCHEN_LOAD_ORDER_STATUSES = {"CREATED", "ACCEPTED_BY_SELLER", "ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}

# Dispatch control defaults (kept in code until moved to admin-configurable settings).
PARTNER_ASSIGNMENT_ACCEPTANCE_TIMEOUT_MIN = 6
MAX_PARTNER_CAPACITY_DEFAULT = 2
MAX_PARTNER_CAPACITY_SURGE = 3
SURGE_OPEN_ORDER_THRESHOLD = 18
KITCHEN_OVERLOAD_THRESHOLD = 10
STACKING_DISTANCE_KM = 2.5
ZONE_GRID_DEGREES = 0.025  # ~2.7 km at the equator; good enough for zone bucketing in MVP.
STALE_LOCATION_MINUTES = 15


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


def _partner_active_order_count(db: Session, partner_id: int) -> int:
    return int(
        db.query(func.count(Order.id))
        .filter(Order.partner_id == partner_id)
        .filter(Order.status.in_(ACTIVE_PARTNER_ORDER_STATUSES))
        .scalar()
        or 0
    )


def _vendor_open_order_count(db: Session, vendor_id: int) -> int:
    return int(
        db.query(func.count(Order.id))
        .filter(Order.vendor_id == vendor_id)
        .filter(Order.status.in_(KITCHEN_LOAD_ORDER_STATUSES))
        .scalar()
        or 0
    )


def _is_sla_breach_risk(order: Order) -> bool:
    if order.delivery_eta_minutes is None:
        return False
    age_minutes = (datetime.utcnow() - order.created_at).total_seconds() / 60
    return age_minutes >= max(15, int(order.delivery_eta_minutes) - 5)


def _is_surge_active(db: Session) -> bool:
    citywide_open_orders = int(
        db.query(func.count(Order.id))
        .filter(Order.status.in_(KITCHEN_LOAD_ORDER_STATUSES))
        .scalar()
        or 0
    )
    return citywide_open_orders >= SURGE_OPEN_ORDER_THRESHOLD


def _zone_bucket(lat: float | None, lng: float | None) -> tuple[int, int] | None:
    if lat is None or lng is None:
        return None
    return (int(lat / ZONE_GRID_DEGREES), int(lng / ZONE_GRID_DEGREES))


def _within_timeout(order: Order, timeout_minutes: int) -> bool:
    if order.assigned_at is None:
        return True
    return datetime.utcnow() - order.assigned_at <= timedelta(minutes=timeout_minutes)


def _assignment_is_stale(order: Order) -> bool:
    if order.status != "ASSIGNED_TO_PARTNER":
        return False
    if order.assigned_at is None:
        return False
    return not _within_timeout(order, PARTNER_ASSIGNMENT_ACCEPTANCE_TIMEOUT_MIN)


def _order_can_be_stacked(target_order: Order, candidate_order: Order, vendor: Vendor | None) -> bool:
    if candidate_order.status not in STACKABLE_PARTNER_ORDER_STATUSES:
        return False
    if candidate_order.delivery_lat is None or candidate_order.delivery_lng is None:
        return False
    if target_order.delivery_lat is None or target_order.delivery_lng is None:
        return False

    # Prefer same vendor and nearby drop zones for stacking.
    if vendor and candidate_order.vendor_id != vendor.id:
        return False

    dist = haversine_km(
        target_order.delivery_lat,
        target_order.delivery_lng,
        candidate_order.delivery_lat,
        candidate_order.delivery_lng,
    )
    return dist <= STACKING_DISTANCE_KM


def _partner_stackable_order_count(db: Session, partner_id: int, order: Order, vendor: Vendor | None) -> int:
    candidate_orders = (
        db.query(Order)
        .filter(Order.partner_id == partner_id)
        .filter(Order.status.in_(STACKABLE_PARTNER_ORDER_STATUSES))
        .all()
    )
    count = 0
    for candidate_order in candidate_orders:
        if _order_can_be_stacked(order, candidate_order, vendor):
            count += 1
    return count


def _partner_capacity_limit(surge_active: bool) -> int:
    return MAX_PARTNER_CAPACITY_SURGE if surge_active else MAX_PARTNER_CAPACITY_DEFAULT


def _partner_can_take_more(db: Session, partner_id: int, surge_active: bool) -> bool:
    return _partner_active_order_count(db, partner_id) < _partner_capacity_limit(surge_active)


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


def _partner_latest_loc_is_stale(loc: PartnerLocation | None) -> bool:
    if not loc:
        return True
    return datetime.utcnow() - loc.created_at > timedelta(minutes=STALE_LOCATION_MINUTES)


def _partner_score_for_order(
    db: Session,
    order: Order,
    vendor: Vendor | None,
    partner_id: int,
    distance_to_vendor_km: float | None,
    loc: PartnerLocation | None,
    surge_active: bool,
    partner_order_count: int,
) -> tuple[float, float, int]:
    # Lower score is better.
    zone_bonus = 0.0
    stack_bonus = 0.0
    stale_loc_penalty = 3.0 if _partner_latest_loc_is_stale(loc) else 0.0
    load_penalty = float(partner_order_count) * (0.75 if surge_active else 1.0)
    prep_weight = max(0.5, min(3.0, float(getattr(vendor, "avg_prep_time_min", 15) or 15) / 15.0))

    if (
        vendor
        and vendor.lat is not None
        and vendor.lng is not None
        and loc
    ):
        vendor_zone = _zone_bucket(vendor.lat, vendor.lng)
        partner_zone = _zone_bucket(loc.lat, loc.lng)
        if vendor_zone is not None and vendor_zone == partner_zone:
            zone_bonus = -1.0

    stackable_count = _partner_stackable_order_count(db, partner_id, order, vendor)
    if stackable_count > 0:
        stack_bonus = -min(1.5, 0.75 * float(stackable_count))

    base_distance = distance_to_vendor_km if distance_to_vendor_km is not None else 12.0
    weighted_distance = base_distance * prep_weight
    total_score = weighted_distance + load_penalty + stale_loc_penalty + zone_bonus + stack_bonus
    return (round(total_score, 4), round(base_distance, 4), partner_id)


def _candidate_partners(
    db: Session,
    order: Order,
    vendor: Vendor | None,
) -> tuple[list[tuple[float, float, int]], dict[int, User]]:
    surge_active = _is_surge_active(db)
    partners = (
        db.query(User)
        .filter(User.role == "PARTNER")
        .all()
    )
    if not partners:
        return [], {}

    partner_by_id = {p.id: p for p in partners}
    loc_map = _latest_partner_locations(db, list(partner_by_id.keys()))

    vendor_lat = getattr(vendor, "lat", None)
    vendor_lng = getattr(vendor, "lng", None)

    candidates: list[tuple[float, float, int]] = []
    for pid, p in partner_by_id.items():
        active_order_count = _partner_active_order_count(db, pid)
        if not _partner_can_take_more(db, pid, surge_active):
            continue
        if not p.is_partner_available and active_order_count == 0:
            # Respect manual offline state but still allow available stacked partners.
            continue

        dist: float | None = None
        loc = loc_map.get(pid)
        if vendor_lat is not None and vendor_lng is not None:
            if loc:
                dist = haversine_km(loc.lat, loc.lng, vendor_lat, vendor_lng)

        candidates.append(
            _partner_score_for_order(
                db=db,
                order=order,
                vendor=vendor,
                partner_id=pid,
                distance_to_vendor_km=dist,
                loc=loc,
                surge_active=surge_active,
                partner_order_count=active_order_count,
            )
        )

    # Prefer best score, then shortest distance, then stable id.
    candidates.sort(key=lambda x: (x[0], x[1], x[2]))
    return candidates, partner_by_id


def _claim_partner_row(db: Session, partner_id: int) -> bool:
    """Atomically claim a partner for assignment.

    For stacked batches, partner may already be marked unavailable;
    we still claim when row exists and role matches.
    """
    updated = (
        db.query(User)
        .filter(User.id == partner_id)
        .filter(User.role == "PARTNER")
        .update({User.is_partner_available: False}, synchronize_session=False)
    )
    db.flush()
    return updated == 1


def _release_partner_row(db: Session, partner_id: int) -> None:
    db.query(User).filter(User.id == partner_id, User.role == "PARTNER").update(
        {User.is_partner_available: True}, synchronize_session=False
    )
    db.flush()


def _clear_stale_assignments_for_vendor(db: Session, vendor_id: int) -> None:
    stale_orders = (
        db.query(Order)
        .filter(Order.vendor_id == vendor_id)
        .filter(Order.status == "ASSIGNED_TO_PARTNER")
        .filter(Order.assigned_at.isnot(None))
        .all()
    )
    for stale_order in stale_orders:
        if not _assignment_is_stale(stale_order):
            continue
        stale_partner_id = stale_order.partner_id
        stale_order.partner_id = None
        stale_order.assigned_at = None
        _add_event(
            db,
            stale_order,
            "ACCEPTED_BY_SELLER",
            "Partner acceptance timeout, auto reassigning",
            actor_user_id=None,
        )
        if stale_partner_id:
            _maybe_mark_partner_available(db, stale_partner_id)


def _try_assign_partner(db: Session, order: Order, vendor: Vendor | None) -> User | None:
    if order.partner_id:
        return None

    if vendor:
        _clear_stale_assignments_for_vendor(db, vendor.id)

    candidates, partner_by_id = _candidate_partners(db, order, vendor)
    for _score, _dist, pid in candidates:
        if not _claim_partner_row(db, pid):
            continue

        if not _partner_can_take_more(db, pid, _is_surge_active(db)):
            _release_partner_row(db, pid)
            continue

        order.partner_id = pid
        order.assigned_at = datetime.utcnow()
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

    active_store_orders = _vendor_open_order_count(db, v.id)
    if active_store_orders >= KITCHEN_OVERLOAD_THRESHOLD:
        raise HTTPException(
            status_code=429,
            detail="Kitchen overloaded. Store throttling active; please clear pending orders before accepting new ones.",
        )

    try:
        reserve_inventory_for_order(
            db,
            order,
            actor_user_id=user.id,
            note="Inventory reserved on seller acceptance",
        )
    except InventoryReservationError as error:
        db.rollback()
        raise HTTPException(status_code=409, detail=str(error)) from error

    order.accepted_at = datetime.utcnow()
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

    release_inventory_for_order(
        db,
        order,
        actor_user_id=user.id,
        note="Inventory released after seller rejection",
    )
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
    if _is_sla_breach_risk(order):
        _add_event(
            db,
            order,
            order.status,
            "SLA breach risk detected; dispatch prioritized for nearest available rider",
            actor_user_id=None,
        )
    order.ready_for_pickup_at = datetime.utcnow()
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
