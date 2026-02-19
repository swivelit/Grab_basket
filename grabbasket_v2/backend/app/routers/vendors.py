from __future__ import annotations

from datetime import datetime

from fastapi import APIRouter, Depends, HTTPException, Query
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
    return now >= v.open_time or now <= v.close_time


def _annotate_vendor(v: Vendor, lat: float | None, lng: float | None) -> VendorOut:
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
    q: str | None = Query(default=None),
    open_only: bool = Query(default=False),
    deliverable_only: bool = Query(default=False),
    limit: int = Query(default=50, ge=1, le=200),
    offset: int = Query(default=0, ge=0),
):
    query = db.query(Vendor)
    if q:
        s = f"%{q.strip()}%"
        query = query.filter(or_(Vendor.name.ilike(s), Vendor.description.ilike(s), Vendor.address.ilike(s)))

    vendors = query.order_by(Vendor.id.desc()).offset(offset).limit(limit).all()

    out: list[VendorOut] = []
    for v in vendors:
        vo = _annotate_vendor(v, lat, lng)
        if open_only and not vo.open_now:
            continue
        if deliverable_only and (lat is None or lng is None or vo.can_deliver is not True):
            continue
        out.append(vo)

    if lat is not None and lng is not None:
        out.sort(key=lambda x: (x.distance_km is None, x.distance_km or 10**9))
    return out


@router.get("/{vendor_id}", response_model=VendorOut)
def get_vendor(vendor_id: int, db: Session = Depends(get_db), lat: float | None = None, lng: float | None = None):
    v = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not v:
        raise HTTPException(status_code=404, detail="Vendor not found")
    return _annotate_vendor(v, lat, lng)


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def vendor_products(
    vendor_id: int,
    db: Session = Depends(get_db),
    q: str | None = Query(default=None),
    include_unavailable: bool = Query(default=False),
    limit: int = Query(default=200, ge=1, le=500),
):
    query = db.query(Product).filter(Product.vendor_id == vendor_id)
    if not include_unavailable:
        query = query.filter(Product.is_available == True)  # noqa
    if q:
        s = f"%{q.strip()}%"
        query = query.filter(or_(Product.name.ilike(s), Product.description.ilike(s)))
    return query.order_by(Product.id.desc()).limit(limit).all()
