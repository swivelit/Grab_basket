from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import require_role, get_current_user
from ..db import get_db
from ..models import User, CustomerAddress
from ..schemas import AddressCreateIn, AddressOut

router = APIRouter(prefix="/addresses", tags=["addresses"], dependencies=[Depends(require_role("CUSTOMER"))])


@router.get("", response_model=list[AddressOut])
def list_addresses(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    return (
        db.query(CustomerAddress)
        .filter(CustomerAddress.customer_id == user.id)
        .order_by(CustomerAddress.id.desc())
        .all()
    )


@router.post("", response_model=AddressOut)
def create_address(payload: AddressCreateIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    if payload.is_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})

    a = CustomerAddress(customer_id=user.id, **payload.model_dump())
    db.add(a)
    db.commit()
    db.refresh(a)
    return a


@router.post("/{address_id}/default")
def set_default(address_id: int, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    a = (
        db.query(CustomerAddress)
        .filter(CustomerAddress.id == address_id, CustomerAddress.customer_id == user.id)
        .first()
    )
    if not a:
        raise HTTPException(status_code=404, detail="Address not found")

    db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})
    a.is_default = True
    db.commit()
    return {"ok": True}
