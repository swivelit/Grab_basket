"""reconcile auth security tables for legacy stamped databases

Revision ID: 20260404_0006
Revises: 20260331_0005
Create Date: 2026-04-04 00:00:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260404_0006"
down_revision = "20260331_0005"
branch_labels = None
depends_on = None


def _has_table(bind, table_name: str) -> bool:
    inspector = sa.inspect(bind)
    return table_name in inspector.get_table_names()


def _has_index(bind, table_name: str, index_name: str) -> bool:
    inspector = sa.inspect(bind)
    return index_name in {idx["name"] for idx in inspector.get_indexes(table_name)}


def _create_auth_challenges() -> None:
    op.create_table(
        "auth_challenges",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("user_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("challenge_type", sa.String(length=32), nullable=False),
        sa.Column("target", sa.String(length=255), nullable=False),
        sa.Column("code_hash", sa.String(length=128), nullable=False),
        sa.Column("status", sa.String(length=24), nullable=False),
        sa.Column("expires_at", sa.DateTime(), nullable=False),
        sa.Column("attempts", sa.Integer(), nullable=False),
        sa.Column("metadata_json", sa.Text(), nullable=False),
        sa.Column("created_at", sa.DateTime(), nullable=False),
        sa.Column("verified_at", sa.DateTime()),
    )
    op.create_index("ix_auth_challenges_challenge_type", "auth_challenges", ["challenge_type"], unique=False)
    op.create_index("ix_auth_challenges_expires_at", "auth_challenges", ["expires_at"], unique=False)
    op.create_index("ix_auth_challenges_status", "auth_challenges", ["status"], unique=False)
    op.create_index("ix_auth_challenges_target", "auth_challenges", ["target"], unique=False)
    op.create_index("ix_auth_challenges_target_type", "auth_challenges", ["target", "challenge_type"], unique=False)
    op.create_index("ix_auth_challenges_user_id", "auth_challenges", ["user_id"], unique=False)


def _create_auth_risk_events() -> None:
    op.create_table(
        "auth_risk_events",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("user_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("email", sa.String(length=255), nullable=False),
        sa.Column("ip_address", sa.String(length=64), nullable=False),
        sa.Column("user_agent", sa.String(length=512), nullable=False),
        sa.Column("event_type", sa.String(length=48), nullable=False),
        sa.Column("risk_score", sa.Integer(), nullable=False),
        sa.Column("reason", sa.Text(), nullable=False),
        sa.Column("blocked", sa.Boolean(), nullable=False),
        sa.Column("created_at", sa.DateTime(), nullable=False),
    )
    op.create_index("ix_auth_risk_events_email", "auth_risk_events", ["email"], unique=False)
    op.create_index("ix_auth_risk_events_event_type", "auth_risk_events", ["event_type"], unique=False)
    op.create_index("ix_auth_risk_events_ip_address", "auth_risk_events", ["ip_address"], unique=False)
    op.create_index("ix_auth_risk_events_user_id", "auth_risk_events", ["user_id"], unique=False)


def _create_user_blocklist() -> None:
    op.create_table(
        "user_blocklist",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("user_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("email", sa.String(length=255), nullable=False),
        sa.Column("device_id", sa.String(length=255), nullable=False),
        sa.Column("reason", sa.Text(), nullable=False),
        sa.Column("active", sa.Boolean(), nullable=False),
        sa.Column("blocked_by_user_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("created_at", sa.DateTime(), nullable=False),
        sa.Column("expires_at", sa.DateTime()),
    )
    op.create_index("ix_user_blocklist_active", "user_blocklist", ["active"], unique=False)
    op.create_index("ix_user_blocklist_blocked_by_user_id", "user_blocklist", ["blocked_by_user_id"], unique=False)
    op.create_index("ix_user_blocklist_device_id", "user_blocklist", ["device_id"], unique=False)
    op.create_index("ix_user_blocklist_email", "user_blocklist", ["email"], unique=False)
    op.create_index("ix_user_blocklist_user_id", "user_blocklist", ["user_id"], unique=False)


def _ensure_auth_challenges_indexes(bind) -> None:
    for index_name, columns in (
        ("ix_auth_challenges_challenge_type", ["challenge_type"]),
        ("ix_auth_challenges_expires_at", ["expires_at"]),
        ("ix_auth_challenges_status", ["status"]),
        ("ix_auth_challenges_target", ["target"]),
        ("ix_auth_challenges_target_type", ["target", "challenge_type"]),
        ("ix_auth_challenges_user_id", ["user_id"]),
    ):
        if not _has_index(bind, "auth_challenges", index_name):
            op.create_index(index_name, "auth_challenges", columns, unique=False)


def _ensure_auth_risk_events_indexes(bind) -> None:
    for index_name, columns in (
        ("ix_auth_risk_events_email", ["email"]),
        ("ix_auth_risk_events_event_type", ["event_type"]),
        ("ix_auth_risk_events_ip_address", ["ip_address"]),
        ("ix_auth_risk_events_user_id", ["user_id"]),
    ):
        if not _has_index(bind, "auth_risk_events", index_name):
            op.create_index(index_name, "auth_risk_events", columns, unique=False)


def _ensure_user_blocklist_indexes(bind) -> None:
    for index_name, columns in (
        ("ix_user_blocklist_active", ["active"]),
        ("ix_user_blocklist_blocked_by_user_id", ["blocked_by_user_id"]),
        ("ix_user_blocklist_device_id", ["device_id"]),
        ("ix_user_blocklist_email", ["email"]),
        ("ix_user_blocklist_user_id", ["user_id"]),
    ):
        if not _has_index(bind, "user_blocklist", index_name):
            op.create_index(index_name, "user_blocklist", columns, unique=False)


def upgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "users"):
        return

    if not _has_table(bind, "auth_challenges"):
        _create_auth_challenges()
    else:
        _ensure_auth_challenges_indexes(bind)

    if not _has_table(bind, "auth_risk_events"):
        _create_auth_risk_events()
    else:
        _ensure_auth_risk_events_indexes(bind)

    if not _has_table(bind, "user_blocklist"):
        _create_user_blocklist()
    else:
        _ensure_user_blocklist_indexes(bind)


def downgrade() -> None:
    return