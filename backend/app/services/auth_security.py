from __future__ import annotations

import hashlib
import time
from datetime import timedelta

from fastapi import HTTPException
from sqlalchemy import or_
from sqlalchemy.orm import Session

from ..models import AuthChallenge, AuthRiskEvent, UserBlocklist
from ..time import coerce_utc, utc_now

_RATE_BUCKETS: dict[tuple[str, str], list[float]] = {}

def check_rate_limit(action: str, key: str, *, max_attempts: int, window_seconds: int) -> None:
    now = time.time()
    bucket_key = (action, key.lower().strip())
    rows = [ts for ts in _RATE_BUCKETS.get(bucket_key, []) if ts >= now - window_seconds]
    if len(rows) >= max_attempts:
        _RATE_BUCKETS[bucket_key] = rows
        raise HTTPException(status_code=429, detail=f"Too many {action} attempts. Try again later.")
    rows.append(now)
    _RATE_BUCKETS[bucket_key] = rows


def ensure_not_blocked(db: Session, *, email: str = "", device_id: str = "") -> None:
    email = (email or "").strip().lower()
    device_id = (device_id or "").strip()
    query = db.query(UserBlocklist).filter(UserBlocklist.active.is_(True))
    if email and device_id:
        query = query.filter(or_(UserBlocklist.email == email, UserBlocklist.device_id == device_id))
    elif email:
        query = query.filter(UserBlocklist.email == email)
    elif device_id:
        query = query.filter(UserBlocklist.device_id == device_id)
    else:
        return

    blocked = query.first()
    blocked_expires_at = coerce_utc(blocked.expires_at) if blocked else None
    if blocked and (blocked_expires_at is None or blocked_expires_at >= utc_now()):
        raise HTTPException(status_code=403, detail="Access blocked")


def record_risk_event(
    db: Session,
    *,
    email: str,
    event_type: str,
    reason: str,
    ip_address: str = "",
    user_agent: str = "",
    blocked: bool = False,
    risk_score: int = 50,
) -> None:
    db.add(
        AuthRiskEvent(
            email=(email or "")[:255].lower(),
            event_type=event_type[:48],
            reason=reason,
            ip_address=(ip_address or "")[:64],
            user_agent=(user_agent or "")[:512],
            blocked=blocked,
            risk_score=risk_score,
        )
    )


def hash_challenge_code(code: str) -> str:
    return hashlib.sha256((code or "").encode("utf-8")).hexdigest()


def create_challenge(
    db: Session,
    *,
    challenge_type: str,
    target: str,
    code: str,
    user_id: int | None = None,
    ttl_minutes: int = 15,
) -> AuthChallenge:
    row = AuthChallenge(
        user_id=user_id,
        challenge_type=challenge_type,
        target=target,
        code_hash=hash_challenge_code(code),
        expires_at=utc_now() + timedelta(minutes=ttl_minutes),
        status="PENDING",
    )
    db.add(row)
    db.flush()
    return row


def verify_challenge(db: Session, *, challenge_id: int, code: str) -> AuthChallenge:
    row = db.query(AuthChallenge).filter(AuthChallenge.id == challenge_id).first()
    if not row:
        raise HTTPException(status_code=404, detail="Challenge not found")
    if row.status != "PENDING":
        raise HTTPException(status_code=400, detail="Challenge already used")
    expires_at = coerce_utc(row.expires_at)
    if expires_at and expires_at < utc_now():
        row.status = "EXPIRED"
        raise HTTPException(status_code=400, detail="Challenge expired")

    row.attempts += 1
    if row.code_hash != hash_challenge_code(code):
        if row.attempts >= 5:
            row.status = "FAILED"
        raise HTTPException(status_code=400, detail="Invalid challenge code")

    row.status = "VERIFIED"
    row.verified_at = utc_now()
    return row
