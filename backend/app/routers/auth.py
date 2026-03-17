from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..config import settings
from ..db import get_db
from ..models import User, FcmToken
from ..schemas import RegisterIn, LoginIn, Token, FcmRegisterIn
from ..auth import hash_password, verify_password, create_access_token, get_current_user

router = APIRouter(prefix="/auth", tags=["auth"])

ALLOWED_ROLES = {"CUSTOMER", "SELLER", "PARTNER"}


@router.post("/register", response_model=Token)
def register(data: RegisterIn, db: Session = Depends(get_db)):
    role = (data.role or "").upper().strip()
    if role == "ADMIN":
        # Only allow creating an ADMIN account outside production for testing.
        if settings.APP_ENV.lower() == "prod":
            raise HTTPException(status_code=400, detail="Invalid role")
    elif role not in ALLOWED_ROLES:
        raise HTTPException(status_code=400, detail="Invalid role")

    existing = db.query(User).filter(User.email == data.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email already registered")

    user = User(email=data.email, password_hash=hash_password(data.password), role=role)
    db.add(user)
    db.commit()
    db.refresh(user)

    token = create_access_token(subject=user.email, role=user.role)
    return Token(access_token=token, role=user.role)


@router.post("/login", response_model=Token)
def login(data: LoginIn, db: Session = Depends(get_db)):
    user = db.query(User).filter(User.email == data.email).first()
    if not user or not verify_password(data.password, user.password_hash):
        raise HTTPException(status_code=401, detail="Invalid credentials")

    token = create_access_token(subject=user.email, role=user.role)
    return Token(access_token=token, role=user.role)


@router.post("/fcm/register")
def register_fcm_token(payload: FcmRegisterIn, db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    existing = db.query(FcmToken).filter(FcmToken.token == payload.token).first()
    if existing:
        return {"ok": True}

    db.add(FcmToken(user_id=user.id, token=payload.token, platform=payload.platform))
    db.commit()
    return {"ok": True}
