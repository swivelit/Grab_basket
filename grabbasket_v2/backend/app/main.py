from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware

from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner
from .routers import addresses, tracking
from .admin import router as admin_router

Base.metadata.create_all(bind=engine)

app = FastAPI(title="Grabbasket API")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # tighten in prod
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(addresses.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(tracking.router)
app.include_router(admin_router)


@app.get("/health")
def health():
    return {"ok": True}
