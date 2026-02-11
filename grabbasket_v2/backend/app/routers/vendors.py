from fastapi import APIRouter, Depends, Query
from sqlalchemy.orm import Session

from ..db import get_db
from ..geo import haversine_km
from ..models import Vendor, Product
from ..schemas import VendorOut, ProductOut

router = APIRouter(prefix="/vendors", tags=["vendors"])


@router.get("", response_model=list[VendorOut])
def list_vendors(
    db: Session = Depends(get_db),
    lat: float | None = Query(default=None),
    lng: float | None = Query(default=None),
):
    vendors = db.query(Vendor).order_by(Vendor.id.desc()).all()

    out: list[VendorOut] = []
    for v in vendors:
        # store open filter
        if not v.is_open:
            continue

        # radius filter if customer location provided and vendor has geo
        if lat is not None and lng is not None and v.lat is not None and v.lng is not None:
            dist = haversine_km(lat, lng, v.lat, v.lng)
            if dist > float(v.delivery_radius_km):
                continue

        out.append(
            VendorOut(
                id=v.id,
                name=v.name,
                description=v.description,
                address=v.address,
                lat=v.lat,
                lng=v.lng,
                delivery_radius_km=v.delivery_radius_km,
                is_open=v.is_open,
                open_time=v.open_time.isoformat(timespec="minutes") if v.open_time else None,
                close_time=v.close_time.isoformat(timespec="minutes") if v.close_time else None,
            )
        )
    return out


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def vendor_products(vendor_id: int, db: Session = Depends(get_db)):
    rows = db.query(Product).filter(Product.vendor_id == vendor_id, Product.is_available == True).order_by(Product.id.desc()).all()
    return [
        ProductOut(
            id=p.id,
            vendor_id=p.vendor_id,
            name=p.name,
            description=p.description,
            price=float(p.price),
            is_available=p.is_available,
        )
        for p in rows
    ]
