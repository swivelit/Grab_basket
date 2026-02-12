"""Compatibility wrapper for the SQLAlchemy session.

New code should import from `app.db`.
"""

from .db import Base, engine, SessionLocal, get_db

__all__ = ["Base", "engine", "SessionLocal", "get_db"]
