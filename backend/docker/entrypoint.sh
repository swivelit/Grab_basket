#!/usr/bin/env sh
set -eu

ACTION="$(python - <<'PY'
from __future__ import annotations

import sys

from app.config import settings

try:
    import psycopg
except Exception as exc:
    print(f"Failed to import psycopg: {exc}", file=sys.stderr)
    raise SystemExit(1)


def to_psycopg_dsn(url: str) -> str:
    value = (url or "").strip()
    if value.startswith("postgresql+psycopg://"):
        return "postgresql://" + value[len("postgresql+psycopg://"):]
    if value.startswith("postgres://"):
        return "postgresql://" + value[len("postgres://"):]
    return value


dsn = to_psycopg_dsn(settings.DATABASE_URL)

try:
    with psycopg.connect(dsn) as conn:
        with conn.cursor() as cur:
            cur.execute(
                """
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = 'public' AND table_name = 'alembic_version'
                )
                """
            )
            has_alembic_version = bool(cur.fetchone()[0])

            cur.execute(
                """
                SELECT EXISTS (
                    SELECT 1
                    FROM information_schema.tables
                    WHERE table_schema = 'public' AND table_name IN (
                        'users',
                        'vendors',
                        'products',
                        'orders'
                    )
                )
                """
            )
            has_existing_app_tables = bool(cur.fetchone()[0])

    if has_alembic_version:
        print("upgrade")
    elif has_existing_app_tables:
        print("stamp")
    else:
        print("upgrade")
except Exception as exc:
    print(f"Failed to inspect database before migrations: {exc}", file=sys.stderr)
    raise SystemExit(1)
PY
)"

if [ "$ACTION" = "stamp" ]; then
  echo "Existing GrabBasket schema detected without alembic_version; stamping database to head."
  alembic stamp head
else
  alembic upgrade head
fi

if [ "${1:-}" = "worker" ]; then
  exec python -m app.worker
fi

exec uvicorn app.main:app --host 0.0.0.0 --port "${PORT:-8000}"