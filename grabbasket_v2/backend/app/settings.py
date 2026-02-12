"""Compatibility wrapper.

We now use `app.config.settings` as the single source of truth.
Keep this module to avoid breaking old imports.
"""

from __future__ import annotations
from .config import settings as _s


class _CompatSettings:
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

    @property
    def firebase_service_account_json(self) -> str | None:
        return _s.FCM_SERVICE_ACCOUNT_JSON

    @property
    def admin_panel_enabled(self) -> bool:
        return True


settings = _CompatSettings()
