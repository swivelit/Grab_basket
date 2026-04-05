from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import User, CustomerAddress, FcmToken
from ..schemas import AddressCreateIn, AddressOut, FcmRegisterIn

router = APIRouter(prefix="/me", tags=["me"])


def _get_customer_address(db: Session, user_id: int, address_id: int) -> CustomerAddress:
    address = (
        db.query(CustomerAddress)
        .filter(CustomerAddress.id == address_id, CustomerAddress.customer_id == user_id)
        .first()
    )
    if not address:
        raise HTTPException(status_code=404, detail="Address not found")
    return address


def _ensure_default_address(db: Session, user_id: int, exclude_address_id: int | None = None) -> None:
    existing_default = (
        db.query(CustomerAddress)
        .filter(CustomerAddress.customer_id == user_id, CustomerAddress.is_default.is_(True))
        .first()
    )
    if existing_default:
        return

    fallback_query = db.query(CustomerAddress).filter(CustomerAddress.customer_id == user_id)
    if exclude_address_id is not None:
        fallback_query = fallback_query.filter(CustomerAddress.id != exclude_address_id)

    fallback = fallback_query.order_by(CustomerAddress.id.desc()).first()
    if fallback:
        fallback.is_default = True
        return

    if exclude_address_id is None:
        return

    original = (
        db.query(CustomerAddress)
        .filter(CustomerAddress.customer_id == user_id, CustomerAddress.id == exclude_address_id)
        .first()
    )
    if original:
        original.is_default = True


@router.get("/profile")
def profile(user: User = Depends(get_current_user)):
    return {
        "id": user.id,
        "email": user.email,
        "phone": user.phone,
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
    has_existing_addresses = (
        db.query(CustomerAddress.id)
        .filter(CustomerAddress.customer_id == user.id)
        .first()
        is not None
    )

    should_be_default = bool(payload.is_default or not has_existing_addresses)
    if should_be_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})

    addr = CustomerAddress(customer_id=user.id, **payload.model_dump(), is_default=should_be_default)
    db.add(addr)
    db.commit()
    db.refresh(addr)
    return addr


@router.put("/addresses/{address_id}", response_model=AddressOut, dependencies=[Depends(require_role("CUSTOMER"))])
def update_address(
    address_id: int,
    payload: AddressCreateIn,
    user: User = Depends(get_current_user),
    db: Session = Depends(get_db),
):
    addr = _get_customer_address(db, user.id, address_id)
    was_default = bool(addr.is_default)

    if payload.is_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})

    for field, value in payload.model_dump().items():
        setattr(addr, field, value)

    if not payload.is_default and was_default:
        _ensure_default_address(db, user.id, exclude_address_id=addr.id)

    db.commit()
    db.refresh(addr)
    return addr


@router.delete("/addresses/{address_id}", dependencies=[Depends(require_role("CUSTOMER"))])
def delete_address(address_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    addr = _get_customer_address(db, user.id, address_id)
    was_default = bool(addr.is_default)

    db.delete(addr)
    db.flush()

    if was_default:
        _ensure_default_address(db, user.id)

    db.commit()
    return {"ok": True}


@router.post("/addresses/{address_id}/default", dependencies=[Depends(require_role("CUSTOMER"))])
def set_default(address_id: int, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    addr = _get_customer_address(db, user.id, address_id)

    db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})
    addr.is_default = True
    db.commit()
    return {"ok": True}


@router.post("/fcm/register")
def register_fcm(payload: FcmRegisterIn, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    # Alias for /auth/fcm/register
    existing = db.query(FcmToken).filter(FcmToken.token == payload.token).first()
    if existing:
        return {"ok": True}

    db.add(FcmToken(user_id=user.id, token=payload.token, platform=payload.platform))
    db.commit()
    return {"ok": True}