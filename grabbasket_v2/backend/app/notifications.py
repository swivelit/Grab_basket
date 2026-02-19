from __future__ import annotations

import json
import logging
from typing import Iterable, Optional

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


def send_push(tokens: Iterable[str], title: str, body: str, data: Optional[dict] = None) -> None:
    tokens = [t for t in tokens if t]
    if not tokens:
        return

    _init_fcm()

    if not _app or not messaging:
        logger.info("[PUSH][DEV] tokens=%s title=%s", len(tokens), title)
        return

    msg = messaging.MulticastMessage(
        notification=messaging.Notification(title=title, body=body),
        data={k: str(v) for k, v in (data or {}).items()},
        tokens=tokens,
    )
    try:
        messaging.send_multicast(msg)
    except Exception:
        logger.exception("[PUSH][ERROR]")
