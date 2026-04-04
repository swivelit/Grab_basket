from __future__ import annotations

import random

from fastapi import APIRouter, Depends, HTTPException, Request
from sqlalchemy import or_
from sqlalchemy.orm import Session

from ..auth import (
    get_current_user,
    hash_password,
    issue_auth_tokens,
    revoke_refresh_token,
    rotate_refresh_token,
    verify_password,
)
from ..config import settings
from ..db import get_db
from ..models import FcmToken, User
from ..schemas import (
    AuthChallengeCreateIn,
    AuthChallengeVerifyIn,
    FcmRegisterIn,
    LoginIn,
    LogoutIn,
    RefreshTokenIn,
    RegisterIn,
    Token,
)
from ..services.auth_security import (
    check_rate_limit,
    create_challenge,
    ensure_not_blocked,
    record_risk_event,
    verify_challenge,
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
        if settings.is_prod:
            raise HTTPException(status_code=400, detail="Invalid role")
    elif role not in ALLOWED_ROLES:
        raise HTTPException(status_code=400, detail="Invalid role")

    email = data.email.lower().strip()
    phone = data.phone.strip()
    ip = _request_client_ip(request)
    ua = _request_user_agent(request)
    ensure_not_blocked(db, email=email, device_id=data.device_id)
    check_rate_limit("signup", f"{email}:{ip}", max_attempts=10, window_seconds=300)

    existing = db.query(User).filter(or_(User.email == email, User.phone == phone)).first()
    if existing:
        reason = "duplicate_phone" if existing.phone == phone else "duplicate_email"
        record_risk_event(db, email=email, event_type="SIGNUP", reason=reason, ip_address=ip, user_agent=ua)
        db.commit()
        raise HTTPException(
            status_code=400,
            detail="Phone number already registered" if existing.phone == phone else "Email already registered",
        )

    user = User(
        email=email,
        phone=phone,
        password_hash=hash_password(data.password),
        role=role,
    )
    db.add(user)
    db.flush()

    tokens = issue_auth_tokens(
        db,
        user,
        user_agent=ua,
        ip_address=ip,
        device_id=data.device_id,
    )
    db.commit()
    db.refresh(user)

    tokens["role"] = user.role
    return Token(**tokens)


@router.post("/login", response_model=Token)
def login(data: LoginIn, request: Request, db: Session = Depends(get_db)):
    email = data.email.lower().strip() if data.email else ""
    phone = data.phone.strip() if data.phone else ""
    identifier = email or phone
    ip = _request_client_ip(request)
    ua = _request_user_agent(request)
    ensure_not_blocked(db, email=email, device_id=data.device_id)
    check_rate_limit("login", f"{identifier}:{ip}", max_attempts=12, window_seconds=300)

    user_query = db.query(User)
    if email:
        user_query = user_query.filter(User.email == email)
    else:
        user_query = user_query.filter(User.phone == phone)

    user = user_query.first()
    if not user or not verify_password(data.password, user.password_hash):
        record_risk_event(
            db,
            email=(email or getattr(user, "email", "")),
            event_type="LOGIN",
            reason="invalid_credentials",
            ip_address=ip,
            user_agent=ua,
            risk_score=70,
        )
        db.commit()
        raise HTTPException(status_code=401, detail="Invalid credentials")

    tokens = issue_auth_tokens(
        db,
        user,
        user_agent=ua,
        ip_address=ip,
        device_id=data.device_id,
    )
    db.commit()
    return Token(**tokens)


@router.post("/refresh", response_model=Token)
def refresh_tokens(data: RefreshTokenIn, request: Request, db: Session = Depends(get_db)):
    ip = _request_client_ip(request)
    ua = _request_user_agent(request)
    try:
        tokens = rotate_refresh_token(
            db,
            data.refresh_token,
            user_agent=ua,
            ip_address=ip,
            device_id=data.device_id,
        )
    except HTTPException:
        record_risk_event(
            db,
            email="",
            event_type="LOGIN",
            reason="refresh_failed_or_device_mismatch",
            ip_address=ip,
            user_agent=ua,
            risk_score=85,
        )
        db.commit()
        raise

    db.commit()
    return Token(**tokens)


@router.post("/logout")
def logout(data: LogoutIn, db: Session = Depends(get_db)):
    revoked = revoke_refresh_token(db, data.refresh_token, device_id=data.device_id)
    db.commit()
    return {"ok": bool(revoked)}


@router.post("/challenge/start")
def start_auth_challenge(payload: AuthChallengeCreateIn, db: Session = Depends(get_db)):
    # Delivery to SMS/email provider intentionally externalized. We return code in non-production for dev/test.
    code = f"{random.randint(100000, 999999)}"
    challenge = create_challenge(
        db,
        challenge_type=payload.challenge_type,
        target=payload.target.strip().lower(),
        code=code,
    )
    db.commit()
    response = {"ok": True, "challenge_id": challenge.id, "expires_at": challenge.expires_at.isoformat()}
    if not settings.is_prod:
        response["dev_code"] = code
    return response


@router.post("/challenge/verify")
def verify_auth_challenge(payload: AuthChallengeVerifyIn, db: Session = Depends(get_db)):
    row = verify_challenge(db, challenge_id=payload.challenge_id, code=payload.code)
    db.commit()
    return {"ok": True, "challenge_id": row.id, "status": row.status, "verified_at": row.verified_at.isoformat()}


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