from __future__ import annotations

from datetime import datetime, timedelta

from fastapi import APIRouter, Depends
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import LoyaltyMembership, User
from ..schemas import LoyaltyMembershipOut

router = APIRouter(prefix="/loyalty", tags=["loyalty"], dependencies=[Depends(require_role("CUSTOMER"))])


@router.get("/membership", response_model=LoyaltyMembershipOut)
def get_membership(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    membership = (
        db.query(LoyaltyMembership)
        .filter(LoyaltyMembership.customer_id == user.id)
        .first()
    )

    if not membership:
        now = datetime.utcnow()
        membership = LoyaltyMembership(
            customer_id=user.id,
            tier="BASIC",
            points_balance=0,
            active=True,
            joined_at=now,
            expires_at=now + timedelta(days=365),
        )
        db.add(membership)
        db.commit()
        db.refresh(membership)

    return membership
