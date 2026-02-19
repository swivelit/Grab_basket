from sqlalchemy.orm import Session

from .db import SessionLocal, engine, Base
from .models import User, Vendor, Product
from .auth import hash_password

Base.metadata.create_all(bind=engine)


def get_or_create_user(db: Session, email: str, password: str, role: str) -> User:
    u = db.query(User).filter(User.email == email).first()
    if u:
        return u
    u = User(email=email, password_hash=hash_password(password), role=role)
    db.add(u)
    db.flush()
    return u


def run():
    db = SessionLocal()
    try:
        customer = get_or_create_user(db, "customer@demo.com", "password", "CUSTOMER")
        seller1 = get_or_create_user(db, "seller1@demo.com", "password", "SELLER")
        seller2 = get_or_create_user(db, "seller2@demo.com", "password", "SELLER")
        partner = get_or_create_user(db, "partner@demo.com", "password", "PARTNER")

        # Vendors MUST have seller_id (not null)
        v1 = db.query(Vendor).filter(Vendor.seller_id == seller1.id).first()
        if not v1:
            v1 = Vendor(
                seller_id=seller1.id,
                name="Spice Hub",
                description="Indian food",
                address="MG Road",
                lat=12.9752,
                lng=77.6050,
                delivery_radius_km=6.0,
            )
            db.add(v1)
            db.flush()

        v2 = db.query(Vendor).filter(Vendor.seller_id == seller2.id).first()
        if not v2:
            v2 = Vendor(
                seller_id=seller2.id,
                name="Burger Bae",
                description="Burgers and fries",
                address="Indiranagar",
                lat=12.9719,
                lng=77.6412,
                delivery_radius_km=7.0,
            )
            db.add(v2)
            db.flush()

        # Products
        def ensure_product(vendor_id: int, name: str, price: float, desc: str = ""):
            p = db.query(Product).filter(Product.vendor_id == vendor_id, Product.name == name).first()
            if p:
                return
            db.add(Product(vendor_id=vendor_id, name=name, price=price, description=desc, is_available=True))

        ensure_product(v1.id, "Paneer Butter Masala", 180, "Creamy paneer curry")
        ensure_product(v1.id, "Butter Naan", 35, "Soft naan")
        ensure_product(v2.id, "Chicken Burger", 160, "Juicy chicken burger")
        ensure_product(v2.id, "French Fries", 90, "Crispy fries")

        db.commit()

        print("Seeded demo users/vendors/products.")
        print("Demo logins:")
        print("  CUSTOMER: customer@demo.com / password")
        print("  SELLER1 : seller1@demo.com / password")
        print("  SELLER2 : seller2@demo.com / password")
        print("  PARTNER : partner@demo.com / password")
    finally:
        db.close()


if __name__ == "__main__":
    run()
