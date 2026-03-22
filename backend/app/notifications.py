from __future__ import annotations

import json
import logging
import uuid
from datetime import datetime, timezone
from typing import Iterable, Optional

import requests

from .config import settings

logger = logging.getLogger("grabbasket.notifications")

try:  # pragma: no cover
    import firebase_admin
    from firebase_admin import credentials, messaging
except Exception:  # pragma: no cover
    firebase_admin = None
    credentials = None
    messaging = None

_initialized = False
_app = None
EXPO_PUSH_API_URL = "https://exp.host/--/api/v2/push/send"
EXPO_TOKEN_PREFIXES = ("ExpoPushToken[", "ExponentPushToken[")
EXPO_MAX_BATCH_SIZE = 100
FCM_MAX_BATCH_SIZE = 500
REQUEST_TIMEOUT_SECONDS = 10
DEFAULT_CHANNEL_ID = "orders-updates"
APP_ROUTE_BY_TARGET = {
    "consumer": "/(tabs)/account",
    "delivery": "/(delivery)/(tabs)/orders",
    "partner": "/(partner)/(tabs)/orders",
}



def _load_service_account() -> Optional[dict]:
    if settings.FCM_SERVICE_ACCOUNT_JSON:
        try:
            return json.loads(settings.FCM_SERVICE_ACCOUNT_JSON)
        except Exception:
            logger.warning("Invalid FCM_SERVICE_ACCOUNT_JSON")
            return None

    if settings.FCM_SERVICE_ACCOUNT_FILE:
        try:
            with open(settings.FCM_SERVICE_ACCOUNT_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except FileNotFoundError:
            logger.warning("FCM_SERVICE_ACCOUNT_FILE not found")
            return None
        except Exception:
            logger.warning("Failed reading FCM_SERVICE_ACCOUNT_FILE")
            return None
    return None


def _init_fcm() -> None:
    global _initialized, _app
    if _initialized:
        return
    _initialized = True

    if not firebase_admin or not credentials:
        return

    sa = _load_service_account()
    if not sa:
        return

    try:
        cred = credentials.Certificate(sa)
        _app = firebase_admin.initialize_app(cred)
    except Exception:
        _app = None


def _chunked(items: list, size: int) -> list[list]:
    return [items[i:i + size] for i in range(0, len(items), size)]


def _normalize_tokens(tokens: Iterable[str]) -> list[str]:
    seen: set[str] = set()
    normalized: list[str] = []

    for token in tokens:
        value = str(token or "").strip()
        if not value or value in seen:
            continue
        seen.add(value)
        normalized.append(value)

    return normalized


def _stringify_data(data: Optional[dict]) -> dict[str, str]:
    return {str(k): str(v) for k, v in (data or {}).items() if k is not None and v is not None}


def _now_iso() -> str:
    return datetime.now(timezone.utc).isoformat()


def _normalize_order_id(data: dict) -> str | None:
    raw = data.get("order_id") or data.get("orderId") or data.get("order")
    text = str(raw or "").strip()
    return text or None


def _normalize_target_app(value: str | None) -> str | None:
    target = str(value or "").strip().lower()
    if not target:
        return None
    if target in {"customer", "consumer", "user"}:
        return "consumer"
    if target in {"delivery", "rider", "partner_delivery"}:
        return "delivery"
    if target in {"partner", "seller", "merchant", "vendor"}:
        return "partner"
    return None


def build_order_notification_data(
    order_id: int | str,
    *,
    status: str | None = None,
    target_app: str | None = None,
    event_type: str = "order_update",
    deep_link_path: str | None = None,
    extra: Optional[dict] = None,
) -> dict:
    payload = dict(extra or {})
    normalized_target = _normalize_target_app(target_app)

    payload["order_id"] = str(order_id)
    payload["type"] = str(event_type or "order_update").strip() or "order_update"

    if status:
        payload["status"] = str(status).strip().upper()

    if normalized_target:
        payload["target_app"] = normalized_target

    link_path = str(deep_link_path or "").strip()
    if not link_path and normalized_target:
        link_path = APP_ROUTE_BY_TARGET.get(normalized_target, "")
    if link_path:
        payload["deep_link_path"] = link_path

    return payload


def _build_notification_data(data: Optional[dict]) -> dict[str, str]:
    incoming = dict(data or {})
    order_id = _normalize_order_id(incoming)
    target_app = _normalize_target_app(incoming.get("target_app"))

    if order_id and not incoming.get("order_id"):
        incoming["order_id"] = order_id

    if target_app:
        incoming["target_app"] = target_app
        incoming.setdefault("deep_link_path", APP_ROUTE_BY_TARGET.get(target_app, ""))

    incoming.setdefault("notification_id", uuid.uuid4().hex)
    incoming.setdefault("type", "order_update" if order_id else "generic")
    incoming.setdefault("sent_at", _now_iso())
    incoming.setdefault("channel_id", DEFAULT_CHANNEL_ID)

    return _stringify_data(incoming)


def _is_expo_push_token(token: str) -> bool:
    return token.startswith(EXPO_TOKEN_PREFIXES)


def _split_tokens(tokens: list[str]) -> tuple[list[str], list[str]]:
    expo_tokens: list[str] = []
    native_tokens: list[str] = []

    for token in tokens:
        if _is_expo_push_token(token):
            expo_tokens.append(token)
        else:
            native_tokens.append(token)

    return expo_tokens, native_tokens


def _send_expo_push(tokens: list[str], title: str, body: str, data: dict[str, str]) -> None:
    if not tokens:
        return

    channel_id = str(data.get("channel_id") or DEFAULT_CHANNEL_ID).strip() or DEFAULT_CHANNEL_ID
    payload = [
        {
            "to": token,
            "title": title,
            "body": body,
            "data": data,
            "sound": "default",
            "priority": "high",
            "channelId": channel_id,
        }
        for token in tokens
    ]

    headers = {
        "Accept": "application/json",
        "Accept-Encoding": "gzip, deflate",
        "Content-Type": "application/json",
    }

    for batch in _chunked(payload, EXPO_MAX_BATCH_SIZE):
        try:
            response = requests.post(
                EXPO_PUSH_API_URL,
                json=batch,
                headers=headers,
                timeout=REQUEST_TIMEOUT_SECONDS,
            )
            response.raise_for_status()

            response_json = response.json() if response.content else {}
            result_items = response_json.get("data") if isinstance(response_json, dict) else None
            if not isinstance(result_items, list):
                continue

            failures: list[dict] = []
            for item in result_items:
                if isinstance(item, dict) and item.get("status") != "ok":
                    failures.append(item)

            if failures:
                sample = failures[0]
                logger.warning(
                    "[PUSH][EXPO] %s ticket(s) failed for title=%s sample=%s",
                    len(failures),
                    title,
                    {
                        "status": sample.get("status"),
                        "message": sample.get("message"),
                        "details": sample.get("details"),
                    },
                )
        except Exception:
            logger.exception("[PUSH][EXPO][ERROR]")


def _send_fcm_push(tokens: list[str], title: str, body: str, data: dict[str, str]) -> None:
    if not tokens:
        return

    _init_fcm()

    if not _app or not messaging:
        logger.info("[PUSH][DEV][FCM] tokens=%s title=%s", len(tokens), title)
        return

    for batch in _chunked(tokens, FCM_MAX_BATCH_SIZE):
        msg = messaging.MulticastMessage(
            notification=messaging.Notification(title=title, body=body),
            data=data,
            tokens=batch,
        )
        try:
            messaging.send_each_for_multicast(msg)
        except Exception:
            logger.exception("[PUSH][FCM][ERROR]")


def send_push(tokens: Iterable[str], title: str, body: str, data: Optional[dict] = None) -> None:
    normalized_tokens = _normalize_tokens(tokens)
    if not normalized_tokens:
        return

    string_data = _build_notification_data(data)
    expo_tokens, native_tokens = _split_tokens(normalized_tokens)

    _send_expo_push(expo_tokens, title, body, string_data)
    _send_fcm_push(native_tokens, title, body, string_data)