from __future__ import annotations

import asyncio
import json
import time
from datetime import timedelta

from fastapi import APIRouter, Depends, HTTPException, Query, Request
from fastapi.responses import StreamingResponse
from pydantic import BaseModel, Field
from sqlalchemy.orm import Session

from ..auth import get_current_user, require_role
from ..db import get_db
from ..metrics import metrics
from ..models import AsyncJob, Order, OrderEvent, PartnerLocation, User, Vendor, WebhookDelivery
from ..time import coerce_utc, utc_now
from ..utils.geo import haversine_km

router = APIRouter(prefix="/platform", tags=["platform"])

_ROUTE_CACHE: dict[str, tuple[float, dict]] = {}
_ROUTE_RL: dict[str, list[float]] = {}


class DispatchRecalculationIn(BaseModel):
    order_id: int
    prep_variance_minutes: int = Field(default=0, ge=-30, le=120)
    traffic_multiplier: float = Field(default=1.0, ge=0.4, le=3.0)


class EnqueueJobIn(BaseModel):
    queue_name: str = Field(min_length=2, max_length=64)
    job_type: str = Field(min_length=2, max_length=64)
    payload: dict = Field(default_factory=dict)
    delay_seconds: int = Field(default=0, ge=0, le=86400)


class WebhookIngestIn(BaseModel):
    provider: str = Field(min_length=2, max_length=32)
    event_id: str = Field(min_length=4, max_length=128)
    event_type: str = Field(default="", max_length=64)
    signature_hash: str = Field(default="", max_length=128)
    payload: dict = Field(default_factory=dict)


class RouteIntelligenceIn(BaseModel):
    order_id: int
    rider_lat: float | None = None
    rider_lng: float | None = None
    traffic_multiplier: float = Field(default=1.0, ge=0.4, le=3.0)


def _route_key(body: RouteIntelligenceIn) -> str:
    return f"{body.order_id}:{round(body.rider_lat or 0, 5)}:{round(body.rider_lng or 0, 5)}:{body.traffic_multiplier}"


def _check_route_rate_limit(user: User, period_seconds: int = 60, limit: int = 60) -> None:
    now = time.time()
    key = f"{user.id}"
    rows = [ts for ts in _ROUTE_RL.get(key, []) if ts >= now - period_seconds]
    if len(rows) >= limit:
        raise HTTPException(status_code=429, detail="Rate limit exceeded for route intelligence")
    rows.append(now)
    _ROUTE_RL[key] = rows


@router.get("/orders/{order_id}/timeline/stream")
async def order_timeline_sse(
    request: Request,
    order_id: int,
    since_id: int = 0,
    heartbeat_seconds: float = Query(default=10.0, ge=3.0, le=30.0),
    poll_seconds: float = Query(default=1.0, ge=0.25, le=10.0),
    max_events: int = Query(default=0, ge=0, le=100),
    db: Session = Depends(get_db),
    user: User = Depends(get_current_user),
):
    order = db.query(Order).filter(Order.id == order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")

    allowed = {order.customer_id, order.partner_id}
    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    if vendor:
        allowed.add(vendor.seller_id)

    if user.role != "ADMIN" and user.id not in allowed:
        raise HTTPException(status_code=403, detail="Forbidden")

    async def gen():
        cursor = since_id
        emitted = 0
        metrics.incr("sse.active_connections", 1)
        last_heartbeat = time.monotonic()
        yield "retry: 5000\n\n"
        try:
            while True:
                if await request.is_disconnected():
                    break

                db.expire_all()
                rows = (
                    db.query(OrderEvent)
                    .filter(OrderEvent.order_id == order_id, OrderEvent.id > cursor)
                    .order_by(OrderEvent.id.asc())
                    .limit(100)
                    .all()
                )
                for row in rows:
                    cursor = max(cursor, row.id)
                    payload = {
                        "id": row.id,
                        "status": row.status,
                        "note": row.note,
                        "created_at": row.created_at.isoformat(),
                        "actor_user_id": row.actor_user_id,
                        "metadata_json": row.metadata_json,
                    }
                    yield f"id: {row.id}\nevent: order.timeline\ndata: {json.dumps(payload)}\n\n"
                    emitted += 1
                    if max_events and emitted >= max_events:
                        return

                now = time.monotonic()
                if now - last_heartbeat >= heartbeat_seconds:
                    yield "event: heartbeat\ndata: {}\n\n"
                    emitted += 1
                    if max_events and emitted >= max_events:
                        return
                    last_heartbeat = now
                await asyncio.sleep(poll_seconds)
        finally:
            metrics.incr("sse.active_connections", -1)

    return StreamingResponse(gen(), media_type="text/event-stream")


@router.post("/dispatch/route-intelligence")
def route_intelligence(
    body: RouteIntelligenceIn,
    db: Session = Depends(get_db),
    user: User = Depends(require_role("ADMIN", "SELLER", "PARTNER")),
):
    _check_route_rate_limit(user)
    cache_key = _route_key(body)
    now = time.time()
    cached = _ROUTE_CACHE.get(cache_key)
    if cached and cached[0] >= now:
        return {"ok": True, "cached": True, **cached[1]}

    order = db.query(Order).filter(Order.id == body.order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.delivery_lat is None or order.delivery_lng is None:
        raise HTTPException(status_code=400, detail="Order delivery location missing")

    rider_lat = body.rider_lat
    rider_lng = body.rider_lng
    if rider_lat is None or rider_lng is None:
        latest = (
            db.query(PartnerLocation)
            .filter(PartnerLocation.partner_id == order.partner_id)
            .order_by(PartnerLocation.id.desc())
            .first()
        )
        if not latest:
            raise HTTPException(status_code=409, detail="No rider location available")
        rider_lat, rider_lng = latest.lat, latest.lng

    distance_km = haversine_km(float(rider_lat), float(rider_lng), float(order.delivery_lat), float(order.delivery_lng))
    speed_kmph = max(8.0, 22.0 / body.traffic_multiplier)
    eta_minutes = max(3, int(round((distance_km / speed_kmph) * 60)))

    geometry = [
        {"latitude": float(rider_lat), "longitude": float(rider_lng)},
        {"latitude": float(order.delivery_lat), "longitude": float(order.delivery_lng)},
    ]
    payload = {
        "order_id": order.id,
        "distance_km": round(distance_km, 3),
        "eta_minutes": eta_minutes,
        "polyline_points": geometry,
        "provider": "internal_haversine_v1",
    }
    _ROUTE_CACHE[cache_key] = (now + 45, payload)
    return {"ok": True, "cached": False, **payload}


@router.post("/dispatch/recalculate")
def recalculate_route_and_eta(
    body: DispatchRecalculationIn,
    db: Session = Depends(get_db),
    user: User = Depends(require_role("ADMIN", "SELLER", "PARTNER")),
):
    order = db.query(Order).filter(Order.id == body.order_id).first()
    if not order:
        raise HTTPException(status_code=404, detail="Order not found")
    if order.partner_id is None or order.delivery_lat is None or order.delivery_lng is None:
        raise HTTPException(status_code=400, detail="Order is not dispatch-eligible yet")

    partner_location = (
        db.query(PartnerLocation)
        .filter(PartnerLocation.partner_id == order.partner_id)
        .order_by(PartnerLocation.id.desc())
        .first()
    )
    if not partner_location:
        raise HTTPException(status_code=409, detail="No partner location available")

    distance_km = haversine_km(
        partner_location.lat,
        partner_location.lng,
        float(order.delivery_lat),
        float(order.delivery_lng),
    )
    baseline_speed_kmph = 20.0
    travel_minutes = max(3, int(round((distance_km / baseline_speed_kmph) * 60 * body.traffic_multiplier)))
    prep_base = 0
    vendor = db.query(Vendor).filter(Vendor.id == order.vendor_id).first()
    if vendor:
        prep_base = int(vendor.avg_prep_time_min or 0)

    eta_minutes = max(5, prep_base + body.prep_variance_minutes + travel_minutes)
    order.delivery_distance_km = round(distance_km, 2)
    order.delivery_eta_minutes = eta_minutes

    db.add(
        OrderEvent(
            order_id=order.id,
            status="ETA_RECALCULATED",
            note=f"ETA recalculated to {eta_minutes} min (traffic={body.traffic_multiplier}, prep_variance={body.prep_variance_minutes})",
            actor_user_id=user.id,
            metadata_json=json.dumps(
                {
                    "distance_km": round(distance_km, 2),
                    "traffic_multiplier": body.traffic_multiplier,
                    "prep_variance_minutes": body.prep_variance_minutes,
                }
            ),
        )
    )
    db.commit()

    return {
        "ok": True,
        "order_id": order.id,
        "delivery_distance_km": order.delivery_distance_km,
        "delivery_eta_minutes": order.delivery_eta_minutes,
    }


@router.post("/dispatch/stale-location-scan")
def stale_location_scan(
    stale_after_seconds: int = 180,
    db: Session = Depends(get_db),
    user: User = Depends(require_role("ADMIN")),
):
    cutoff = utc_now() - timedelta(seconds=max(60, min(stale_after_seconds, 3600)))
    active_orders = (
        db.query(Order)
        .filter(Order.partner_id.isnot(None), Order.status.in_(["ASSIGNED_TO_PARTNER", "READY_FOR_PICKUP", "PICKED_UP"]))
        .all()
    )

    flagged = []
    for order in active_orders:
        latest = (
            db.query(PartnerLocation)
            .filter(PartnerLocation.partner_id == order.partner_id)
            .order_by(PartnerLocation.id.desc())
            .first()
        )
        latest_created_at = coerce_utc(latest.created_at) if latest else None
        if not latest_created_at or latest_created_at < cutoff:
            flagged.append(order.id)
            db.add(
                OrderEvent(
                    order_id=order.id,
                    status="LOCATION_STALE_ESCALATED",
                    note="Partner location is stale; queued reassignment/escalation",
                    actor_user_id=user.id,
                    metadata_json=json.dumps({"stale_after_seconds": stale_after_seconds}),
                )
            )
            db.add(
                AsyncJob(
                    queue_name="dispatch",
                    job_type="partner_reassignment",
                    payload_json=json.dumps({"order_id": order.id, "reason": "stale_location"}),
                )
            )

    db.commit()
    return {"ok": True, "flagged_order_ids": flagged, "count": len(flagged)}


@router.post("/jobs/enqueue")
def enqueue_job(
    body: EnqueueJobIn,
    db: Session = Depends(get_db),
    user: User = Depends(require_role("ADMIN", "SELLER")),
):
    run_after = utc_now() + timedelta(seconds=body.delay_seconds)
    job = AsyncJob(
        queue_name=body.queue_name,
        job_type=body.job_type,
        payload_json=json.dumps(body.payload),
        run_after=run_after,
    )
    db.add(job)
    db.commit()
    db.refresh(job)
    return {"ok": True, "job_id": job.id, "queue": job.queue_name, "run_after": job.run_after.isoformat()}


@router.post("/webhooks/ingest")
def ingest_webhook(
    body: WebhookIngestIn,
    db: Session = Depends(get_db),
    user: User = Depends(require_role("ADMIN")),
):
    existing = (
        db.query(WebhookDelivery)
        .filter(WebhookDelivery.provider == body.provider, WebhookDelivery.event_id == body.event_id)
        .first()
    )
    if existing:
        existing.replay_count += 1
        if existing.replay_count >= 3 and existing.status != "DEAD_LETTER":
            existing.status = "DEAD_LETTER"
            existing.dead_lettered_at = utc_now()
            existing.error_message = "Repeated replay detected"
        metrics.incr("webhook.duplicate_total")
        db.commit()
        return {"ok": True, "duplicate": True, "status": existing.status, "replay_count": existing.replay_count}

    delivery = WebhookDelivery(
        provider=body.provider,
        event_id=body.event_id,
        event_type=body.event_type,
        signature_hash=body.signature_hash,
        payload_json=json.dumps(body.payload),
        status="PROCESSED",
        processed_at=utc_now(),
    )
    db.add(delivery)
    metrics.incr("webhook.processed_total")
    db.commit()
    db.refresh(delivery)
    return {"ok": True, "duplicate": False, "delivery_id": delivery.id, "status": delivery.status}
