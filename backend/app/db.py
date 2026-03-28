from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

try:  # SQLAlchemy 2.x
    from sqlalchemy.orm import DeclarativeBase
except ImportError:  # pragma: no cover - SQLAlchemy 1.4 fallback
    DeclarativeBase = None
    from sqlalchemy.orm import declarative_base

from .config import settings

if DeclarativeBase is not None:
    class Base(DeclarativeBase):
        pass
else:  # pragma: no cover - SQLAlchemy 1.4 fallback
    Base = declarative_base()


engine = create_engine(settings.DATABASE_URL, pool_pre_ping=True)
SessionLocal = sessionmaker(bind=engine, autoflush=False, autocommit=False)


def get_db():
    db = SessionLocal()
    try:
        yield db
    finally:
        db.close()
