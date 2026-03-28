"""growth and support foundations

Revision ID: 20260328_0002
Revises: 20260328_0001
Create Date: 2026-03-28 00:30:00.000000
"""

from __future__ import annotations

from alembic import op

from app.db import Base
from app import models  # noqa: F401  Ensures metadata is populated.

# revision identifiers, used by Alembic.
revision = '20260328_0002'
down_revision = '20260328_0001'
branch_labels = None
depends_on = None


def upgrade() -> None:
    bind = op.get_bind()
    Base.metadata.create_all(bind=bind)


def downgrade() -> None:
    bind = op.get_bind()
    Base.metadata.drop_all(bind=bind)
