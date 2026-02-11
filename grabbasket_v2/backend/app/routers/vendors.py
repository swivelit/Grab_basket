from datetime import datetime
from fastapi import APIRouter, Depends, Query
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


@router.get("", response_model=list[VendorOut])
def list_vendors(
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None),
    lng: float | None = Query(default=None),
):
    vendors = db.query(Vendor).all()
    out: list[VendorOut] = []

    for v in vendors:
        vo = VendorOut.model_validate(v)
        vo.open_now = _open_now(v)

        if lat is not None and lng is not None and v.lat is not None and v.lng is not None:
            dist = haversine_km(lat, lng, v.lat, v.lng)
            vo.distance_km = dist
            vo.can_deliver = dist <= float(v.delivery_radius_km)
        out.append(vo)

    # Sort by distance if computed
    if lat is not None and lng is not None:
        out.sort(key=lambda x: (x.distance_km is None, x.distance_km or 10**9))
    return out


@router.get("/nearby", response_model=list[VendorOut])
def nearby_vendors(
    lat: float = Query(...),
    lng: float = Query(...),
    db: Session = Depends(get_db),
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
    return out


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def vendor_products(vendor_id: int, db: Session = Depends(get_db)):
    products = (
        db.query(Product)
        .filter(Product.vendor_id == vendor_id)
        .filter(Product.is_available == True)  # noqa
        .all()
    )
    return products
