#!/usr/bin/env sh
set -eu

alembic upgrade head

if [ "${1:-}" = "worker" ]; then
  exec python -m app.worker
fi

exec uvicorn app.main:app --host 0.0.0.0 --port "${PORT:-8000}"
