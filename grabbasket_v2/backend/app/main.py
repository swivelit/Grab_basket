from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from .config import settings
from .routers import auth, vendors, orders, seller, partner

app = FastAPI(title="Grabbasket API", version="0.1.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.cors_origin_list(),
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)

@app.get("/health")
def health():
    return {"ok": True}
