import json
from typing import Iterable
from .settings import settings

_fcm_ready = False
_fcm_app = None

try:
    import firebase_admin
    from firebase_admin import credentials, messaging
except Exception:  # pragma: no cover
    firebase_admin = None
    credentials = None
    messaging = None


def _init_fcm():
    global _fcm_ready, _fcm_app
    if _fcm_ready:
        return
    _fcm_ready = True

    if not settings.firebase_service_account_json:
        return
    if not firebase_admin:
        return

    try:
        cred_obj = json.loads(settings.firebase_service_account_json)
        cred = credentials.Certificate(cred_obj)
        _fcm_app = firebase_admin.initialize_app(cred)
    except Exception:
        _fcm_app = None


def send_push(tokens: Iterable[str], title: str, body: str, data: dict | None = None) -> None:
    _init_fcm()

    tokens = [t for t in tokens if t]
    if not tokens:
        return

    # If no FCM configured, just print (dev-friendly)
    if not _fcm_app or not messaging:
        print("[PUSH][DEV]", {"tokens": len(tokens), "title": title, "body": body, "data": data or {}})
        return

    msg = messaging.MulticastMessage(
        notification=messaging.Notification(title=title, body=body),
        data={k: str(v) for k, v in (data or {}).items()},
        tokens=tokens,
    )
    try:
        messaging.send_multicast(msg)
    except Exception as e:
        print("[PUSH][ERROR]", str(e))
import json
from typing import Iterable, Optional

import requests
from .config import settings


def _load_service_account() -> Optional[dict]:
    if settings.FCM_SERVICE_ACCOUNT_JSON:
        return json.loads(settings.FCM_SERVICE_ACCOUNT_JSON)

    if settings.FCM_SERVICE_ACCOUNT_FILE:
        try:
            with open(settings.FCM_SERVICE_ACCOUNT_FILE, "r", encoding="utf-8") as f:
                return json.load(f)
        except FileNotFoundError:
            return None

    return None


def send_push(tokens: Iterable[str], title: str, body: str, data: Optional[dict] = None) -> None:
    """
    Simple FCM v1 send stub.
    For production: use google-auth + proper OAuth token minting.
    Here: if no service account provided, we just no-op safely.
    """
    sa = _load_service_account()
    tokens = [t for t in tokens if t]
    if not sa or not tokens:
        # No creds: keep backend working without notifications.
        return

    # NOTE: This is intentionally a safe placeholder.
    # We’ll wire the official OAuth flow when you add real Firebase creds.
    # For now you can verify token registration + call paths.
    return
