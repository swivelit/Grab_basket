"""production hardening foundations

Revision ID: 20260328_0003
Revises: 20260328_0002
Create Date: 2026-03-28 01:45:00.000000
"""

from __future__ import annotations

from alembic import op

from app.db import Base
from app import models  # noqa: F401  Ensures metadata is populated.

# revision identifiers, used by Alembic.
revision = '20260328_0003'
down_revision = '20260328_0002'
branch_labels = None
depends_on = None


def upgrade() -> None:
    bind = op.get_bind()
    Base.metadata.create_all(bind=bind)


def downgrade() -> None:
    bind = op.get_bind()
    Base.metadata.drop_all(bind=bind)
