"""reconcile vendor storefront columns for legacy stamped databases

Revision ID: 20260331_0005
Revises: 20260331_0004
Create Date: 2026-03-31 00:30:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260331_0005"
down_revision = "20260331_0004"
branch_labels = None
depends_on = None


def _has_table(bind, table_name: str) -> bool:
    inspector = sa.inspect(bind)
    return table_name in inspector.get_table_names()


def _has_column(bind, table_name: str, column_name: str) -> bool:
    inspector = sa.inspect(bind)
    return column_name in {col["name"] for col in inspector.get_columns(table_name)}


def _add_column_if_missing(bind, table_name: str, column: sa.Column) -> bool:
    if _has_column(bind, table_name, column.name):
        return False

    op.add_column(table_name, column)
    return True


def upgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "vendors"):
        return

    added_with_defaults: list[str] = []

    columns_to_reconcile = [
        sa.Column("logo_image_url", sa.String(length=2048), nullable=False, server_default=""),
        sa.Column("cover_image_url", sa.String(length=2048), nullable=False, server_default=""),
        sa.Column("banner_image_url", sa.String(length=2048), nullable=False, server_default=""),
        sa.Column("cuisine_tags", sa.Text(), nullable=False, server_default=""),
        sa.Column("price_bucket", sa.String(length=8), nullable=False, server_default=""),
        sa.Column("support_phone", sa.String(length=24), nullable=False, server_default=""),
        sa.Column("support_email", sa.String(length=255), nullable=False, server_default=""),
        sa.Column("gstin", sa.String(length=32), nullable=False, server_default=""),
        sa.Column("lat", sa.Float(), nullable=True),
        sa.Column("lng", sa.Float(), nullable=True),
        sa.Column("delivery_radius_km", sa.Float(), nullable=False, server_default="5.0"),
        sa.Column("min_order_amount", sa.Float(), nullable=False, server_default="0"),
        sa.Column("packaging_fee", sa.Float(), nullable=False, server_default="0"),
        sa.Column("estimated_delivery_time_min", sa.Integer(), nullable=False, server_default="30"),
        sa.Column("avg_prep_time_min", sa.Integer(), nullable=False, server_default="15"),
        sa.Column("avg_rating", sa.Float(), nullable=False, server_default="0"),
        sa.Column("total_ratings", sa.Integer(), nullable=False, server_default="0"),
        sa.Column("is_accepting_orders", sa.Boolean(), nullable=False, server_default=sa.true()),
        sa.Column("is_busy", sa.Boolean(), nullable=False, server_default=sa.false()),
        sa.Column("accepts_cod", sa.Boolean(), nullable=False, server_default=sa.true()),
        sa.Column("open_time", sa.Time(), nullable=True),
        sa.Column("close_time", sa.Time(), nullable=True),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("updated_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ]

    for column in columns_to_reconcile:
        added = _add_column_if_missing(bind, "vendors", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    # Backfill storefront media fields from legacy columns when available.
    if _has_column(bind, "vendors", "image_url"):
        op.execute(
            sa.text(
                """
                UPDATE vendors
                SET
                  cover_image_url = CASE
                    WHEN coalesce(cover_image_url, '') = '' THEN coalesce(image_url, '')
                    ELSE cover_image_url
                  END,
                  banner_image_url = CASE
                    WHEN coalesce(banner_image_url, '') = '' THEN coalesce(image_url, '')
                    ELSE banner_image_url
                  END,
                  logo_image_url = CASE
                    WHEN coalesce(logo_image_url, '') = '' THEN coalesce(image_url, '')
                    ELSE logo_image_url
                  END
                """
            )
        )

    if _has_column(bind, "vendors", "logo_url"):
        op.execute(
            sa.text(
                """
                UPDATE vendors
                SET
                  logo_image_url = CASE
                    WHEN coalesce(logo_image_url, '') = '' THEN coalesce(logo_url, '')
                    ELSE logo_image_url
                  END
                """
            )
        )

    # Remove temporary defaults from newly added not-null columns.
    for column_name in added_with_defaults:
        op.alter_column("vendors", column_name, server_default=None)


def downgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "vendors"):
        return

    # Reconcile migration is intentionally conservative on downgrade.
    # Dropping these columns could destroy production data, so we leave them in place.
    return