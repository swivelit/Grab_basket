from fastapi import FastAPI
from .database import Base, engine
from .routers import auth, me, vendors, orders, seller, partner, admin

# Create tables (MVP). For production, switch to Alembic migrations.
Base.metadata.create_all(bind=engine)

app = FastAPI(title="Grabbasket API", version="0.2")

app.include_router(auth.router)
app.include_router(me.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(admin.router)


@app.get("/health")
def health():
    return {"ok": True}
