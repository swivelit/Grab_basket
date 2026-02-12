from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import User, CustomerAddress, FcmToken
from ..schemas import AddressCreateIn, AddressOut, FcmRegisterIn

router = APIRouter(prefix="/me", tags=["me"])


@router.get("/profile")
def profile(user: User = Depends(get_current_user)):
    return {
        "id": user.id,
        "email": user.email,
        "role": user.role,
        "is_partner_available": user.is_partner_available,
        "created_at": user.created_at,
    }


@router.get("/addresses", response_model=list[AddressOut], dependencies=[Depends(require_role("CUSTOMER"))])
def list_addresses(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    return (
        db.query(CustomerAddress)
        .filter(CustomerAddress.customer_id == user.id)
        .order_by(CustomerAddress.id.desc())
        .all()
    )


@router.post("/addresses", response_model=AddressOut, dependencies=[Depends(require_role("CUSTOMER"))])
def create_address(payload: AddressCreateIn, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    if payload.is_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})

    addr = CustomerAddress(customer_id=user.id, **payload.model_dump())
    db.add(addr)
    db.commit()
    db.refresh(addr)
    return addr


@router.post("/addresses/{address_id}/default", dependencies=[Depends(require_role("CUSTOMER"))])
def set_default(address_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    addr = (
        db.query(CustomerAddress)
        .filter(CustomerAddress.id == address_id, CustomerAddress.customer_id == user.id)
        .first()
    )
    if not addr:
        raise HTTPException(status_code=404, detail="Address not found")

    db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})
    addr.is_default = True
    db.commit()
    return {"ok": True}


@router.post("/fcm/register")
def register_fcm(payload: FcmRegisterIn, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    # Alias for /auth/fcm/register, kept for convenience.
    existing = db.query(FcmToken).filter(FcmToken.token == payload.token).first()
    if existing:
        return {"ok": True}

    db.add(FcmToken(user_id=user.id, token=payload.token, platform=payload.platform))
    db.commit()
    return {"ok": True}
