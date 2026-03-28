from __future__ import annotations

import json
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
from .db import engine
from .metrics import metrics
from .routers import ROUTERS


class JsonLogFormatter(logging.Formatter):
    def format(self, record: logging.LogRecord) -> str:
        payload = {
            "level": record.levelname,
            "logger": record.name,
            "message": record.getMessage(),
            "ts": int(time.time() * 1000),
        }
        request_id = getattr(record, "request_id", None)
        if request_id:
            payload["request_id"] = request_id
        return json.dumps(payload, default=str)


def _configure_logging() -> None:
    level = getattr(logging, settings.LOG_LEVEL.upper(), logging.INFO)
    if settings.LOG_JSON:
        handler = logging.StreamHandler()
        handler.setFormatter(JsonLogFormatter())
        root = logging.getLogger()
        root.handlers = [handler]
        root.setLevel(level)
        return
    logging.basicConfig(level=level)


_configure_logging()
logger = logging.getLogger("grabbasket")

app = FastAPI(
    title="Grabbasket API",
    version="1.1.0",
)


def _build_release_readiness_payload() -> dict:
    report = settings.release_readiness_report
    return {
        "env": settings.APP_ENV,
        "errors": list(report.get("errors") or []),
        "warnings": list(report.get("warnings") or []),
        "components": dict(report.get("components") or {}),
    }


@app.on_event("startup")
def _startup() -> None:
    readiness = _build_release_readiness_payload()

    for warning in readiness["warnings"]:
        logger.warning("Release readiness warning: %s", warning)

    if readiness["errors"]:
        logger.error("Release readiness errors: %s", readiness["errors"])
    else:
        logger.info("Release readiness OK for env=%s", settings.APP_ENV)


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
    metrics.incr('http.requests_total')
    response = None
    try:
        response = await call_next(request)
    except Exception:
        metrics.incr('http.errors_total')
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
        metrics.observe('http.request_latency_ms', dur_ms)
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
    readiness = _build_release_readiness_payload()
    return {
        "ok": not readiness["errors"],
        "message": "GrabBasket backend is live",
        "service": "grabbasket-api",
        "env": settings.APP_ENV,
        "health": "/health",
        "ready": "/ready",
        "docs": "/docs",
    }


@app.get("/health")
def health():
    readiness = _build_release_readiness_payload()
    db_ok = False
    try:
        with engine.connect() as conn:
            conn.execute(text("SELECT 1"))
        db_ok = True
    except Exception:
        logger.exception("Health check failed")

    return {
        "ok": db_ok and not readiness["errors"],
        "env": settings.APP_ENV,
        "database": "ok" if db_ok else "error",
        "release_readiness": readiness,
    }


@app.get("/metrics")
def get_metrics():
    return metrics.snapshot()


@app.get("/ready")
def ready():
    readiness = _build_release_readiness_payload()

    with engine.connect() as conn:
        conn.execute(text("SELECT 1"))

    return {
        "ok": not readiness["errors"],
        "env": settings.APP_ENV,
        "release_readiness": readiness,
    }


for router in ROUTERS:
    app.include_router(router)
