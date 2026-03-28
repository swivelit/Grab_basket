from __future__ import annotations

from collections import Counter

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session, joinedload

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import Order, User, Vendor

router = APIRouter(prefix="/personalization", tags=["personalization"], dependencies=[Depends(require_role("CUSTOMER"))])


def _normalize_service_hint(value: str | None) -> str:
    text = str(value or "").strip().lower()
    if not text:
        return "food"
    return text


@router.get("/reorder-intelligence")
def reorder_intelligence(
    limit: int = 5,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    safe_limit = max(1, min(int(limit), 20))
    orders = (
        db.query(Order)
        .options(joinedload(Order.items), joinedload(Order.vendor))
        .filter(Order.customer_id == user.id)
        .order_by(Order.created_at.desc())
        .limit(250)
        .all()
    )

    delivered = [order for order in orders if str(order.status or "").upper() == "DELIVERED"]

    vendor_counter: Counter[int] = Counter()
    product_counter: Counter[int] = Counter()
    service_counter: Counter[str] = Counter()

    for order in delivered:
        vendor_counter[int(order.vendor_id)] += 1
        service_counter[_normalize_service_hint(getattr(order, "order_source", "food"))] += 1
        for item in order.items or []:
            product_counter[int(item.product_id)] += int(item.qty or 1)

    top_vendor_ids = [vendor_id for vendor_id, _ in vendor_counter.most_common(safe_limit)]
    vendor_rows = db.query(Vendor).filter(Vendor.id.in_(top_vendor_ids)).all() if top_vendor_ids else []
    vendor_by_id = {int(v.id): v for v in vendor_rows}

    top_vendors = [
        {
            "vendor_id": vendor_id,
            "name": getattr(vendor_by_id.get(vendor_id), "name", ""),
            "order_count": count,
        }
        for vendor_id, count in vendor_counter.most_common(safe_limit)
    ]

    top_products = [
        {
            "product_id": product_id,
            "quantity_ordered": qty,
        }
        for product_id, qty in product_counter.most_common(safe_limit)
    ]

    return {
        "ok": True,
        "customer_id": user.id,
        "orders_considered": len(delivered),
        "top_vendors": top_vendors,
        "top_products": top_products,
        "service_preferences": [
            {"service": service, "orders": count}
            for service, count in service_counter.most_common(safe_limit)
        ],
    }
