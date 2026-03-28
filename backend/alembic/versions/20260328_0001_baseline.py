"""baseline schema

Revision ID: 20260328_0001
Revises:
Create Date: 2026-03-28 00:00:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

from app.db import Base
from app import models  # noqa: F401

revision = '20260328_0001'
down_revision = None
branch_labels = None
depends_on = None


def _clone_column(column: sa.Column) -> sa.Column:
    return column.copy()


def upgrade() -> None:
    metadata = Base.metadata
    for table in metadata.sorted_tables:
        cols = [_clone_column(col) for col in table.columns]
        constraints = [c.copy() for c in list(table.constraints) if not isinstance(c, sa.PrimaryKeyConstraint)]
        op.create_table(table.name, *cols, *constraints)


def downgrade() -> None:
    metadata = Base.metadata
    for table in reversed(metadata.sorted_tables):
        op.drop_table(table.name)
