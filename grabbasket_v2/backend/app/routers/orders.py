from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from ..database import get_db
from ..deps import require_role
from ..models import Order, OrderItem, Product, OrderStatus, Role
from ..schemas import OrderCreate, OrderOut
from decimal import Decimal

router = APIRouter(prefix="/orders", tags=["orders"])

@router.post("", response_model=OrderOut)
def create_order(payload: OrderCreate, db: Session = Depends(get_db), user=Depends(require_role(Role.CUSTOMER))):
    product_ids = [i.product_id for i in payload.items]
    products = db.query(Product).filter(Product.id.in_(product_ids), Product.vendor_id == payload.vendor_id).all()
    if len(products) != len(product_ids):
        raise HTTPException(status_code=400, detail="One or more products invalid for this vendor")

    price_map = {p.id: p for p in products}
    order = Order(customer_id=user.id, vendor_id=payload.vendor_id, status=OrderStatus.CREATED, delivery_fee=Decimal("20.00"))
    db.add(order)
    db.flush()

    total = Decimal("0.00")
    for item in payload.items:
        p = price_map[item.product_id]
        total += Decimal(str(p.price)) * item.qty
        db.add(OrderItem(
            order_id=order.id,
            product_id=p.id,
            name_snapshot=p.name,
            price_snapshot=p.price,
            qty=item.qty,
        ))

    order.total_amount = total + Decimal(str(order.delivery_fee))
    db.commit()
    db.refresh(order)
    return order

@router.get("/me", response_model=list[OrderOut])
def my_orders(db: Session = Depends(get_db), user=Depends(require_role(Role.CUSTOMER))):
    return db.query(Order).filter(Order.customer_id == user.id).order_by(Order.id.desc()).all()
