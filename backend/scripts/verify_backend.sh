#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VENV_DIR="${VENV_DIR:-$ROOT_DIR/.venv}"
VERIFY_DB_PATH="${VERIFY_DB_PATH:-$ROOT_DIR/.verify.db}"

if [[ -n "${PYTHON_BIN:-}" ]]; then
  PYTHON_CMD="$PYTHON_BIN"
elif command -v python3.12 >/dev/null 2>&1; then
  PYTHON_CMD="python3.12"
elif command -v python3.11 >/dev/null 2>&1; then
  PYTHON_CMD="python3.11"
else
  PYTHON_CMD="python3"
fi

export APP_ENV=development
export GRABBASKET_DISABLE_DOTENV=1
export DATABASE_URL="sqlite:///$VERIFY_DB_PATH"
export RUN_DB_CREATE_ON_STARTUP=false
export PYTHONDONTWRITEBYTECODE=1

cd "$ROOT_DIR"

if [[ ! -x "$VENV_DIR/bin/python" ]]; then
  "$PYTHON_CMD" -m venv "$VENV_DIR"
fi

if ! "$VENV_DIR/bin/python" - <<'PY' >/dev/null 2>&1
import importlib
for module_name in ("fastapi", "sqlalchemy", "alembic", "jose", "passlib"):
    importlib.import_module(module_name)
PY
then
  "$VENV_DIR/bin/pip" install -r requirements.txt
fi

rm -f "$VERIFY_DB_PATH"
"$VENV_DIR/bin/alembic" -c alembic.ini upgrade head
"$VENV_DIR/bin/python" -m unittest discover -s tests -p "test_*.py" -v
