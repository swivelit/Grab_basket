import logging
import uuid
from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from starlette.middleware.base import BaseHTTPMiddleware
from starlette.requests import Request

from .config import settings
from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner, admin, addresses, tracking


logger = logging.getLogger("grabbasket")
logging.basicConfig(level=getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO))


class RequestIdMiddleware(BaseHTTPMiddleware):
    async def dispatch(self, request: Request, call_next):
        rid = request.headers.get("X-Request-ID") or str(uuid.uuid4())
        request.state.request_id = rid
        response = await call_next(request)
        response.headers["X-Request-ID"] = rid
        return response


app = FastAPI(title="Grabbasket API", version="0.2.0")

# CORS (safe defaults for mobile dev; tighten in prod)
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.add_middleware(RequestIdMiddleware)

app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(addresses.router)
app.include_router(tracking.router)
app.include_router(admin.router)


@app.on_event("startup")
def _startup():
    # Ensure tables exist (MVP). For production, switch to Alembic migrations.
    Base.metadata.create_all(bind=engine)


@app.get("/health")
def health():
    return {"ok": True, "env": settings.APP_ENV}
