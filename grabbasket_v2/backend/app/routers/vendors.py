from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import Vendor, Product
from ..schemas import VendorOut, ProductOut


router = APIRouter(prefix="/vendors", tags=["vendors"])


@router.get("", response_model=list[VendorOut])
def list_vendors(db: Session = Depends(get_db)):
    return db.query(Vendor).order_by(Vendor.id.desc()).all()


@router.get("/{vendor_id}/products", response_model=list[ProductOut])
def list_products(vendor_id: int, db: Session = Depends(get_db)):
    vendor = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not vendor:
        raise HTTPException(status_code=404, detail="Vendor not found")
    return (
        db.query(Product)
        .filter(Product.vendor_id == vendor_id)
        .order_by(Product.id.desc())
        .all()
    )
