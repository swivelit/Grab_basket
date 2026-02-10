from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from ..database import get_db
from ..deps import require_role
from ..models import Vendor, SellerProfile, Product, Order, OrderStatus, Role, PartnerProfile
from ..schemas import VendorOut, ProductOut, ProductCreate, OrderOut

router = APIRouter(prefix="/seller", tags=["seller"])

@router.post("/vendor", response_model=VendorOut)
def create_vendor(name: str, description: str = "", address: str = "", db: Session = Depends(get_db), user=Depends(require_role(Role.SELLER))):
    existing_profile = db.query(SellerProfile).filter(SellerProfile.user_id == user.id).first()
    if existing_profile:
        return db.query(Vendor).filter(Vendor.id == existing_profile.vendor_id).first()

    vendor = Vendor(name=name, description=description, address=address)
    db.add(vendor)
    db.flush()
    profile = SellerProfile(user_id=user.id, vendor_id=vendor.id)
    db.add(profile)
    db.commit()
    db.refresh(vendor)
    return vendor

def _my_vendor_id(db: Session, user_id: int) -> int:
    profile = db.query(SellerProfile).filter(SellerProfile.user_id == user_id).first()
    if not profile:
        raise HTTPException(status_code=400, detail="Seller vendor not set. Create vendor first.")
    return profile.vendor_id

@router.post("/products", response_model=ProductOut)
def add_product(payload: ProductCreate, db: Session = Depends(get_db), user=Depends(require_role(Role.SELLER))):
    vendor_id = _my_vendor_id(db, user.id)
    product = Product(vendor_id=vendor_id, **payload.model_dump())
    db.add(product)
    db.commit()
    db.refresh(product)
    return product

@router.get("/products", response_model=list[ProductOut])
def my_products(db: Session = Depends(get_db), user=Depends(require_role(Role.SELLER))):
    vendor_id = _my_vendor_id(db, user.id)
    return db.query(Product).filter(Product.vendor_id == vendor_id).order_by(Product.id.desc()).all()

@router.get("/orders", response_model=list[OrderOut])
def vendor_orders(status: OrderStatus | None = None, db: Session = Depends(get_db), user=Depends(require_role(Role.SELLER))):
    vendor_id = _my_vendor_id(db, user.id)
    q = db.query(Order).filter(Order.vendor_id == vendor_id)
    if status:
        q = q.filter(Order.status == status)
    return q.order_by(Order.id.desc()).all()

@router.post("/orders/{order_id}/accept", response_model=OrderOut)
def accept_order(order_id: int, db: Session = Depends(get_db), user=Depends(require_role(Role.SELLER))):
    vendor_id = _my_vendor_id(db, user.id)
    order = db.query(Order).filter(Order.id == order_id, Order.vendor_id == vendor_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.status != OrderStatus.CREATED:
        raise HTTPException(status_code=400, detail="Order cannot be accepted in current state")

    order.status = OrderStatus.ACCEPTED_BY_SELLER

    # Very simple dispatch: pick first available partner
    partner = (
        db.query(PartnerProfile)
        .filter(PartnerProfile.is_available == True)
        .order_by(PartnerProfile.user_id.asc())
        .first()
    )
    if partner:
        order.partner_id = partner.user_id
        order.status = OrderStatus.ASSIGNED_TO_PARTNER
        partner.is_available = False

    db.commit()
    db.refresh(order)
    return order
