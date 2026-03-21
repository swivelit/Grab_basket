from __future__ import annotations

import logging
import time
import uuid

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from sqlalchemy import text
from starlette.exceptions import HTTPException as StarletteHTTPException
from starlette.middleware.cors import CORSMiddleware

from .config import settings
from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner, addresses, tracking, admin, me


def _configure_logging() -> None:
    level = getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO)
    logging.basicConfig(level=level)


_configure_logging()
logger = logging.getLogger("grabbasket")

app = FastAPI(
    title="Grabbasket API",
    version="1.1.0",
)


@app.on_event("startup")
def _startup() -> None:
    if settings.RUN_DB_CREATE_ON_STARTUP:
        Base.metadata.create_all(bind=engine)
        logger.warning("Database tables auto-created at startup. Use Alembic migrations outside development.")


@app.exception_handler(StarletteHTTPException)
async def _http_exception_handler(request: Request, exc: StarletteHTTPException):
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "detail": exc.detail,
            "error": {"code": "HTTP_ERROR", "message": str(exc.detail)},
            "request_id": getattr(request.state, "request_id", None),
        },
    )


@app.exception_handler(RequestValidationError)
async def _validation_exception_handler(request: Request, exc: RequestValidationError):
    return JSONResponse(
        status_code=422,
        content={
            "detail": "Invalid request",
            "error": {
                "code": "VALIDATION_ERROR",
                "message": "Invalid request",
                "details": exc.errors(),
            },
            "request_id": getattr(request.state, "request_id", None),
        },
    )


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
    request.state.request_id = req_id

    start = time.time()
    response = None
    try:
        response = await call_next(request)
    except Exception:
        logger.exception("Unhandled error", extra={"request_id": req_id})
        return JSONResponse(
            status_code=500,
            content={
                "detail": "Something went wrong",
                "error": {"code": "INTERNAL_ERROR", "message": "Something went wrong"},
                "request_id": req_id,
            },
        )
    finally:
        dur_ms = int((time.time() - start) * 1000)
        status = getattr(response, "status_code", "?")
        logger.info(
            "%s %s -> %s (%sms)",
            request.method,
            request.url.path,
            status,
            dur_ms,
            extra={"request_id": req_id},
        )

    response.headers["x-request-id"] = req_id
    response.headers["x-response-time-ms"] = str(dur_ms)

    if settings.SECURITY_HEADERS_ENABLED:
        response.headers.setdefault("x-content-type-options", "nosniff")
        response.headers.setdefault("x-frame-options", "DENY")
        response.headers.setdefault("referrer-policy", "same-origin")
        response.headers.setdefault("permissions-policy", "camera=(), microphone=(), geolocation=()")
        response.headers.setdefault("cache-control", "no-store")
        if settings.is_prod:
            response.headers.setdefault("strict-transport-security", "max-age=31536000; includeSubDomains")

    return response


@app.get("/")
def root():
    return {
        "ok": True,
        "message": "GrabBasket backend is live",
        "service": "grabbasket-api",
        "env": settings.APP_ENV,
        "health": "/health",
        "docs": "/docs",
    }


@app.get("/health")
def health():
    db_ok = False
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        db_ok = True
    except Exception:
        logger.exception("Health check failed")

    return {
        "ok": db_ok,
        "env": settings.APP_ENV,
        "database": "ok" if db_ok else "error",
    }


@app.get("/ready")
def ready():
    with engine.connect() as conn:
        conn.execute(text("SELECT 1"))
    return {"ok": True, "env": settings.APP_ENV}


# Routers
app.include_router(auth.router)
app.include_router(vendors.router)
app.include_router(orders.router)
app.include_router(seller.router)
app.include_router(partner.router)
app.include_router(addresses.router)
app.include_router(tracking.router)
app.include_router(me.router)
app.include_router(admin.router)