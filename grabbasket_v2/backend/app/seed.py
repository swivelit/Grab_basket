# backend/app/seed.py
from __future__ import annotations

from sqlalchemy.orm import Session

from .db import SessionLocal
from .models import User, Vendor, Product
from .auth import hash_password


def get_or_create_user(db: Session, email: str, password: str, role: str) -> User:
    u = db.query(User).filter(User.email == email).first()
    if u:
        return u
    u = User(
        email=email,
        password_hash=hash_password(password),
        role=role,
        is_partner_available=(role == "PARTNER"),
    )
    db.add(u)
    db.flush()  # get id
    return u


def run():
    db = SessionLocal()
    try:
        # --- Users ---
        seller1 = get_or_create_user(db, "seller1@demo.com", "password", "SELLER")
        seller2 = get_or_create_user(db, "seller2@demo.com", "password", "SELLER")

        get_or_create_user(db, "customer@demo.com", "password", "CUSTOMER")
        get_or_create_user(db, "partner@demo.com", "password", "PARTNER")

        # --- Vendors (must have seller_id) ---
        v1 = db.query(Vendor).filter(Vendor.seller_id == seller1.id).first()
        if not v1:
            v1 = Vendor(
                seller_id=seller1.id,
                name="Spice Hub",
                description="Indian food",
                address="MG Road",
                lat=12.9738,
                lng=77.6119,
                delivery_radius_km=5.0,
                is_open=True,
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
                lat=12.9716,
                lng=77.6412,
                delivery_radius_km=4.0,
                is_open=True,
            )
            db.add(v2)
            db.flush()

        # --- Products (idempotent: only seed if vendor has none) ---
        if db.query(Product).filter(Product.vendor_id == v1.id).count() == 0:
            db.add_all(
                [
                    Product(vendor_id=v1.id, name="Masala Dosa", description="Crispy dosa", price=80.0),
                    Product(vendor_id=v1.id, name="Idli Vada", description="2 idli + vada", price=60.0),
                    Product(vendor_id=v1.id, name="Veg Thali", description="Full meals", price=150.0),
                ]
            )

        if db.query(Product).filter(Product.vendor_id == v2.id).count() == 0:
            db.add_all(
                [
                    Product(vendor_id=v2.id, name="Classic Burger", description="Single patty", price=120.0),
                    Product(vendor_id=v2.id, name="Cheese Fries", description="Loaded fries", price=90.0),
                    Product(vendor_id=v2.id, name="Chicken Burger", description="Grilled chicken", price=160.0),
                ]
            )

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
