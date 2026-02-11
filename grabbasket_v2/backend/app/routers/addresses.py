from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..db import get_db
from ..deps import get_current_user
from ..models import CustomerAddress
from ..schemas import AddressIn, AddressOut

router = APIRouter(prefix="/addresses", tags=["addresses"])


@router.get("", response_model=list[AddressOut])
def list_addresses(db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "CUSTOMER":
        raise HTTPException(403, "Only CUSTOMER can manage addresses")
    rows = db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).order_by(CustomerAddress.id.desc()).all()
    return [
        AddressOut(
            id=r.id,
            label=r.label,
            line1=r.line1,
            line2=r.line2,
            city=r.city,
            pincode=r.pincode,
            lat=r.lat,
            lng=r.lng,
            is_default=r.is_default,
        )
        for r in rows
    ]


@router.post("", response_model=AddressOut)
def create_address(payload: AddressIn, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "CUSTOMER":
        raise HTTPException(403, "Only CUSTOMER can manage addresses")

    if payload.is_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({CustomerAddress.is_default: False})

    row = CustomerAddress(
        customer_id=user.id,
        label=payload.label,
        line1=payload.line1,
        line2=payload.line2,
        city=payload.city,
        pincode=payload.pincode,
        lat=payload.lat,
        lng=payload.lng,
        is_default=payload.is_default,
    )
    db.add(row)
    db.commit()
    db.refresh(row)
    return AddressOut(
        id=row.id,
        label=row.label,
        line1=row.line1,
        line2=row.line2,
        city=row.city,
        pincode=row.pincode,
        lat=row.lat,
        lng=row.lng,
        is_default=row.is_default,
    )


@router.post("/{address_id}/default")
def set_default(address_id: int, db: Session = Depends(get_db), user=Depends(get_current_user)):
    if user.role != "CUSTOMER":
        raise HTTPException(403, "Only CUSTOMER can manage addresses")

    row = db.query(CustomerAddress).filter(CustomerAddress.id == address_id, CustomerAddress.customer_id == user.id).first()
    if not row:
        raise HTTPException(404, "Address not found")

    db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({CustomerAddress.is_default: False})
    row.is_default = True
    db.commit()
    return {"ok": True}
