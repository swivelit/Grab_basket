from __future__ import annotations

from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy import func
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import Coupon, CouponRedemption, User
from ..schemas import CouponApplyIn, CouponApplyOut, CouponOut

router = APIRouter(prefix="/offers", tags=["offers"], dependencies=[Depends(require_role("CUSTOMER"))])


def _calculate_discount(coupon: Coupon, order_amount: float) -> float:
    amount = round(float(order_amount), 2)

    if str(coupon.discount_type or "FLAT").upper() == "PERCENT":
        discount = amount * (float(coupon.discount_value or 0.0) / 100.0)
    else:
        discount = float(coupon.discount_value or 0.0)

    max_cap = float(coupon.max_discount_amount or 0.0)
    if max_cap > 0:
        discount = min(discount, max_cap)

    return round(max(0.0, min(discount, amount)), 2)


@router.get("/coupons", response_model=list[CouponOut])
def list_coupons(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    now = datetime.now(timezone.utc)
    return (
        db.query(Coupon)
        .filter(Coupon.active == True)  # noqa: E712
        .filter((Coupon.valid_from == None) | (Coupon.valid_from <= now))  # noqa: E711
        .filter((Coupon.valid_to == None) | (Coupon.valid_to >= now))  # noqa: E711
        .order_by(Coupon.id.desc())
        .all()
    )


@router.post("/coupons/apply", response_model=CouponApplyOut)
def apply_coupon(
    payload: CouponApplyIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    now = datetime.now(timezone.utc)
    coupon = db.query(Coupon).filter(func.upper(Coupon.code) == payload.code.upper()).first()
    if not coupon:
        raise HTTPException(status_code=404, detail="Coupon not found")

    if not coupon.active:
        raise HTTPException(status_code=400, detail="Coupon is inactive")
    if coupon.valid_from and now < coupon.valid_from:
        raise HTTPException(status_code=400, detail="Coupon is not active yet")
    if coupon.valid_to and now > coupon.valid_to:
        raise HTTPException(status_code=400, detail="Coupon has expired")

    order_amount = round(float(payload.order_amount), 2)
    if order_amount < float(coupon.min_order_amount or 0.0):
        raise HTTPException(status_code=400, detail="Order amount does not meet coupon minimum")

    if int(coupon.usage_limit_global or 0) > 0:
        total_uses = db.query(CouponRedemption).filter(CouponRedemption.coupon_id == coupon.id).count()
        if total_uses >= int(coupon.usage_limit_global):
            raise HTTPException(status_code=400, detail="Coupon usage limit reached")

    user_uses = (
        db.query(CouponRedemption)
        .filter(CouponRedemption.coupon_id == coupon.id, CouponRedemption.customer_id == user.id)
        .count()
    )
    if user_uses >= int(coupon.usage_limit_per_user or 1):
        raise HTTPException(status_code=400, detail="Coupon usage limit reached for this account")

    discount_amount = _calculate_discount(coupon, order_amount)
    final_amount = round(order_amount - discount_amount, 2)

    db.add(
        CouponRedemption(
            coupon_id=coupon.id,
            customer_id=user.id,
            discount_amount=discount_amount,
        )
    )
    db.commit()

    return CouponApplyOut(
        ok=True,
        code=coupon.code,
        discount_amount=discount_amount,
        final_amount=final_amount,
        message="Coupon applied successfully",
    )
