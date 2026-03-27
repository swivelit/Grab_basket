from __future__ import annotations

import json

from sqlalchemy.orm import Session

from ..models import Order, OrderEvent, Product

INVENTORY_RESERVED_EVENT = "INVENTORY_RESERVED"
INVENTORY_RELEASED_EVENT = "INVENTORY_RELEASED"


class InventoryReservationError(Exception):
    pass


def _safe_json_loads(raw: str | None) -> dict:
    try:
        parsed = json.loads(raw or "{}")
        return parsed if isinstance(parsed, dict) else {}
    except Exception:
        return {}


def _normalize_items(items: list[dict] | None) -> list[dict[str, int]]:
    aggregated: dict[int, int] = {}
    for item in items or []:
        product_id = int(item.get("product_id") or 0)
        qty = int(item.get("qty") or 0)
        if product_id <= 0 or qty <= 0:
            continue
        aggregated[product_id] = aggregated.get(product_id, 0) + qty

    return [
        {"product_id": product_id, "qty": qty}
        for product_id, qty in sorted(aggregated.items())
    ]


def _order_items_payload(order: Order) -> list[dict[str, int]]:
    return _normalize_items(
        [
            {"product_id": int(item.product_id), "qty": int(item.qty)}
            for item in list(order.items or [])
        ]
    )


def get_active_inventory_reservation(order: Order) -> dict | None:
    active: dict | None = None

    ordered_events = sorted(
        list(order.events or []),
        key=lambda event: (event.created_at, event.id),
    )

    for event in ordered_events:
        if event.status == INVENTORY_RESERVED_EVENT:
            metadata = _safe_json_loads(event.metadata_json)
            active = {"items": _normalize_items(metadata.get("items"))}
        elif event.status == INVENTORY_RELEASED_EVENT:
            active = None

    return active


def is_inventory_reserved(order: Order) -> bool:
    return get_active_inventory_reservation(order) is not None


def reserve_inventory_for_order(
    db: Session,
    order: Order,
    *,
    actor_user_id: int | None = None,
    note: str = "Inventory reserved",
) -> bool:
    if get_active_inventory_reservation(order) is not None:
        return False

    reserved_items: list[dict[str, int]] = []
    for item in _order_items_payload(order):
        product_id = int(item["product_id"])
        qty = int(item["qty"])

        product = (
            db.query(Product)
            .filter(Product.id == product_id, Product.vendor_id == order.vendor_id)
            .first()
        )
        if not product:
            raise InventoryReservationError("One of the ordered products no longer exists")

        tracked_stock_qty = int(product.stock_qty or 0)
        if tracked_stock_qty <= 0:
            continue

        updated = (
            db.query(Product)
            .filter(Product.id == product_id, Product.vendor_id == order.vendor_id)
            .filter(Product.stock_qty >= qty)
            .update({Product.stock_qty: Product.stock_qty - qty}, synchronize_session=False)
        )
        db.flush()

        if updated != 1:
            latest_product = (
                db.query(Product)
                .filter(Product.id == product_id, Product.vendor_id == order.vendor_id)
                .first()
            )
            remaining = max(int(getattr(latest_product, "stock_qty", 0) or 0), 0)
            product_name = str(getattr(product, "name", "This item") or "This item").strip() or "This item"
            raise InventoryReservationError(
                f"Only {remaining} unit(s) of {product_name} are left in stock"
            )

        refreshed_product = (
            db.query(Product)
            .filter(Product.id == product_id, Product.vendor_id == order.vendor_id)
            .first()
        )
        if refreshed_product:
            refreshed_product.stock_qty = max(int(refreshed_product.stock_qty or 0), 0)
            if refreshed_product.stock_qty <= 0:
                refreshed_product.is_available = False

        reserved_items.append({"product_id": product_id, "qty": qty})

    db.add(
        OrderEvent(
            order_id=order.id,
            status=INVENTORY_RESERVED_EVENT,
            note=note,
            actor_user_id=actor_user_id,
            metadata_json=json.dumps({"items": reserved_items}, separators=(",", ":")),
        )
    )
    return True


def release_inventory_for_order(
    db: Session,
    order: Order,
    *,
    actor_user_id: int | None = None,
    note: str = "Inventory released",
) -> bool:
    active_reservation = get_active_inventory_reservation(order)
    if active_reservation is None:
        return False

    reserved_items = _normalize_items(active_reservation.get("items"))
    for item in reserved_items:
        product_id = int(item["product_id"])
        qty = int(item["qty"])

        product = (
            db.query(Product)
            .filter(Product.id == product_id, Product.vendor_id == order.vendor_id)
            .first()
        )
        if not product:
            continue

        product.stock_qty = max(int(product.stock_qty or 0), 0) + qty
        if product.stock_qty > 0:
            product.is_available = True

    db.add(
        OrderEvent(
            order_id=order.id,
            status=INVENTORY_RELEASED_EVENT,
            note=note,
            actor_user_id=actor_user_id,
            metadata_json=json.dumps({"items": reserved_items}, separators=(",", ":")),
        )
    )
    return True