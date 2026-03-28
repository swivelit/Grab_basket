from __future__ import annotations

from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException, Header
from sqlalchemy.orm import Session, joinedload

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
from ..utils.inventory import release_inventory_for_order
from ..notifications import build_order_notification_data, send_push

router = APIRouter(prefix="/orders", tags=["orders"])

# Partner order states that mean the partner is currently busy.
ACTIVE_PARTNER_ORDER_STATUSES = {"ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"}
ONLINE_PAYMENT_METHODS = {"UPI", "CARD"}
TERMINAL_ORDER_STATUSES = {"DELIVERED"}
NON_CANCELLABLE_ORDER_STATUSES = {"PICKED_UP", "DELIVERED"}


def add_event(db: Session, order: Order, status: str, note: str = "", actor_user_id: int | None = None) -> None:
    order.status = status
    db.add(OrderEvent(order_id=order.id, status=status, note=note, actor_user_id=actor_user_id))


def _seller_tokens(db: Session, vendor: Vendor) -> list[str]:
    if not vendor.seller_id:
        return []
    return [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == vendor.seller_id).all()]


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


def _normalize_payment_method(value: str | None) -> str:
    return str(value or "COD").strip().upper()


def _normalize_idempotency_key(value: str | None) -> str:
    return str(value or "").strip()[:128]


def _is_online_payment(payment_method: str) -> bool:
    return _normalize_payment_method(payment_method) in ONLINE_PAYMENT_METHODS


def _is_vendor_open_for_orders(vendor: Vendor) -> bool:
    if not bool(getattr(vendor, "is_open", True)):
        return False
    if not bool(getattr(vendor, "is_accepting_orders", True)):
        return False
    return True


def _line_item_signature(items: list[OrderItem]) -> list[tuple[int, int]]:
    signature: list[tuple[int, int]] = []
    for item in items:
        signature.append((int(item.product_id), int(item.qty)))
    return sorted(signature)


def _payload_item_signature(payload: OrderCreateIn) -> list[tuple[int, int]]:
    aggregate: dict[int, int] = {}
    for item in payload.items:
        aggregate[int(item.product_id)] = aggregate.get(int(item.product_id), 0) + int(item.qty)
    return sorted(aggregate.items())


def _find_existing_order_for_idempotency(db: Session, user_id: int, idempotency_key: str) -> Order | None:
    if not idempotency_key:
        return None

    return (
        db.query(Order)
        .options(joinedload(Order.items))
        .filter(Order.customer_id == user_id, Order.idempotency_key == idempotency_key)
        .order_by(Order.id.desc())
        .first()
    )


def _matches_idempotent_request(existing_order: Order, payload: OrderCreateIn) -> bool:
    if int(existing_order.vendor_id) != int(payload.vendor_id):
        return False

    if int(existing_order.delivery_address_id or 0) != int(payload.delivery_address_id or 0):
        return False

    if _normalize_payment_method(existing_order.payment_method) != _normalize_payment_method(payload.payment_method):
        return False

    return _line_item_signature(list(existing_order.items or [])) == _payload_item_signature(payload)


def _estimate_delivery_fee(distance_km: float | None) -> float:
    if distance_km is None:
        return 0.0
    return round(max(20.0, 10.0 + (distance_km * 5.0)), 2)


def _estimate_eta_minutes(vendor: Vendor, distance_km: float | None) -> int | None:
    prep_minutes = int(getattr(vendor, "estimated_delivery_time_min", 0) or getattr(vendor, "avg_prep_time_min", 0) or 0)
    if prep_minutes <= 0 and distance_km is None:
        return None

    travel_minutes = 0
    if distance_km is not None:
        # Conservative city-speed assumption, with a small handling buffer.
        travel_minutes = max(8, int(round(distance_km * 4.5)))

    total = prep_minutes + travel_minutes
    return total if total > 0 else None


@router.post("", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def create_order(
    payload: OrderCreateIn,
    x_idempotency_key: str | None = Header(default=None, alias="X-Idempotency-Key"),
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    idempotency_key = _normalize_idempotency_key(x_idempotency_key)
    if idempotency_key:
        existing_order = _find_existing_order_for_idempotency(db, user.id, idempotency_key)
        if existing_order:
            if not _matches_idempotent_request(existing_order, payload):
                raise HTTPException(status_code=409, detail="X-Idempotency-Key was already used for a different basket")
            return existing_order

    vendor = db.query(Vendor).filter(Vendor.id == payload.vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    if not _is_vendor_open_for_orders(vendor):
        raise HTTPException(status_code=400, detail="Store is not accepting orders right now")

    if not payload.items:
        raise HTTPException(status_code=400, detail="Basket cannot be empty")

    payment_method = _normalize_payment_method(payload.payment_method)
    if payment_method == "COD" and not bool(getattr(vendor, "accepts_cod", True)):
        raise HTTPException(status_code=400, detail="Cash on delivery is not available for this store")

    delivery_lat = None
    delivery_lng = None
    distance_km = None

    if payload.delivery_address_id is not None:
        addr = (
            db.query(CustomerAddress)
            .filter(CustomerAddress.id == payload.delivery_address_id, CustomerAddress.customer_id == user.id)
            .first()
        )
        if not addr:
            raise HTTPException(status_code=400, detail="Invalid address")

        delivery_lat, delivery_lng = addr.lat, addr.lng

        if vendor.lat is not None and vendor.lng is not None:
            distance_km = haversine_km(delivery_lat, delivery_lng, vendor.lat, vendor.lng)
            if distance_km > float(vendor.delivery_radius_km or 0):
                raise HTTPException(status_code=400, detail="Address outside delivery radius")

    requested_qty_by_product_id: dict[int, int] = {}
    for item in payload.items:
        requested_qty_by_product_id[int(item.product_id)] = requested_qty_by_product_id.get(int(item.product_id), 0) + int(item.qty)

    product_ids = list(requested_qty_by_product_id.keys())
    products = (
        db.query(Product)
        .filter(Product.vendor_id == vendor.id, Product.id.in_(product_ids))
        .all()
    )
    products_by_id = {int(product.id): product for product in products}

    missing_product_ids = sorted(set(product_ids) - set(products_by_id.keys()))
    if missing_product_ids:
        raise HTTPException(status_code=400, detail=f"Invalid product(s): {', '.join(str(item) for item in missing_product_ids)}")

    subtotal = 0.0
    tax_amount = 0.0
    order_items_to_create: list[OrderItem] = []

    for product_id, qty in sorted(requested_qty_by_product_id.items()):
        product = products_by_id[product_id]

        if not bool(product.is_available):
            raise HTTPException(status_code=400, detail=f"{product.name} is currently unavailable")

        max_qty_per_order = int(product.max_qty_per_order or 0)
        if max_qty_per_order > 0 and qty > max_qty_per_order:
            raise HTTPException(
                status_code=400,
                detail=f"{product.name} allows a maximum quantity of {max_qty_per_order} per order",
            )

        tracked_stock_qty = int(product.stock_qty or 0)
        if tracked_stock_qty > 0 and qty > tracked_stock_qty:
            raise HTTPException(
                status_code=400,
                detail=f"Only {tracked_stock_qty} unit(s) of {product.name} are left in stock",
            )

        unit_price = round(float(product.price or 0), 2)
        line_subtotal = round(unit_price * qty, 2)
        line_tax = round(line_subtotal * max(float(product.tax_rate_percent or 0), 0.0) / 100, 2)
        line_total = round(line_subtotal + line_tax, 2)

        subtotal += line_subtotal
        tax_amount += line_tax

        order_items_to_create.append(
            OrderItem(
                product_id=product.id,
                name_snapshot=product.name,
                price_snapshot=unit_price,
                image_snapshot=product.image_url or product.thumbnail_url or "",
                unit_snapshot=product.unit_label or "",
                sku_snapshot=product.sku or "",
                qty=qty,
                line_total_amount=line_total,
                variant_snapshot="",
            )
        )

    subtotal = round(subtotal, 2)
    tax_amount = round(tax_amount, 2)
    min_order_amount = round(float(getattr(vendor, "min_order_amount", 0) or 0), 2)
    if min_order_amount > 0 and subtotal < min_order_amount:
        raise HTTPException(
            status_code=400,
            detail=f"Minimum order amount for {vendor.name} is ₹{min_order_amount:.0f}",
        )

    packaging_fee = round(float(getattr(vendor, "packaging_fee", 0) or 0), 2)
    delivery_fee = _estimate_delivery_fee(distance_km)
    total_amount = round(subtotal + tax_amount + packaging_fee + delivery_fee, 2)

    is_online_payment = _is_online_payment(payment_method)
    payment_status = "PENDING_VERIFICATION" if is_online_payment else "PENDING"
    initial_status = "PAYMENT_PENDING" if is_online_payment else "CREATED"

    order = Order(
        vendor_id=vendor.id,
        customer_id=user.id,
        status=initial_status,
        delivery_address_id=payload.delivery_address_id,
        delivery_lat=delivery_lat,
        delivery_lng=delivery_lng,
        delivery_distance_km=round(distance_km, 2) if distance_km is not None else None,
        delivery_eta_minutes=_estimate_eta_minutes(vendor, distance_km),
        payment_method=payment_method,
        payment_status=payment_status,
        idempotency_key=idempotency_key or None,
        subtotal_amount=subtotal,
        delivery_fee=delivery_fee,
        packaging_fee=packaging_fee,
        tax_amount=tax_amount,
        discount_amount=0.0,
        total_amount=total_amount,
        payment_ref=f"PAY-{payment_method}-{int(datetime.now(timezone.utc).timestamp())}",
    )
    db.add(order)
    db.flush()

    for order_item in order_items_to_create:
        order_item.order_id = order.id
        db.add(order_item)

    add_event(
        db,
        order,
        initial_status,
        "Awaiting payment verification" if is_online_payment else "Order created",
        actor_user_id=user.id,
    )

    db.commit()
    db.refresh(order)

    if not is_online_payment:
        send_push(
            _seller_tokens(db, vendor),
            "New order",
            f"Order #{order.id} placed",
            data=build_order_notification_data(order.id, status="CREATED", target_app="partner"),
        )
    return order


@router.get("/me", response_model=list[OrderOut])
def my_orders(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if user.role == "CUSTOMER":
        return db.query(Order).filter(Order.customer_id == user.id).order_by(Order.id.desc()).all()
    if user.role == "SELLER":
        v = db.query(Vendor).filter(Vendor.seller_id == user.id).first()
        return [] if not v else db.query(Order).filter(Order.vendor_id == v.id).order_by(Order.id.desc()).all()
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


@router.post("/{order_id}/cancel", response_model=OrderOut, dependencies=[Depends(require_role("CUSTOMER"))])
def cancel_order(order_id: int, reason: str = "", db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id, Order.customer_id == user.id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    current_status = str(order.status or "").upper()
    if current_status in NON_CANCELLABLE_ORDER_STATUSES:
        raise HTTPException(status_code=400, detail="Order cannot be cancelled now")
    if current_status.startswith("CANCELLED"):
        return order
    if current_status in TERMINAL_ORDER_STATUSES:
        raise HTTPException(status_code=400, detail="Order is already closed")

    partner_id = order.partner_id
    order.partner_id = None
    order.cancelled_at = datetime.now(timezone.utc)
    order.cancellation_reason = (reason or "Cancelled by customer").strip()[:500]

    release_inventory_for_order(
        db,
        order,
        actor_user_id=user.id,
        note="Inventory released after customer cancellation",
    )

    if str(order.payment_status or "").upper() == "PAID" and str(order.refund_status or "").upper() == "NOT_APPLICABLE":
        order.refund_status = "PENDING"

    add_event(db, order, "CANCELLED_BY_CUSTOMER", order.cancellation_reason, actor_user_id=user.id)
    if partner_id:
        _maybe_mark_partner_available(db, partner_id)

    db.commit()
    db.refresh(order)

    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    if vendor:
        send_push(
            _seller_tokens(db, vendor),
            "Order cancelled",
            f"Order #{order.id} cancelled",
            data=build_order_notification_data(order.id, status="CANCELLED_BY_CUSTOMER", target_app="partner"),
        )
    if partner_id:
        ptokens = [t.token for t in db.query(FcmToken).filter(FcmToken.user_id == partner_id).all()]
        send_push(
            ptokens,
            "Order cancelled",
            f"Order #{order.id} cancelled",
            data=build_order_notification_data(order.id, status="CANCELLED_BY_CUSTOMER", target_app="delivery"),
        )

    return order


@router.get("/{order_id}/tracking", response_model=OrderTrackingOut)
def tracking(order_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

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