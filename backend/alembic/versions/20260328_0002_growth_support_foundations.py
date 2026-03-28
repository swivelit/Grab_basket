"""growth and support foundations

Revision ID: 20260328_0002
Revises: 20260328_0001
Create Date: 2026-03-28 00:30:00.000000
"""

from __future__ import annotations

revision = '20260328_0002'
down_revision = '20260328_0001'
branch_labels = None
depends_on = None


def upgrade() -> None:
    # Intentionally no-op: schema introduced explicitly in baseline migration.
    return None


def downgrade() -> None:
    return None
