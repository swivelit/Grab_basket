from __future__ import annotations

import json
from typing import Iterable, Optional

from .config import settings

try:
    import firebase_admin
    from firebase_admin import credentials, messaging
except Exception:  # pragma: no cover
    firebase_admin = None
    credentials = None
    messaging = None


_fcm_app = None


def _load_service_account() -> Optional[dict]:
    if settings.FCM_SERVICE_ACCOUNT_JSON:
        try:
            return json.loads(settings.FCM_SERVICE_ACCOUNT_JSON)
        except Exception:
            return None

    if settings.FCM_SERVICE_ACCOUNT_FILE:
        try:
            with open(settings.FCM_SERVICE_ACCOUNT_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except FileNotFoundError:
            return None
        except Exception:
            return None

    return None


def _ensure_fcm_app():
    """Initialize Firebase Admin app once (if credentials are provided)."""
    global _fcm_app

    if _fcm_app is not None:
        return _fcm_app

    sa = _load_service_account()
    if not sa or not firebase_admin or not credentials:
        _fcm_app = False  # mark attempted
        return _fcm_app

    try:
        cred = credentials.Certificate(sa)

        # firebase_admin keeps a global registry; avoid double-init.
        try:
            _fcm_app = firebase_admin.get_app()
        except Exception:
            _fcm_app = firebase_admin.initialize_app(cred)

        return _fcm_app
    except Exception:
        _fcm_app = False
        return _fcm_app


def send_push(tokens: Iterable[str], title: str, body: str, data: Optional[dict] = None) -> None:
    """Send an FCM push to many tokens.

    - If Firebase credentials are not configured, this becomes a safe no-op in prod,
      and a console print in dev (so flows can still be tested).
    """
    tok_list = [t for t in tokens if t]
    if not tok_list:
        return

    app = _ensure_fcm_app()

    # Dev-friendly fallback
    if not app or not messaging:
        if settings.APP_ENV != "prod":
            print("[PUSH][DEV]", {"tokens": len(tok_list), "title": title, "body": body, "data": data or {}})
        return

    msg = messaging.MulticastMessage(
        notification=messaging.Notification(title=title, body=body),
        data={k: str(v) for k, v in (data or {}).items()},
        tokens=tok_list,
    )

    try:
        messaging.send_multicast(msg)
    except Exception as e:
        if settings.APP_ENV != "prod":
            print("[PUSH][ERROR]", str(e))
