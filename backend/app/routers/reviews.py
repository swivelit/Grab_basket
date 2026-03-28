from __future__ import annotations

from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import Order, OrderReview, User, Vendor
from ..schemas import OrderReviewCreateIn, OrderReviewOut

router = APIRouter(prefix="/reviews", tags=["reviews"], dependencies=[Depends(require_role("CUSTOMER"))])


def _recompute_vendor_rating(db: Session, vendor_id: int) -> None:
    rows = db.query(OrderReview).filter(OrderReview.vendor_id == vendor_id).all()
    total = len(rows)
    avg = sum(int(row.rating or 0) for row in rows) / total if total > 0 else 0.0

    vendor = db.query(Vendor).filter(Vendor.id == vendor_id).first()
    if not vendor:
        return

    vendor.avg_rating = round(float(avg), 2)
    vendor.total_ratings = int(total)


@router.get("/me", response_model=list[OrderReviewOut])
def list_my_reviews(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    return (
        db.query(OrderReview)
        .filter(OrderReview.customer_id == user.id)
        .order_by(OrderReview.created_at.desc())
        .all()
    )


@router.post("", response_model=OrderReviewOut)
def upsert_review(
    payload: OrderReviewCreateIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    order = (
        db.query(Order)
        .filter(Order.id == payload.order_id, Order.customer_id == user.id)
        .first()
    )
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    if str(order.status or "").upper() != "DELIVERED":
        raise HTTPException(status_code=400, detail="You can review only delivered orders")

    review = db.query(OrderReview).filter(OrderReview.order_id == order.id).first()
    if review:
        review.rating = int(payload.rating)
        review.review_text = payload.review_text
        review.tags = ",".join(payload.tags)
        review.updated_at = datetime.now(timezone.utc)
    else:
        review = OrderReview(
            order_id=order.id,
            vendor_id=order.vendor_id,
            customer_id=user.id,
            rating=int(payload.rating),
            review_text=payload.review_text,
            tags=",".join(payload.tags),
        )
        db.add(review)

    _recompute_vendor_rating(db, int(order.vendor_id))
    db.commit()
    db.refresh(review)
    return review
