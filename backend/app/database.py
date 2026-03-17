"""Compatibility DB wrapper.

The canonical SQLAlchemy setup lives in `app.db`.

This module exists to keep older imports working:
`from app.database import get_db, Base, engine, SessionLocal`
"""

from __future__ import annotations

from .db import Base, engine, SessionLocal, get_db

__all__ = ["Base", "engine", "SessionLocal", "get_db"]
