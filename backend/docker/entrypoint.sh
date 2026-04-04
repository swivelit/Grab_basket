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


def has_table(cur, table_name: str) -> bool:
    cur.execute(
        """
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.tables
            WHERE table_schema = 'public' AND table_name = %s
        )
        """,
        (table_name,),
    )
    return bool(cur.fetchone()[0])


def has_column(cur, table_name: str, column_name: str) -> bool:
    cur.execute(
        """
        SELECT EXISTS (
            SELECT 1
            FROM information_schema.columns
            WHERE table_schema = 'public' AND table_name = %s AND column_name = %s
        )
        """,
        (table_name, column_name),
    )
    return bool(cur.fetchone()[0])


dsn = to_psycopg_dsn(settings.DATABASE_URL)

try:
    with psycopg.connect(dsn) as conn:
        with conn.cursor() as cur:
            has_alembic_version = has_table(cur, "alembic_version")
            has_existing_app_tables = any(
                has_table(cur, table_name)
                for table_name in ("users", "vendors", "products", "orders")
            )

            needs_reconcile = False
            if has_existing_app_tables:
                auth_tables_to_check = (
                    "auth_challenges",
                    "auth_risk_events",
                    "user_blocklist",
                    "refresh_tokens",
                    "fcm_tokens",
                )
                missing_auth_tables = [
                    table_name for table_name in auth_tables_to_check if not has_table(cur, table_name)
                ]

                user_columns_to_check = (
                    "full_name",
                    "phone",
                    "avatar_url",
                    "is_active",
                    "is_partner_available",
                    "created_at",
                    "updated_at",
                )
                missing_user_columns = []
                if has_table(cur, "users"):
                    for column_name in user_columns_to_check:
                        if not has_column(cur, "users", column_name):
                            missing_user_columns.append(column_name)

                vendor_columns_to_check = (
                    "slug",
                    "logo_image_url",
                    "cover_image_url",
                    "banner_image_url",
                    "cuisine_tags",
                    "price_bucket",
                    "support_phone",
                    "support_email",
                    "gstin",
                    "lat",
                    "lng",
                    "delivery_radius_km",
                    "min_order_amount",
                    "packaging_fee",
                    "estimated_delivery_time_min",
                    "avg_prep_time_min",
                    "avg_rating",
                    "total_ratings",
                    "is_accepting_orders",
                    "is_busy",
                    "accepts_cod",
                    "open_time",
                    "close_time",
                    "created_at",
                    "updated_at",
                )
                missing_vendor_columns = []
                if has_table(cur, "vendors"):
                    for column_name in vendor_columns_to_check:
                        if not has_column(cur, "vendors", column_name):
                            missing_vendor_columns.append(column_name)

                needs_reconcile = bool(
                    missing_auth_tables or missing_user_columns or missing_vendor_columns
                )

    if has_alembic_version:
        print("upgrade")
    elif has_existing_app_tables and needs_reconcile:
        print("stamp_reconcile")
    elif has_existing_app_tables:
        print("stamp_head")
    else:
        print("upgrade")
except Exception as exc:
    print(f"Failed to inspect database before migrations: {exc}", file=sys.stderr)
    raise SystemExit(1)
PY
)"

if [ "$ACTION" = "stamp_reconcile" ]; then
  echo "Legacy GrabBasket schema detected without alembic_version; stamping to 20260328_0003 and running reconcile migrations."
  alembic stamp 20260328_0003
  alembic upgrade head
elif [ "$ACTION" = "stamp_head" ]; then
  echo "Existing GrabBasket schema already matches reconciled migrations; stamping database to head."
  alembic stamp head
else
  alembic upgrade head
fi

if [ "${1:-}" = "worker" ]; then
  exec python -m app.worker
fi

exec uvicorn app.main:app --host 0.0.0.0 --port "${PORT:-8000}"