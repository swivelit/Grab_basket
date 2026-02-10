from sqlalchemy.orm import Session
from decimal import Decimal
from .database import SessionLocal, engine
from .models import Base, Vendor, Product

def run():
    Base.metadata.create_all(bind=engine)
    db: Session = SessionLocal()
    try:
        if db.query(Vendor).count() > 0:
            print("Seed skipped: vendors already exist")
            return

        v1 = Vendor(name="Spice Hub", description="Indian food", address="MG Road")
        v2 = Vendor(name="Burger Bae", description="Burgers and fries", address="Indiranagar")
        db.add_all([v1, v2])
        db.flush()

        db.add_all([
            Product(vendor_id=v1.id, name="Paneer Butter Masala", description="Creamy paneer curry", price=Decimal("220.00")),
            Product(vendor_id=v1.id, name="Butter Naan", description="Soft naan", price=Decimal("45.00")),
            Product(vendor_id=v2.id, name="Classic Chicken Burger", description="Grilled chicken patty", price=Decimal("180.00")),
            Product(vendor_id=v2.id, name="French Fries", description="Crispy fries", price=Decimal("99.00")),
        ])
        db.commit()
        print("Seeded demo vendors/products.")
    finally:
        db.close()

if __name__ == "__main__":
    run()
