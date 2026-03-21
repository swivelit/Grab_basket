from __future__ import annotations

import hashlib
import secrets
from datetime import datetime, timedelta, timezone
from typing import Optional
from uuid import uuid4

from fastapi import Depends, HTTPException
from fastapi.security import OAuth2PasswordBearer
from jose import jwt, JWTError
from passlib.context import CryptContext
from sqlalchemy.orm import Session

from .config import settings
from .db import get_db
from .models import RefreshToken, User

pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")
oauth2_scheme = OAuth2PasswordBearer(tokenUrl="/auth/login")


def _utcnow() -> datetime:
    return datetime.now(timezone.utc)


def _normalize_password(password: str) -> str:
    raw = (password or "").encode("utf-8")
    if len(raw) > 72:
        raw = raw[:72]
    return raw.decode("utf-8", errors="ignore")


def hash_password(password: str) -> str:
    return pwd_context.hash(_normalize_password(password))


def verify_password(password: str, password_hash: str) -> bool:
    return pwd_context.verify(_normalize_password(password), password_hash)


def _jwt_expiry(minutes: int) -> tuple[datetime, int]:
    now = _utcnow()
    expires_at = now + timedelta(minutes=minutes)
    return now, int(expires_at.timestamp())


def create_access_token(subject: str, role: str) -> str:
    now, exp_ts = _jwt_expiry(settings.ACCESS_TOKEN_MINUTES)
    payload = {
        "sub": subject,
        "role": role,
        "type": "access",
        "iat": int(now.timestamp()),
        "exp": exp_ts,
        "jti": str(uuid4()),
    }
    return jwt.encode(payload, settings.JWT_SECRET, algorithm=settings.JWT_ALG)


def _hash_refresh_token(token: str) -> str:
    return hashlib.sha256(token.encode("utf-8")).hexdigest()


def _issue_refresh_token(
    db: Session,
    user: User,
    *,
    token_family: str | None = None,
    user_agent: str = "",
    ip_address: str = "",
) -> str:
    raw_token = secrets.token_urlsafe(settings.REFRESH_TOKEN_BYTES)
    now = _utcnow()
    refresh_row = RefreshToken(
        user_id=user.id,
        token_hash=_hash_refresh_token(raw_token),
        token_family=token_family or uuid4().hex,
        user_agent=(user_agent or "")[:512],
        ip_address=(ip_address or "")[:64],
        expires_at=(now + timedelta(minutes=settings.REFRESH_TOKEN_MINUTES)).replace(tzinfo=None),
        last_used_at=None,
        revoked_at=None,
    )
    db.add(refresh_row)
    db.flush()
    return raw_token


def issue_auth_tokens(
    db: Session,
    user: User,
    *,
    user_agent: str = "",
    ip_address: str = "",
) -> dict:
    access_token = create_access_token(subject=user.email, role=user.role)
    refresh_token = _issue_refresh_token(db, user, user_agent=user_agent, ip_address=ip_address)
    return {
        "access_token": access_token,
        "refresh_token": refresh_token,
        "token_type": "bearer",
        "access_token_expires_in": settings.ACCESS_TOKEN_MINUTES * 60,
        "refresh_token_expires_in": settings.REFRESH_TOKEN_MINUTES * 60,
        "role": user.role,
    }


def decode_token(token: str) -> dict:
    try:
        payload = jwt.decode(token, settings.JWT_SECRET, algorithms=[settings.JWT_ALG])
    except JWTError:
        raise HTTPException(status_code=401, detail="Invalid token")

    if payload.get("type") not in {None, "access"}:
        raise HTTPException(status_code=401, detail="Invalid token")

    return payload


def _refresh_row_for_token(db: Session, refresh_token: str) -> RefreshToken | None:
    token_hash = _hash_refresh_token(refresh_token)
    return db.query(RefreshToken).filter(RefreshToken.token_hash == token_hash).first()


def rotate_refresh_token(
    db: Session,
    refresh_token: str,
    *,
    user_agent: str = "",
    ip_address: str = "",
) -> dict:
    row = _refresh_row_for_token(db, refresh_token)
    now = _utcnow().replace(tzinfo=None)

    if not row or row.revoked_at is not None or row.expires_at <= now:
        raise HTTPException(status_code=401, detail="Invalid refresh token")

    user = db.query(User).filter(User.id == row.user_id).first()
    if not user:
        raise HTTPException(status_code=401, detail="User not found")

    row.revoked_at = now
    row.last_used_at = now

    access_token = create_access_token(subject=user.email, role=user.role)
    next_refresh_token = _issue_refresh_token(
        db,
        user,
        token_family=row.token_family,
        user_agent=user_agent,
        ip_address=ip_address,
    )

    return {
        "access_token": access_token,
        "refresh_token": next_refresh_token,
        "token_type": "bearer",
        "access_token_expires_in": settings.ACCESS_TOKEN_MINUTES * 60,
        "refresh_token_expires_in": settings.REFRESH_TOKEN_MINUTES * 60,
        "role": user.role,
    }


def revoke_refresh_token(db: Session, refresh_token: str) -> bool:
    row = _refresh_row_for_token(db, refresh_token)
    if not row:
        return False

    if row.revoked_at is None:
        row.revoked_at = _utcnow().replace(tzinfo=None)
        row.last_used_at = row.revoked_at
    return True


def get_current_user(db: Session = Depends(get_db), token: str = Depends(oauth2_scheme)) -> User:
    payload = decode_token(token)
    email: Optional[str] = payload.get("sub")
    if not email:
        raise HTTPException(status_code=401, detail="Invalid token")

    user = db.query(User).filter(User.email == email).first()
    if not user:
        raise HTTPException(status_code=401, detail="User not found")
    return user


def require_role(*roles: str):
    def _guard(user: User = Depends(get_current_user)) -> User:
        allowed = []
        for r in roles:
            allowed.append((getattr(r, "value", r) or "").__str__().upper())
        if (user.role or "").upper() not in allowed:
            raise HTTPException(status_code=403, detail="Forbidden")
        return user

    return _guard