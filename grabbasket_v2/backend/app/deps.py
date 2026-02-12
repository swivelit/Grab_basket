"""Compatibility dependencies."""

from __future__ import annotations

from .db import get_db
from .auth import get_current_user, require_role, decode_token

__all__ = ["get_db", "get_current_user", "require_role", "decode_token"]
