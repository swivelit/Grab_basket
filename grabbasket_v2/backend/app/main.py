from __future__ import annotations

import logging
import time
import uuid

from fastapi import FastAPI, Request
from fastapi.responses import JSONResponse
from starlette.middleware.cors import CORSMiddleware

from .config import settings
from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner, addresses, tracking, admin


def _configure_logging() -> None:
    level = getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO)
    logging.getLogger().setLevel(level)


_configure_logging()
logger = logging.getLogger("grabbasket")

app = FastAPI(title="Grabbasket API")


@app.on_event("startup")
def _startup() -> None:
    # MVP: auto-create tables
    # Production: use Alembic migrations
    Base.metadata.create_all(bind=engine)


allow_origins = settings.CORS_ORIGINS
app.add_middleware(
    CORSMiddleware,
    allow_origins=allow_origins,
    allow_credentials=True,
    allow_methods=["*"] if allow_origins == ["*"] else ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allow_headers=["*"],
)


@app.middleware("http")
async def request_context(request: Request, call_next):
    req_id = request.headers.get("x-request-id") or str(uuid.uuid4())
    start = time.time()
    response = None
    try:
        response = await call_next(request)
    except Exception:
        logger.exception("Unhandled error", extra={"request_id": req_id})
        return JSONResponse(
            status_code=500,
            content={"error": {"code": "INTERNAL_ERROR", "message": "Something went wrong"}, "request_id": req_id},
        )
    finally:
        dur_ms = int((time.time() - start) * 1000)
        status = getattr(response, "status_code", "?")
        logger.info("%s %s -> %s (%sms)", request.method, request.url.path, status, dur_ms, extra={"request_id": req_id})

    response.headers["x-request-id"] = req_id
    return response


# Routers
app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(addresses.router)
app.include_router(tracking.router)
app.include_router(admin.router)


@app.get("/health")
def health():
    return {"ok": True, "env": settings.APP_ENV}
