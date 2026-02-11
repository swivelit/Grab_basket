from sqlalchemy.orm import Session
from .database import SessionLocal, Base, engine
from .models import User, Vendor, Product, Role
from .auth import hash_password


def get_or_create_user(db: Session, email: str, password: str, role: Role) -> User:
    u = db.query(User).filter(User.email == email).first()
    if u:
        return u
    u = User(email=email, password_hash=hash_password(password), role=role)
    db.add(u)
    db.flush()
    return u


def run():
    Base.metadata.create_all(bind=engine)
    db = SessionLocal()
    try:
        customer = get_or_create_user(db, "customer@demo.com", "password", Role.CUSTOMER)
        seller1 = get_or_create_user(db, "seller1@demo.com", "password", Role.SELLER)
        seller2 = get_or_create_user(db, "seller2@demo.com", "password", Role.SELLER)
        partner = get_or_create_user(db, "partner@demo.com", "password", Role.PARTNER)
        admin = get_or_create_user(db, "admin@demo.com", "password", Role.ADMIN)

        db.commit()

        # Vendors tied to sellers (seller_id required)
        v1 = db.query(Vendor).filter(Vendor.seller_id == seller1.id).first()
        if not v1:
            v1 = Vendor(
                seller_id=seller1.id,
                name="Spice Hub",
                description="Indian food",
                address="MG Road",
                lat=12.9716,
                lng=77.5946,
                delivery_radius_km=6.0,
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
                lat=12.9784,
                lng=77.6408,
                delivery_radius_km=6.0,
                is_open=True,
            )
            db.add(v2)
            db.flush()

        db.commit()

        # Products
        def ensure_product(vendor_id: int, name: str, price: float, desc: str):
            p = db.query(Product).filter(Product.vendor_id == vendor_id, Product.name == name).first()
            if p:
                return
            db.add(Product(vendor_id=vendor_id, name=name, price=price, description=desc, is_available=True))

        ensure_product(v1.id, "Paneer Butter Masala", 180.0, "Creamy paneer curry")
        ensure_product(v1.id, "Butter Naan", 35.0, "Soft naan")
        ensure_product(v2.id, "Classic Burger", 160.0, "Juicy patty burger")
        ensure_product(v2.id, "French Fries", 80.0, "Crispy fries")

        db.commit()

        print("Seeded demo users/vendors/products.")
        print("Demo logins:")
        print("  CUSTOMER:", "customer@demo.com / password")
        print("  SELLER1 :", "seller1@demo.com / password")
        print("  SELLER2 :", "seller2@demo.com / password")
        print("  PARTNER :", "partner@demo.com / password")
        print("  ADMIN  :", "admin@demo.com / password")

    finally:
        db.close()


if __name__ == "__main__":
    run()
