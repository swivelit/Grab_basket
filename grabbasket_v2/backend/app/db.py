# Simple compatibility wrapper.
# Some parts of the code import from app.db, while the actual implementation is in app.database.

from .database import Base, engine, SessionLocal, get_db

__all__ = ["Base", "engine", "SessionLocal", "get_db"]
