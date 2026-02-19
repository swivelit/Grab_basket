from fastapi import APIRouter, Depends, Request, HTTPException
from fastapi.responses import HTMLResponse
from fastapi.templating import Jinja2Templates
from sqlalchemy.orm import Session

from .db import get_db
from .deps import get_current_user
from .models import User, Vendor, Order

templates = Jinja2Templates(directory="app/templates")
router = APIRouter(prefix="/admin", tags=["admin"])


def require_admin(user):
    if user.role != "ADMIN":
        raise HTTPException(403, "ADMIN only")


@router.get("", response_class=HTMLResponse)
def dashboard(request: Request, db: Session = Depends(get_db), user=Depends(get_current_user)):
    require_admin(user)
    counts = {
        "users": db.query(User).count(),
        "vendors": db.query(Vendor).count(),
        "orders": db.query(Order).count(),
    }
    latest_orders = db.query(Order).order_by(Order.id.desc()).limit(20).all()
    return templates.TemplateResponse("dashboard.html", {
        "request": request,
        "counts": counts,
        "orders": latest_orders,
    })


@router.get("/vendors", response_class=HTMLResponse)
def vendors(request: Request, db: Session = Depends(get_db), user=Depends(get_current_user)):
    require_admin(user)
    rows = db.query(Vendor).order_by(Vendor.id.desc()).all()
    return templates.TemplateResponse("vendors.html", {"request": request, "vendors": rows})


@router.get("/partners", response_class=HTMLResponse)
def partners(request: Request, db: Session = Depends(get_db), user=Depends(get_current_user)):
    require_admin(user)
    rows = db.query(User).filter(User.role == "PARTNER").order_by(User.id.desc()).all()
    return templates.TemplateResponse("partners.html", {"request": request, "partners": rows})
