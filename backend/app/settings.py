"""Compatibility settings wrapper.

The canonical settings live in `app.config.settings` (UPPER_SNAKE_CASE fields).

This module exists because some older modules still import `app.settings.settings`
with lower_snake_case attribute names.
"""

from __future__ import annotations

from .config import settings as _s


class _CompatSettings:
    # old-style attributes (lowercase)
    @property
    def database_url(self) -> str:
        return _s.DATABASE_URL

    @property
    def jwt_secret(self) -> str:
        return _s.JWT_SECRET

    @property
    def jwt_algorithm(self) -> str:
        return _s.JWT_ALG

    @property
    def jwt_exp_minutes(self) -> int:
        return _s.ACCESS_TOKEN_MINUTES

    # keep for older code; not currently used in the runtime path
    @property
    def base_delivery_fee(self) -> float:
        return 25.0

    @property
    def firebase_service_account_json(self) -> str | None:
        return _s.FCM_SERVICE_ACCOUNT_JSON

    @property
    def admin_panel_enabled(self) -> bool:
        return True


settings = _CompatSettings()
