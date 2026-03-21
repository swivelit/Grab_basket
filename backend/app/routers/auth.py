from __future__ import annotations

from fastapi import APIRouter, Depends, HTTPException, Request
from sqlalchemy.orm import Session

from ..config import settings
from ..db import get_db
from ..models import User, FcmToken
from ..schemas import RegisterIn, LoginIn, Token, FcmRegisterIn, RefreshTokenIn, LogoutIn
from ..auth import (
    hash_password,
    verify_password,
    get_current_user,
    issue_auth_tokens,
    rotate_refresh_token,
    revoke_refresh_token,
)

router = APIRouter(prefix="/auth", tags=["auth"])

ALLOWED_ROLES = {"CUSTOMER", "SELLER", "PARTNER"}


def _request_client_ip(request: Request) -> str:
    forwarded_for = request.headers.get("x-forwarded-for", "").strip()
    if forwarded_for:
        return forwarded_for.split(",")[0].strip()[:64]
    client = getattr(request, "client", None)
    return (getattr(client, "host", "") or "")[:64]


def _request_user_agent(request: Request) -> str:
    return request.headers.get("user-agent", "")[:512]


@router.post("/register", response_model=Token)
def register(data: RegisterIn, request: Request, db: Session = Depends(get_db)):
    role = (data.role or "").upper().strip()
    if role == "ADMIN":
        # Only allow creating an ADMIN account outside production for testing.
        if settings.is_prod:
            raise HTTPException(status_code=400, detail="Invalid role")
    elif role not in ALLOWED_ROLES:
        raise HTTPException(status_code=400, detail="Invalid role")

    existing = db.query(User).filter(User.email == data.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email already registered")

    user = User(email=data.email, password_hash=hash_password(data.password), role=role)
    db.add(user)
    db.flush()

    tokens = issue_auth_tokens(
        db,
        user,
        user_agent=_request_user_agent(request),
        ip_address=_request_client_ip(request),
    )
    db.commit()
    db.refresh(user)

    tokens["role"] = user.role
    return Token(**tokens)


@router.post("/login", response_model=Token)
def login(data: LoginIn, request: Request, db: Session = Depends(get_db)):
    user = db.query(User).filter(User.email == data.email).first()
    if not user or not verify_password(data.password, user.password_hash):
        raise HTTPException(status_code=401, detail="Invalid credentials")

    tokens = issue_auth_tokens(
        db,
        user,
        user_agent=_request_user_agent(request),
        ip_address=_request_client_ip(request),
    )
    db.commit()
    return Token(**tokens)


@router.post("/refresh", response_model=Token)
def refresh_tokens(data: RefreshTokenIn, request: Request, db: Session = Depends(get_db)):
    tokens = rotate_refresh_token(
        db,
        data.refresh_token,
        user_agent=_request_user_agent(request),
        ip_address=_request_client_ip(request),
    )
    db.commit()
    return Token(**tokens)


@router.post("/logout")
def logout(data: LogoutIn, db: Session = Depends(get_db)):
    revoke_refresh_token(db, data.refresh_token)
    db.commit()
    return {"ok": True}


@router.post("/fcm/register")
def register_fcm_token(
    payload: FcmRegisterIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    existing = db.query(FcmToken).filter(FcmToken.token == payload.token).first()
    if existing:
        if existing.user_id != user.id or existing.platform != payload.platform:
            existing.user_id = user.id
            existing.platform = payload.platform
            db.commit()
        return {"ok": True}

    db.add(FcmToken(user_id=user.id, token=payload.token, platform=payload.platform))
    db.commit()
    return {"ok": True}