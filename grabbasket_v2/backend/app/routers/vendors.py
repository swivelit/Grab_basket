from datetime import datetime
from fastapi import APIRouter, Depends, Query, HTTPException
from sqlalchemy import or_
from sqlalchemy.orm import Session

from ..db import get_db
from ..models import Vendor, Product
from ..schemas import VendorOut, ProductOut
from ..utils.geo import haversine_km

router = APIRouter(prefix="/vendors", tags=["vendors"])


def _open_now(v: Vendor) -> bool:
    if not v.is_open:
        return False
    if v.open_time is None or v.close_time is None:
        return True
    now = datetime.now().time()
    if v.open_time <= v.close_time:
        return v.open_time <= now <= v.close_time
    # overnight store (e.g. 20:00 -> 02:00)
    return now >= v.open_time or now <= v.close_time


def _vendor_to_out(v: Vendor, lat: float | None, lng: float | None) -> VendorOut:
    vo = VendorOut.model_validate(v)
    vo.open_now = _open_now(v)

    if lat is not None and lng is not None and v.lat is not None and v.lng is not None:
        dist = haversine_km(lat, lng, v.lat, v.lng)
        vo.distance_km = dist
        vo.can_deliver = dist <= float(v.delivery_radius_km)

    return vo


@router.get("", response_model=list[VendorOut])
def list_vendors(
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None),
    lng: float | None = Query(default=None),
    q: str | None = Query(default=None, description="Search by vendor name/description"),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
):
    query = db.query(Vendor)
    if q:
        q_like = f"%{q.strip()}%"
        query = query.filter(or_(Vendor.name.ilike(q_like), Vendor.description.ilike(q_like)))

    # If distance sorting is requested, compute in-memory (MVP).
    if lat is not None and lng is not None:
        vendors = query.all()
        out = [_vendor_to_out(v, lat, lng) for v in vendors]
        out.sort(key=lambda x: (x.distance_km is None, x.distance_km or 10**9))
        return out[offset : offset + limit]

    vendors = query.order_by(Vendor.id.desc()).offset(offset).limit(limit).all()
    return [_vendor_to_out(v, None, None) for v in vendors]


@router.get("/nearby", response_model=list[VendorOut])
def nearby_vendors(
    lat: float = Query(...),
    lng: float = Query(...),
    db: Session = Depends(get_db),
    limit: int = Query(default=50, ge=1, le=200),
):
    vendors = db.query(Vendor).all()
    out: list[VendorOut] = []
    for v in vendors:
        if v.lat is None or v.lng is None:
            continue
        dist = haversine_km(lat, lng, v.lat, v.lng)
        if dist <= float(v.delivery_radius_km) and _open_now(v):
            vo = VendorOut.model_validate(v)
            vo.distance_km = dist
            vo.can_deliver = True
            vo.open_now = True
            out.append(vo)
    out.sort(key=lambda x: x.distance_km or 10**9)
    return out[:limit]


@router.get("/{vendor_id}", response_model=VendorOut)
def get_vendor(
    vendor_id: int,
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None),
    lng: float | None = Query(default=None),
):
    v = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")
    return _vendor_to_out(v, lat, lng)


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def vendor_products(
    vendor_id: int,
    db: Session = Depends(get_db),
    q: str | None = Query(default=None, description="Search products by name/description"),
    limit: int = Query(default=100, ge=1, le=500),
    offset: int = Query(default=0, ge=0),
):
    query = (
        db.query(Product)
        .filter(Product.vendor_id == vendor_id)
        .filter(Product.is_available == True)  # noqa
    )
    if q:
        q_like = f"%{q.strip()}%"
        query = query.filter(or_(Product.name.ilike(q_like), Product.description.ilike(q_like)))

    return query.order_by(Product.id.desc()).offset(offset).limit(limit).all()
