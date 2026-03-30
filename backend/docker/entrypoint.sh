#!/usr/bin/env sh
set -eu

ACTION="$(python - <<'PY'
from __future__ import annotations

import os
import sys

from sqlalchemy import create_engine, inspect

database_url = os.environ.get("DATABASE_URL")
if not database_url:
    print("upgrade")
    raise SystemExit(0)

try:
    engine = create_engine(database_url)
    with engine.connect() as connection:
        inspector = inspect(connection)
        tables = set(inspector.get_table_names())

    if "alembic_version" in tables:
        print("upgrade")
    elif "users" in tables:
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