from __future__ import annotations

from datetime import datetime, timezone

from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..models import Order, SupportTicket, User
from ..schemas import SupportTicketCreateIn, SupportTicketOut

router = APIRouter(prefix="/support", tags=["support"], dependencies=[Depends(require_role("CUSTOMER"))])


@router.get("/tickets", response_model=list[SupportTicketOut])
def list_tickets(db: Session = Depends(get_db), user: User = Depends(get_current_user)):
    return (
        db.query(SupportTicket)
        .filter(SupportTicket.customer_id == user.id)
        .order_by(SupportTicket.created_at.desc())
        .all()
    )


@router.post("/tickets", response_model=SupportTicketOut)
def create_ticket(
    payload: SupportTicketCreateIn,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    order = None
    if payload.order_id is not None:
        order = (
            db.query(Order)
            .filter(Order.id == payload.order_id, Order.customer_id == user.id)
            .first()
        )
        if not order:
            raise HTTPException(status_code=404, detail="Order not found")

    ticket = SupportTicket(
        order_id=payload.order_id,
        customer_id=user.id,
        vendor_id=getattr(order, "vendor_id", None),
        category=str(payload.category or "GENERAL").upper(),
        status="OPEN",
        subject=payload.subject,
        message=payload.message,
    )
    db.add(ticket)
    db.commit()
    db.refresh(ticket)
    return ticket


@router.post("/tickets/{ticket_id}/close", response_model=SupportTicketOut)
def close_ticket(
    ticket_id: int,
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    ticket = (
        db.query(SupportTicket)
        .filter(SupportTicket.id == ticket_id, SupportTicket.customer_id == user.id)
        .first()
    )
    if not ticket:
        raise HTTPException(status_code=404, detail="Ticket not found")

    ticket.status = "CLOSED"
    ticket.closed_at = datetime.now(timezone.utc)
    if not ticket.resolution_note:
        ticket.resolution_note = "Closed by customer"

    db.commit()
    db.refresh(ticket)
    return ticket
