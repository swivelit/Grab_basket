from __future__ import annotations

import logging
import time
import uuid

from fastapi import FastAPI, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from starlette.exceptions import HTTPException as StarletteHTTPException
from starlette.middleware.cors import CORSMiddleware

from .config import settings
from .db import Base, engine
from .routers import auth, vendors, orders, seller, partner, admin, addresses, tracking, me

logger = logging.getLogger("grabbasket")
logging.basicConfig(level=getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO))

app = FastAPI(title="Grabbasket API", version="0.2.0")


@app.on_event("startup")
def _startup():
    # Ensure tables exist (MVP). For production, switch to Alembic migrations.
    Base.metadata.create_all(bind=engine)


def _request_id(request: Request) -> str | None:
    return getattr(request.state, "request_id", None)


@app.exception_handler(StarletteHTTPException)
async def _http_exception_handler(request: Request, exc: StarletteHTTPException):
    # Keep FastAPI-compatible 'detail' while also returning a consistent error envelope.
    return JSONResponse(
        status_code=exc.status_code,
        content={
            "detail": exc.detail,
            "error": {"code": "HTTP_ERROR", "message": str(exc.detail)},
            "request_id": _request_id(request),
        },
    )


@app.exception_handler(RequestValidationError)
async def _validation_exception_handler(request: Request, exc: RequestValidationError):
    return JSONResponse(
        status_code=422,
        content={
            "detail": "Invalid request",
            "error": {"code": "VALIDATION_ERROR", "message": "Invalid request", "details": exc.errors()},
            "request_id": _request_id(request),
        },
    )


@app.middleware("http")
async def request_context(request: Request, call_next):
    rid = request.headers.get("x-request-id") or request.headers.get("X-Request-ID") or str(uuid.uuid4())
    request.state.request_id = rid

    start = time.time()
    response = None
    try:
        response = await call_next(request)
        return response
    except Exception:
        logger.exception("Unhandled error", extra={"request_id": rid})
        return JSONResponse(
            status_code=500,
            content={
                "detail": "Something went wrong",
                "error": {"code": "INTERNAL_ERROR", "message": "Something went wrong"},
                "request_id": rid,
            },
        )
    finally:
        dur_ms = int((time.time() - start) * 1000)
        status = getattr(response, "status_code", "ERR")
        logger.info("%s %s -> %s (%sms)", request.method, request.url.path, status, dur_ms, extra={"request_id": rid})


# CORS (safe defaults for mobile dev; tighten in prod)
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.CORS_ORIGINS,
    allow_credentials=True,
    allow_methods=["*"] if settings.CORS_ORIGINS == ["*"] else ["GET", "POST", "PUT", "PATCH", "DELETE", "OPTIONS"],
    allow_headers=["*"],
)

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


@app.get("/health")
def health():
    return {"ok": True, "env": settings.APP_ENV}
