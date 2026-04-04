"""reconcile legacy user auth core schema

Revision ID: 20260404_0007
Revises: 20260404_0006
Create Date: 2026-04-04 00:30:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260404_0007"
down_revision = "20260404_0006"
branch_labels = None
depends_on = None


def _has_table(bind, table_name: str) -> bool:
    inspector = sa.inspect(bind)
    return table_name in inspector.get_table_names()


def _has_column(bind, table_name: str, column_name: str) -> bool:
    inspector = sa.inspect(bind)
    return column_name in {column["name"] for column in inspector.get_columns(table_name)}


def _has_index(bind, table_name: str, index_name: str) -> bool:
    inspector = sa.inspect(bind)
    return index_name in {index["name"] for index in inspector.get_indexes(table_name)}


def _add_column_if_missing(bind, table_name: str, column: sa.Column) -> bool:
    if _has_column(bind, table_name, column.name):
        return False
    op.add_column(table_name, column)
    return True


def _create_fcm_tokens() -> None:
    op.create_table(
        "fcm_tokens",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("user_id", sa.Integer(), sa.ForeignKey("users.id"), nullable=False),
        sa.Column("token", sa.String(length=512), nullable=False, unique=True),
        sa.Column("platform", sa.String(length=32), nullable=False),
        sa.Column("app_version", sa.String(length=64), nullable=False),
        sa.Column("device_name", sa.String(length=255), nullable=False),
        sa.Column("device_id", sa.String(length=255), nullable=False),
        sa.Column("last_seen_at", sa.DateTime(), nullable=False),
        sa.Column("created_at", sa.DateTime(), nullable=False),
        sa.UniqueConstraint("token"),
    )
    op.create_index("ix_fcm_tokens_device_id", "fcm_tokens", ["device_id"], unique=False)
    op.create_index("ix_fcm_tokens_user_id", "fcm_tokens", ["user_id"], unique=False)


def _reconcile_fcm_tokens(bind) -> None:
    if not _has_table(bind, "fcm_tokens"):
        _create_fcm_tokens()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("app_version", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("device_name", sa.String(length=255), nullable=False, server_default=""),
        sa.Column("device_id", sa.String(length=255), nullable=False, server_default=""),
        sa.Column("last_seen_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "fcm_tokens", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    if _has_column(bind, "fcm_tokens", "device_id") and not _has_index(bind, "fcm_tokens", "ix_fcm_tokens_device_id"):
        op.create_index("ix_fcm_tokens_device_id", "fcm_tokens", ["device_id"], unique=False)

    if _has_column(bind, "fcm_tokens", "user_id") and not _has_index(bind, "fcm_tokens", "ix_fcm_tokens_user_id"):
        op.create_index("ix_fcm_tokens_user_id", "fcm_tokens", ["user_id"], unique=False)

    for column_name in added_with_defaults:
        op.alter_column("fcm_tokens", column_name, server_default=None)


def _create_refresh_tokens() -> None:
    op.create_table(
        "refresh_tokens",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("user_id", sa.Integer(), sa.ForeignKey("users.id"), nullable=False),
        sa.Column("token_hash", sa.String(length=128), nullable=False, unique=True),
        sa.Column("token_family", sa.String(length=64), nullable=False),
        sa.Column("user_agent", sa.String(length=512), nullable=False),
        sa.Column("ip_address", sa.String(length=64), nullable=False),
        sa.Column("device_id", sa.String(length=255), nullable=False),
        sa.Column("expires_at", sa.DateTime(), nullable=False),
        sa.Column("last_used_at", sa.DateTime()),
        sa.Column("revoked_at", sa.DateTime()),
        sa.Column("created_at", sa.DateTime(), nullable=False),
    )
    op.create_index("ix_refresh_tokens_device_id", "refresh_tokens", ["device_id"], unique=False)
    op.create_index("ix_refresh_tokens_expires_at", "refresh_tokens", ["expires_at"], unique=False)
    op.create_index("ix_refresh_tokens_revoked_at", "refresh_tokens", ["revoked_at"], unique=False)
    op.create_index("ix_refresh_tokens_token_family", "refresh_tokens", ["token_family"], unique=False)
    op.create_index("ix_refresh_tokens_token_hash", "refresh_tokens", ["token_hash"], unique=True)
    op.create_index("ix_refresh_tokens_user_created", "refresh_tokens", ["user_id", "created_at"], unique=False)
    op.create_index("ix_refresh_tokens_user_id", "refresh_tokens", ["user_id"], unique=False)


def _reconcile_refresh_tokens(bind) -> None:
    if not _has_table(bind, "refresh_tokens"):
        _create_refresh_tokens()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("token_family", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("user_agent", sa.String(length=512), nullable=False, server_default=""),
        sa.Column("ip_address", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("device_id", sa.String(length=255), nullable=False, server_default=""),
        sa.Column("expires_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("last_used_at", sa.DateTime(), nullable=True),
        sa.Column("revoked_at", sa.DateTime(), nullable=True),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "refresh_tokens", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    for index_name, columns in (
        ("ix_refresh_tokens_device_id", ["device_id"]),
        ("ix_refresh_tokens_expires_at", ["expires_at"]),
        ("ix_refresh_tokens_revoked_at", ["revoked_at"]),
        ("ix_refresh_tokens_token_family", ["token_family"]),
        ("ix_refresh_tokens_token_hash", ["token_hash"]),
        ("ix_refresh_tokens_user_created", ["user_id", "created_at"]),
        ("ix_refresh_tokens_user_id", ["user_id"]),
    ):
        if all(_has_column(bind, "refresh_tokens", column_name) for column_name in columns):
            if not _has_index(bind, "refresh_tokens", index_name):
                op.create_index(index_name, "refresh_tokens", columns, unique=False)

    for column_name in added_with_defaults:
        op.alter_column("refresh_tokens", column_name, server_default=None)


def _reconcile_users(bind) -> None:
    if not _has_table(bind, "users"):
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("full_name", sa.String(length=120), nullable=False, server_default=""),
        sa.Column("phone", sa.String(length=24), nullable=False, server_default=""),
        sa.Column("avatar_url", sa.String(length=2048), nullable=False, server_default=""),
        sa.Column("is_active", sa.Boolean(), nullable=False, server_default=sa.true()),
        sa.Column("is_partner_available", sa.Boolean(), nullable=False, server_default=sa.false()),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("updated_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "users", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    if _has_column(bind, "users", "name") and _has_column(bind, "users", "full_name"):
        op.execute(
            sa.text(
                """
                UPDATE users
                SET full_name = CASE
                    WHEN coalesce(full_name, '') = '' THEN coalesce(name, '')
                    ELSE full_name
                END
                """
            )
        )

    if _has_column(bind, "users", "mobile") and _has_column(bind, "users", "phone"):
        op.execute(
            sa.text(
                """
                UPDATE users
                SET phone = CASE
                    WHEN coalesce(phone, '') = '' THEN coalesce(mobile, '')
                    ELSE phone
                END
                """
            )
        )

    if _has_column(bind, "users", "mobile_number") and _has_column(bind, "users", "phone"):
        op.execute(
            sa.text(
                """
                UPDATE users
                SET phone = CASE
                    WHEN coalesce(phone, '') = '' THEN coalesce(mobile_number, '')
                    ELSE phone
                END
                """
            )
        )

    if _has_column(bind, "users", "image_url") and _has_column(bind, "users", "avatar_url"):
        op.execute(
            sa.text(
                """
                UPDATE users
                SET avatar_url = CASE
                    WHEN coalesce(avatar_url, '') = '' THEN coalesce(image_url, '')
                    ELSE avatar_url
                END
                """
            )
        )

    if _has_column(bind, "users", "avatar") and _has_column(bind, "users", "avatar_url"):
        op.execute(
            sa.text(
                """
                UPDATE users
                SET avatar_url = CASE
                    WHEN coalesce(avatar_url, '') = '' THEN coalesce(avatar, '')
                    ELSE avatar_url
                END
                """
            )
        )

    for index_name, columns in (
        ("ix_users_email", ["email"]),
        ("ix_users_phone", ["phone"]),
        ("ix_users_role_created", ["role", "created_at"]),
    ):
        if all(_has_column(bind, "users", column_name) for column_name in columns):
            if not _has_index(bind, "users", index_name):
                op.create_index(index_name, "users", columns, unique=False)

    for column_name in added_with_defaults:
        op.alter_column("users", column_name, server_default=None)


def upgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "users"):
        return

    _reconcile_users(bind)
    _reconcile_fcm_tokens(bind)
    _reconcile_refresh_tokens(bind)


def downgrade() -> None:
    return