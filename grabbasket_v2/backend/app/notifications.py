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
