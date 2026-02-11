from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..database import get_db
from ..models import User, CustomerAddress, DeviceToken, Role
from ..schemas import AddressCreateIn, AddressOut, DeviceTokenIn
from ..security import get_current_user

router = APIRouter(prefix="/me", tags=["me"])


@router.get("/addresses", response_model=list[AddressOut])
def list_addresses(user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    if user.role != Role.CUSTOMER:
        raise HTTPException(status_code=403, detail="Only customers can manage addresses")
    return db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).order_by(CustomerAddress.id.desc()).all()


@router.post("/addresses", response_model=AddressOut)
def create_address(data: AddressCreateIn, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    if user.role != Role.CUSTOMER:
        raise HTTPException(status_code=403, detail="Only customers can manage addresses")

    if data.is_default:
        db.query(CustomerAddress).filter(CustomerAddress.customer_id == user.id).update({"is_default": False})

    addr = CustomerAddress(
        customer_id=user.id,
        label=data.label,
        line1=data.line1,
        line2=data.line2,
        city=data.city,
        pincode=data.pincode,
        lat=data.lat,
        lng=data.lng,
        is_default=data.is_default,
    )
    db.add(addr)
    db.commit()
    db.refresh(addr)
    return addr


@router.post("/device-token")
def upsert_device_token(data: DeviceTokenIn, user: User = Depends(get_current_user), db: Session = Depends(get_db)):
    existing = db.query(DeviceToken).filter(DeviceToken.user_id == user.id, DeviceToken.token == data.token).first()
    if existing:
        return {"ok": True, "message": "Already saved"}

    dt = DeviceToken(user_id=user.id, token=data.token, platform=data.platform)
    db.add(dt)
    db.commit()
    return {"ok": True}