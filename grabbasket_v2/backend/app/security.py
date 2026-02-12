"""Compatibility security wrapper.

The canonical auth helpers live in `app.auth`.

This module exists because some legacy code imports from `app.security`.
"""

from __future__ import annotations

from .auth import (
    get_current_user,
    require_role,
    create_access_token,
    decode_token,
    hash_password,
    verify_password,
)

__all__ = [
    "get_current_user",
    "require_role",
    "create_access_token",
    "decode_token",
    "hash_password",
    "verify_password",
]
