from __future__ import annotations

import re
from datetime import datetime
from typing import Optional

from fastapi import APIRouter, Depends, HTTPException, Query
from sqlalchemy import or_
from sqlalchemy.orm import Session

from ..db import get_db
from ..models import Product, Vendor
from ..schemas import ProductOut, VendorOut
from ..utils.geo import haversine_km

router = APIRouter(prefix="/vendors", tags=["vendors"])

MAX_VENDOR_SCAN = 500
MAX_PRODUCT_SCAN = 1000
SERVICE_ALIASES = {
    "instamart": "warehouse",
    "grocery": "warehouse",
    "groceries": "warehouse",
    "dineout": "eatout",
    "dining": "eatout",
    "events": "scenes",
    "experience": "scenes",
}
SERVICE_MATCHERS: dict[str, tuple[re.Pattern[str], ...]] = {
    "warehouse": (
        re.compile(r"\b(grocery|groceries|mart|basket|essentials?|daily|fruit|vegetable|veggies|greens|dairy|milk|bread|eggs?|snacks?|beverages?|beauty|personal care|pharmacy)\b", re.I),
    ),
    "eatout": (
        re.compile(r"\b(dineout|dine\s?in|dining out|table|reserve|reservation|buffet|brunch|rooftop|fine dining|bill offer|book a table)\b", re.I),
    ),
    "scenes": (
        re.compile(r"\b(scene|scenes|event|events|experience|experiences|show|shows|music|comedy|workshop|ticket|tickets|entry|gig|performance|festival|nightlife)\b", re.I),
    ),
    "food": (
        re.compile(r"\b(food|restaurant|restaurants|kitchen|meal|meals|biryani|pizza|burger|burgers|fries|dosa|thali|cafe|bakery|dessert|desserts|chicken|paneer|naan|curry)\b", re.I),
    ),
}


def _clean_text(value: object) -> str:
    return str(value or "").strip()


def _clean_lower(value: object) -> str:
    return _clean_text(value).lower()


def _normalize_service(value: object) -> str:
    normalized = _clean_lower(value)
    if not normalized or normalized == "all":
        return ""
    return SERVICE_ALIASES.get(normalized, normalized)


def _vendor_service_haystack(vendor: Vendor) -> str:
    return " ".join(
        [
            _clean_text(getattr(vendor, "name", "")),
            _clean_text(getattr(vendor, "description", "")),
            _clean_text(getattr(vendor, "cuisine_tags", "")),
            _clean_text(getattr(vendor, "slug", "")),
        ]
    )


def _vendor_matches_service(vendor: Vendor, service: str) -> bool:
    normalized_service = _normalize_service(service)
    if not normalized_service:
        return True

    haystack = _vendor_service_haystack(vendor)

    if normalized_service in {"warehouse", "eatout", "scenes"}:
        return any(pattern.search(haystack) for pattern in SERVICE_MATCHERS[normalized_service])

    # Food is the default consumer vertical. Keep food broad enough to include regular restaurants,
    # but exclude records that clearly belong only to other verticals.
    if normalized_service == "food":
        if any(pattern.search(haystack) for pattern in SERVICE_MATCHERS["food"]):
            return True

        clearly_other_vertical = any(
            pattern.search(haystack)
            for other_service in ("warehouse", "eatout", "scenes")
            for pattern in SERVICE_MATCHERS[other_service]
        )
        return not clearly_other_vertical

    return True


def _open_now(vendor: Vendor) -> bool:
    """
    Operationally open for ordering right now.
    This is stricter than only checking business hours.
    """
    if not bool(vendor.is_open):
        return False
    if not bool(getattr(vendor, "is_accepting_orders", True)):
        return False

    open_time = getattr(vendor, "open_time", None)
    close_time = getattr(vendor, "close_time", None)

    if open_time is None or close_time is None:
        return True

    now = datetime.now().time()
    if open_time <= close_time:
        return open_time <= now <= close_time

    # Overnight range, e.g. 18:00 -> 02:00
    return now >= open_time or now <= close_time


def _vendor_distance_km(vendor: Vendor, lat: float | None, lng: float | None) -> float | None:
    if lat is None or lng is None:
        return None
    if vendor.lat is None or vendor.lng is None:
        return None
    return haversine_km(lat, lng, vendor.lat, vendor.lng)


def _annotate_vendor(vendor: Vendor, lat: float | None, lng: float | None) -> VendorOut:
    out = VendorOut.model_validate(vendor)
    distance_km = _vendor_distance_km(vendor, lat, lng)

    out.open_now = _open_now(vendor)
    out.distance_km = distance_km

    if distance_km is not None:
        try:
            out.can_deliver = distance_km <= float(vendor.delivery_radius_km or 0)
        except Exception:
            out.can_deliver = False
    else:
        out.can_deliver = None

    return out


def _vendor_matches_category(vendor: Vendor, category: str) -> bool:
    if not category:
        return True

    category_norm = _clean_lower(category)
    haystacks = [
        _clean_lower(getattr(vendor, "cuisine_tags", "")),
        _clean_lower(getattr(vendor, "description", "")),
        _clean_lower(getattr(vendor, "name", "")),
    ]
    return any(category_norm in hay for hay in haystacks)


def _sort_vendor_rows(
    rows: list[VendorOut],
    *,
    sort_by: str,
) -> list[VendorOut]:
    mode = _clean_lower(sort_by) or "recommended"

    if mode == "distance":
        return sorted(rows, key=lambda row: (row.distance_km is None, row.distance_km or 10**9, row.id))

    if mode == "rating":
        return sorted(
            rows,
            key=lambda row: (-(row.avg_rating or 0.0), -(row.total_ratings or 0), row.id),
        )

    if mode in {"delivery_time", "eta"}:
        return sorted(
            rows,
            key=lambda row: (
                row.estimated_delivery_time_min is None,
                row.estimated_delivery_time_min or 10**9,
                row.distance_km is None,
                row.distance_km or 10**9,
                row.id,
            ),
        )

    if mode == "a_z":
        return sorted(rows, key=lambda row: (_clean_lower(row.name), row.id))

    if mode == "newest":
        return sorted(
            rows,
            key=lambda row: (
                -(int(row.created_at.timestamp()) if row.created_at else 0),
                row.id,
            ),
        )

    # recommended / default:
    # 1) accepting + open now
    # 2) deliverable when location is known
    # 3) higher rating + more ratings
    # 4) faster ETA
    # 5) nearer distance
    # 6) richer media/catalog signals
    def score(row: VendorOut) -> tuple:
        media_signal = 0
        if _clean_text(getattr(row, "logo_image_url", "")):
            media_signal += 1
        if _clean_text(getattr(row, "cover_image_url", "")):
            media_signal += 1
        if _clean_text(getattr(row, "banner_image_url", "")):
            media_signal += 1

        return (
            0 if row.open_now else 1,
            0 if row.can_deliver is True else 1,
            -(row.avg_rating or 0.0),
            -(row.total_ratings or 0),
            row.estimated_delivery_time_min or 10**9,
            row.distance_km if row.distance_km is not None else 10**9,
            -media_signal,
            row.id,
        )

    return sorted(rows, key=score)


def _sort_product_rows(
    rows: list[Product],
    *,
    sort_by: str,
) -> list[Product]:
    mode = _clean_lower(sort_by) or "recommended"

    if mode == "price_asc":
        return sorted(rows, key=lambda row: (float(row.price or 0), row.id))

    if mode == "price_desc":
        return sorted(rows, key=lambda row: (-float(row.price or 0), row.id))

    if mode == "rating":
        return sorted(
            rows,
            key=lambda row: (-(row.avg_rating or 0.0), -(row.total_ratings or 0), row.id),
        )

    if mode == "newest":
        return sorted(
            rows,
            key=lambda row: (
                -(int(row.created_at.timestamp()) if row.created_at else 0),
                row.id,
            ),
        )

    if mode == "a_z":
        return sorted(rows, key=lambda row: (_clean_lower(row.name), row.id))

    # recommended / default
    return sorted(
        rows,
        key=lambda row: (
            0 if bool(row.is_available) else 1,
            0 if bool(getattr(row, "is_featured", False)) else 1,
            getattr(row, "sort_order", 0),
            -(row.avg_rating or 0.0),
            -(row.total_ratings or 0),
            _clean_lower(row.name),
            row.id,
        ),
    )


@router.get("", response_model=list[VendorOut])
def list_vendors(
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None, ge=-90, le=90),
    lng: float | None = Query(default=None, ge=-180, le=180),
    q: str | None = Query(default=None, max_length=200),
    category: str | None = Query(default=None, max_length=120),
    service: str | None = Query(default=None, max_length=40),
    open_only: bool = Query(default=False),
    deliverable_only: bool = Query(default=False),
    min_rating: float | None = Query(default=None, ge=0, le=5),
    sort_by: str = Query(default="recommended", max_length=40),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
):
    query = db.query(Vendor)

    search = _clean_text(q)
    if search:
        like = f"%{search}%"
        query = query.filter(
            or_(
                Vendor.name.ilike(like),
                Vendor.description.ilike(like),
                Vendor.address.ilike(like),
                Vendor.cuisine_tags.ilike(like),
                Vendor.slug.ilike(like),
            )
        )

    if open_only:
        query = query.filter(Vendor.is_open == True)  # noqa: E712
        query = query.filter(Vendor.is_accepting_orders == True)  # noqa: E712

    if min_rating is not None:
        query = query.filter(Vendor.avg_rating >= float(min_rating))

    # Fetch a larger candidate pool first because we do post-processing
    # for geospatial filters, vertical matching, open-now checks, and sorting.
    scan_limit = min(MAX_VENDOR_SCAN, max(limit + offset + 50, limit * 3))
    vendors = query.order_by(Vendor.id.desc()).limit(scan_limit).all()

    rows: list[VendorOut] = []
    normalized_service = _normalize_service(service)

    for vendor in vendors:
        if normalized_service and not _vendor_matches_service(vendor, normalized_service):
            continue

        if not _vendor_matches_category(vendor, _clean_text(category)):
            continue

        row = _annotate_vendor(vendor, lat, lng)

        if open_only and not row.open_now:
            continue

        if deliverable_only:
            if lat is None or lng is None:
                continue
            if row.can_deliver is not True:
                continue

        rows.append(row)

    rows = _sort_vendor_rows(rows, sort_by=sort_by)
    return rows[offset : offset + limit]


@router.get("/{vendor_id}", response_model=VendorOut)
def get_vendor(
    vendor_id: int,
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None, ge=-90, le=90),
    lng: float | None = Query(default=None, ge=-180, le=180),
):
    vendor = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")
    return _annotate_vendor(vendor, lat, lng)


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def vendor_products(
    vendor_id: int,
    db: Session = Depends(get_db),
    q: str | None = Query(default=None, max_length=200),
    category: str | None = Query(default=None, max_length=120),
    include_unavailable: bool = Query(default=False),
    featured_only: bool = Query(default=False),
    sort_by: str = Query(default="recommended", max_length=40),
    limit: int = Query(default=200, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
):
    vendor = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    query = db.query(Product).filter(Product.vendor_id == vendor_id)

    if not include_unavailable:
        query = query.filter(Product.is_available == True)  # noqa: E712

    if featured_only:
        query = query.filter(Product.is_featured == True)  # noqa: E712

    search = _clean_text(q)
    if search:
        like = f"%{search}%"
        query = query.filter(
            or_(
                Product.name.ilike(like),
                Product.description.ilike(like),
                Product.category.ilike(like),
                Product.subcategory.ilike(like),
                Product.badge_text.ilike(like),
                Product.sku.ilike(like),
                Product.barcode.ilike(like),
            )
        )

    category_value = _clean_text(category)
    if category_value:
        like = f"%{category_value}%"
        query = query.filter(
            or_(
                Product.category.ilike(like),
                Product.subcategory.ilike(like),
            )
        )

    scan_limit = min(MAX_PRODUCT_SCAN, max(limit + offset + 50, limit * 3))
    products = query.limit(scan_limit).all()
    products = _sort_product_rows(products, sort_by=sort_by)

    return products[offset : offset + limit]


@router.get("/{vendor_id}/categories", response_model=list[str])
def vendor_categories(
    vendor_id: int,
    db: Session = Depends(get_db),
    include_unavailable: bool = Query(default=False),
):
    vendor = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")

    query = db.query(Product).filter(Product.vendor_id == vendor_id)
    if not include_unavailable:
        query = query.filter(Product.is_available == True)  # noqa: E712

    rows = query.all()
    seen: set[str] = set()
    output: list[str] = []

    for product in rows:
        for raw in [getattr(product, "category", ""), getattr(product, "subcategory", "")]:
            value = _clean_text(raw)
            key = value.lower()
            if not value or key in seen:
                continue
            seen.add(key)
            output.append(value)

    output.sort(key=lambda value: value.lower())
    return output