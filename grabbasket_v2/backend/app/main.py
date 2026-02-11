from fastapi import FastAPI

from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner, admin, addresses

# Ensure tables exist (MVP)
Base.metadata.create_all(bind=engine)

app = FastAPI(title="Grabbasket API")

app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(addresses.router)
app.include_router(admin.router)


@app.get("/health")
def health():
    return {"ok": True}
