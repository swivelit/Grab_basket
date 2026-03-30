"""reconcile vendor slug for existing production databases

Revision ID: 20260331_0004
Revises: 20260328_0003
Create Date: 2026-03-31 00:00:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260331_0004"
down_revision = "20260328_0003"
branch_labels = None
depends_on = None


def _has_table(bind, table_name: str) -> bool:
    inspector = sa.inspect(bind)
    return table_name in inspector.get_table_names()


def _has_column(bind, table_name: str, column_name: str) -> bool:
    inspector = sa.inspect(bind)
    return column_name in {col["name"] for col in inspector.get_columns(table_name)}


def _has_index(bind, table_name: str, index_name: str) -> bool:
    inspector = sa.inspect(bind)
    return index_name in {idx["name"] for idx in inspector.get_indexes(table_name)}


def upgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "vendors"):
        return

    if not _has_column(bind, "vendors", "slug"):
        op.add_column(
            "vendors",
            sa.Column("slug", sa.String(length=220), nullable=False, server_default=""),
        )

        # Backfill slug from vendor name where possible.
        op.execute(
            sa.text(
                """
                UPDATE vendors
                SET slug = lower(
                    trim(
                        both '-'
                        from regexp_replace(coalesce(name, ''), '[^a-zA-Z0-9]+', '-', 'g')
                    )
                )
                WHERE coalesce(slug, '') = ''
                """
            )
        )

        # Guarantee non-empty slugs even for blank names.
        op.execute(
            sa.text(
                """
                UPDATE vendors
                SET slug = 'vendor-' || id::text
                WHERE coalesce(slug, '') = ''
                """
            )
        )

        op.alter_column("vendors", "slug", server_default=None)

    if not _has_index(bind, "vendors", "ix_vendors_slug"):
        op.create_index("ix_vendors_slug", "vendors", ["slug"], unique=False)


def downgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "vendors"):
        return

    if _has_index(bind, "vendors", "ix_vendors_slug"):
        op.drop_index("ix_vendors_slug", table_name="vendors")

    if _has_column(bind, "vendors", "slug"):
        op.drop_column("vendors", "slug")