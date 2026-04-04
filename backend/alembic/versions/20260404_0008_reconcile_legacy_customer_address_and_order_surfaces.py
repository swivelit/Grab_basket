"""reconcile legacy customer address and order surfaces

Revision ID: 20260404_0008
Revises: 20260404_0007
Create Date: 2026-04-04 17:45:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260404_0008"
down_revision = "20260404_0007"
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


def _create_customer_addresses() -> None:
    op.create_table(
        "customer_addresses",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("customer_id", sa.Integer(), sa.ForeignKey("users.id"), nullable=False),
        sa.Column("label", sa.String(length=64), nullable=False),
        sa.Column("recipient_name", sa.String(length=120), nullable=False),
        sa.Column("contact_phone", sa.String(length=24), nullable=False),
        sa.Column("line1", sa.Text(), nullable=False),
        sa.Column("line2", sa.Text(), nullable=False),
        sa.Column("landmark", sa.String(length=255), nullable=False),
        sa.Column("city", sa.String(length=64), nullable=False),
        sa.Column("pincode", sa.String(length=16), nullable=False),
        sa.Column("delivery_instructions", sa.Text(), nullable=False),
        sa.Column("lat", sa.Float(), nullable=False),
        sa.Column("lng", sa.Float(), nullable=False),
        sa.Column("is_default", sa.Boolean(), nullable=False),
        sa.Column("created_at", sa.DateTime(), nullable=False),
        sa.Column("updated_at", sa.DateTime(), nullable=False),
    )
    op.create_index("ix_customer_addresses_customer_id", "customer_addresses", ["customer_id"], unique=False)


def _reconcile_customer_addresses(bind) -> None:
    if not _has_table(bind, "customer_addresses"):
        _create_customer_addresses()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("label", sa.String(length=64), nullable=False, server_default="Home"),
        sa.Column("recipient_name", sa.String(length=120), nullable=False, server_default=""),
        sa.Column("contact_phone", sa.String(length=24), nullable=False, server_default=""),
        sa.Column("line1", sa.Text(), nullable=False, server_default=""),
        sa.Column("line2", sa.Text(), nullable=False, server_default=""),
        sa.Column("landmark", sa.String(length=255), nullable=False, server_default=""),
        sa.Column("city", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("pincode", sa.String(length=16), nullable=False, server_default=""),
        sa.Column("delivery_instructions", sa.Text(), nullable=False, server_default=""),
        sa.Column("lat", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("lng", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("is_default", sa.Boolean(), nullable=False, server_default=sa.false()),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("updated_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "customer_addresses", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    if _has_column(bind, "customer_addresses", "name") and _has_column(bind, "customer_addresses", "recipient_name"):
        op.execute(
            sa.text(
                """
                UPDATE customer_addresses
                SET recipient_name = CASE
                    WHEN coalesce(recipient_name, '') = '' THEN coalesce(name, '')
                    ELSE recipient_name
                END
                """
            )
        )

    for source_column in ("phone", "mobile", "mobile_number"):
        if _has_column(bind, "customer_addresses", source_column) and _has_column(bind, "customer_addresses", "contact_phone"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE customer_addresses
                    SET contact_phone = CASE
                        WHEN coalesce(contact_phone, '') = '' THEN coalesce({source_column}, '')
                        ELSE contact_phone
                    END
                    """
                )
            )

    for source_column in ("address", "address_line1"):
        if _has_column(bind, "customer_addresses", source_column) and _has_column(bind, "customer_addresses", "line1"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE customer_addresses
                    SET line1 = CASE
                        WHEN coalesce(line1, '') = '' THEN coalesce({source_column}, '')
                        ELSE line1
                    END
                    """
                )
            )

    if _has_column(bind, "customer_addresses", "address_line2") and _has_column(bind, "customer_addresses", "line2"):
        op.execute(
            sa.text(
                """
                UPDATE customer_addresses
                SET line2 = CASE
                    WHEN coalesce(line2, '') = '' THEN coalesce(address_line2, '')
                    ELSE line2
                END
                """
            )
        )

    for source_column in ("instructions", "notes"):
        if _has_column(bind, "customer_addresses", source_column) and _has_column(bind, "customer_addresses", "delivery_instructions"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE customer_addresses
                    SET delivery_instructions = CASE
                        WHEN coalesce(delivery_instructions, '') = '' THEN coalesce({source_column}, '')
                        ELSE delivery_instructions
                    END
                    """
                )
            )

    for source_column in ("postal_code", "zip_code", "zipcode"):
        if _has_column(bind, "customer_addresses", source_column) and _has_column(bind, "customer_addresses", "pincode"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE customer_addresses
                    SET pincode = CASE
                        WHEN coalesce(pincode, '') = '' THEN coalesce({source_column}, '')
                        ELSE pincode
                    END
                    """
                )
            )

    if _has_column(bind, "customer_addresses", "latitude") and _has_column(bind, "customer_addresses", "lat"):
        op.execute(
            sa.text(
                """
                UPDATE customer_addresses
                SET lat = COALESCE(lat, latitude)
                """
            )
        )

    if _has_column(bind, "customer_addresses", "longitude") and _has_column(bind, "customer_addresses", "lng"):
        op.execute(
            sa.text(
                """
                UPDATE customer_addresses
                SET lng = COALESCE(lng, longitude)
                """
            )
        )

    for source_column in ("is_primary", "default_address"):
        if _has_column(bind, "customer_addresses", source_column) and _has_column(bind, "customer_addresses", "is_default"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE customer_addresses
                    SET is_default = COALESCE(is_default, {source_column})
                    """
                )
            )

    if not _has_index(bind, "customer_addresses", "ix_customer_addresses_customer_id") and _has_column(bind, "customer_addresses", "customer_id"):
        op.create_index("ix_customer_addresses_customer_id", "customer_addresses", ["customer_id"], unique=False)

    for column_name in added_with_defaults:
        op.alter_column("customer_addresses", column_name, server_default=None)


def _create_orders() -> None:
    op.create_table(
        "orders",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("vendor_id", sa.Integer(), sa.ForeignKey("vendors.id"), nullable=False),
        sa.Column("customer_id", sa.Integer(), sa.ForeignKey("users.id"), nullable=False),
        sa.Column("partner_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("status", sa.String(length=64), nullable=False),
        sa.Column("order_source", sa.String(length=32), nullable=False),
        sa.Column("customer_note", sa.Text(), nullable=False),
        sa.Column("seller_note", sa.Text(), nullable=False),
        sa.Column("internal_note", sa.Text(), nullable=False),
        sa.Column("cancellation_reason", sa.Text(), nullable=False),
        sa.Column("delivery_address_id", sa.Integer(), sa.ForeignKey("customer_addresses.id")),
        sa.Column("delivery_lat", sa.Float()),
        sa.Column("delivery_lng", sa.Float()),
        sa.Column("delivery_eta_minutes", sa.Integer()),
        sa.Column("delivery_distance_km", sa.Float()),
        sa.Column("subtotal_amount", sa.Float(), nullable=False),
        sa.Column("delivery_fee", sa.Float(), nullable=False),
        sa.Column("packaging_fee", sa.Float(), nullable=False),
        sa.Column("tax_amount", sa.Float(), nullable=False),
        sa.Column("discount_amount", sa.Float(), nullable=False),
        sa.Column("total_amount", sa.Float(), nullable=False),
        sa.Column("payment_method", sa.String(length=32), nullable=False),
        sa.Column("payment_status", sa.String(length=32), nullable=False),
        sa.Column("payment_provider", sa.String(length=32), nullable=False),
        sa.Column("payment_ref", sa.String(length=128)),
        sa.Column("refund_status", sa.String(length=32), nullable=False),
        sa.Column("refund_ref", sa.String(length=128)),
        sa.Column("idempotency_key", sa.String(length=128)),
        sa.Column("accepted_at", sa.DateTime()),
        sa.Column("assigned_at", sa.DateTime()),
        sa.Column("ready_for_pickup_at", sa.DateTime()),
        sa.Column("picked_up_at", sa.DateTime()),
        sa.Column("delivered_at", sa.DateTime()),
        sa.Column("cancelled_at", sa.DateTime()),
        sa.Column("created_at", sa.DateTime(), nullable=False),
        sa.Column("updated_at", sa.DateTime(), nullable=False),
    )
    op.create_index("ix_orders_customer_created", "orders", ["customer_id", "created_at"], unique=False)
    op.create_index("ix_orders_customer_id", "orders", ["customer_id"], unique=False)
    op.create_index("ix_orders_idempotency_key", "orders", ["idempotency_key"], unique=False)
    op.create_index("ix_orders_partner_created", "orders", ["partner_id", "created_at"], unique=False)
    op.create_index("ix_orders_partner_id", "orders", ["partner_id"], unique=False)
    op.create_index("ix_orders_payment_status_created", "orders", ["payment_status", "created_at"], unique=False)
    op.create_index("ix_orders_status", "orders", ["status"], unique=False)
    op.create_index("ix_orders_status_created", "orders", ["status", "created_at"], unique=False)
    op.create_index("ix_orders_vendor_created", "orders", ["vendor_id", "created_at"], unique=False)
    op.create_index("ix_orders_vendor_id", "orders", ["vendor_id"], unique=False)


def _reconcile_orders(bind) -> None:
    if not _has_table(bind, "orders"):
        _create_orders()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("vendor_id", sa.Integer(), nullable=True),
        sa.Column("customer_id", sa.Integer(), nullable=True),
        sa.Column("partner_id", sa.Integer(), nullable=True),
        sa.Column("status", sa.String(length=64), nullable=False, server_default="CREATED"),
        sa.Column("order_source", sa.String(length=32), nullable=False, server_default="APP"),
        sa.Column("customer_note", sa.Text(), nullable=False, server_default=""),
        sa.Column("seller_note", sa.Text(), nullable=False, server_default=""),
        sa.Column("internal_note", sa.Text(), nullable=False, server_default=""),
        sa.Column("cancellation_reason", sa.Text(), nullable=False, server_default=""),
        sa.Column("delivery_address_id", sa.Integer(), nullable=True),
        sa.Column("delivery_lat", sa.Float(), nullable=True),
        sa.Column("delivery_lng", sa.Float(), nullable=True),
        sa.Column("delivery_eta_minutes", sa.Integer(), nullable=True),
        sa.Column("delivery_distance_km", sa.Float(), nullable=True),
        sa.Column("subtotal_amount", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("delivery_fee", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("packaging_fee", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("tax_amount", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("discount_amount", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("total_amount", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("payment_method", sa.String(length=32), nullable=False, server_default="COD"),
        sa.Column("payment_status", sa.String(length=32), nullable=False, server_default="PENDING"),
        sa.Column("payment_provider", sa.String(length=32), nullable=False, server_default=""),
        sa.Column("payment_ref", sa.String(length=128), nullable=True),
        sa.Column("refund_status", sa.String(length=32), nullable=False, server_default="NOT_APPLICABLE"),
        sa.Column("refund_ref", sa.String(length=128), nullable=True),
        sa.Column("idempotency_key", sa.String(length=128), nullable=True),
        sa.Column("accepted_at", sa.DateTime(), nullable=True),
        sa.Column("assigned_at", sa.DateTime(), nullable=True),
        sa.Column("ready_for_pickup_at", sa.DateTime(), nullable=True),
        sa.Column("picked_up_at", sa.DateTime(), nullable=True),
        sa.Column("delivered_at", sa.DateTime(), nullable=True),
        sa.Column("cancelled_at", sa.DateTime(), nullable=True),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
        sa.Column("updated_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "orders", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    for source_column, target_column in (
        ("subtotal", "subtotal_amount"),
        ("delivery_charge", "delivery_fee"),
        ("packaging_charge", "packaging_fee"),
        ("tax", "tax_amount"),
        ("total", "total_amount"),
    ):
        if _has_column(bind, "orders", source_column) and _has_column(bind, "orders", target_column):
            op.execute(
                sa.text(
                    f"""
                    UPDATE orders
                    SET {target_column} = COALESCE({target_column}, {source_column})
                    """
                )
            )

    for source_column in ("payment_reference", "payment_id"):
        if _has_column(bind, "orders", source_column) and _has_column(bind, "orders", "payment_ref"):
            op.execute(
                sa.text(
                    f"""
                    UPDATE orders
                    SET payment_ref = CASE
                        WHEN payment_ref IS NULL OR payment_ref = '' THEN {source_column}
                        ELSE payment_ref
                    END
                    """
                )
            )

    for index_name, columns in (
        ("ix_orders_customer_created", ["customer_id", "created_at"]),
        ("ix_orders_customer_id", ["customer_id"]),
        ("ix_orders_idempotency_key", ["idempotency_key"]),
        ("ix_orders_partner_created", ["partner_id", "created_at"]),
        ("ix_orders_partner_id", ["partner_id"]),
        ("ix_orders_payment_status_created", ["payment_status", "created_at"]),
        ("ix_orders_status", ["status"]),
        ("ix_orders_status_created", ["status", "created_at"]),
        ("ix_orders_vendor_created", ["vendor_id", "created_at"]),
        ("ix_orders_vendor_id", ["vendor_id"]),
    ):
        if all(_has_column(bind, "orders", column_name) for column_name in columns):
            if not _has_index(bind, "orders", index_name):
                op.create_index(index_name, "orders", columns, unique=False)

    for column_name in added_with_defaults:
        op.alter_column("orders", column_name, server_default=None)


def _create_order_events() -> None:
    op.create_table(
        "order_events",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("order_id", sa.Integer(), sa.ForeignKey("orders.id"), nullable=False),
        sa.Column("status", sa.String(length=64), nullable=False),
        sa.Column("note", sa.Text(), nullable=False),
        sa.Column("actor_user_id", sa.Integer(), sa.ForeignKey("users.id")),
        sa.Column("metadata_json", sa.Text(), nullable=False),
        sa.Column("created_at", sa.DateTime(), nullable=False),
    )
    op.create_index("ix_order_events_order_created", "order_events", ["order_id", "created_at"], unique=False)
    op.create_index("ix_order_events_order_id", "order_events", ["order_id"], unique=False)


def _reconcile_order_events(bind) -> None:
    if not _has_table(bind, "order_events"):
        _create_order_events()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("order_id", sa.Integer(), nullable=True),
        sa.Column("status", sa.String(length=64), nullable=False, server_default="CREATED"),
        sa.Column("note", sa.Text(), nullable=False, server_default=""),
        sa.Column("actor_user_id", sa.Integer(), nullable=True),
        sa.Column("metadata_json", sa.Text(), nullable=False, server_default=""),
        sa.Column("created_at", sa.DateTime(), nullable=False, server_default=sa.text("CURRENT_TIMESTAMP")),
    ):
        added = _add_column_if_missing(bind, "order_events", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    if _has_column(bind, "order_events", "message") and _has_column(bind, "order_events", "note"):
        op.execute(
            sa.text(
                """
                UPDATE order_events
                SET note = CASE
                    WHEN coalesce(note, '') = '' THEN coalesce(message, '')
                    ELSE note
                END
                """
            )
        )

    if _has_column(bind, "order_events", "meta_json") and _has_column(bind, "order_events", "metadata_json"):
        op.execute(
            sa.text(
                """
                UPDATE order_events
                SET metadata_json = CASE
                    WHEN coalesce(metadata_json, '') = '' THEN coalesce(meta_json, '')
                    ELSE metadata_json
                END
                """
            )
        )

    for index_name, columns in (
        ("ix_order_events_order_created", ["order_id", "created_at"]),
        ("ix_order_events_order_id", ["order_id"]),
    ):
        if all(_has_column(bind, "order_events", column_name) for column_name in columns):
            if not _has_index(bind, "order_events", index_name):
                op.create_index(index_name, "order_events", columns, unique=False)

    for column_name in added_with_defaults:
        op.alter_column("order_events", column_name, server_default=None)


def _create_order_items() -> None:
    op.create_table(
        "order_items",
        sa.Column("id", sa.Integer(), primary_key=True, nullable=False),
        sa.Column("order_id", sa.Integer(), sa.ForeignKey("orders.id"), nullable=False),
        sa.Column("product_id", sa.Integer(), sa.ForeignKey("products.id"), nullable=False),
        sa.Column("name_snapshot", sa.String(length=200), nullable=False),
        sa.Column("price_snapshot", sa.Float(), nullable=False),
        sa.Column("image_snapshot", sa.String(length=2048), nullable=False),
        sa.Column("unit_snapshot", sa.String(length=64), nullable=False),
        sa.Column("sku_snapshot", sa.String(length=64), nullable=False),
        sa.Column("qty", sa.Integer(), nullable=False),
        sa.Column("line_total_amount", sa.Float(), nullable=False),
        sa.Column("variant_snapshot", sa.Text(), nullable=False),
    )
    op.create_index("ix_order_items_order_id", "order_items", ["order_id"], unique=False)
    op.create_index("ix_order_items_product_id", "order_items", ["product_id"], unique=False)


def _reconcile_order_items(bind) -> None:
    if not _has_table(bind, "order_items"):
        _create_order_items()
        return

    added_with_defaults: list[str] = []
    for column in (
        sa.Column("order_id", sa.Integer(), nullable=True),
        sa.Column("product_id", sa.Integer(), nullable=True),
        sa.Column("name_snapshot", sa.String(length=200), nullable=False, server_default=""),
        sa.Column("price_snapshot", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("image_snapshot", sa.String(length=2048), nullable=False, server_default=""),
        sa.Column("unit_snapshot", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("sku_snapshot", sa.String(length=64), nullable=False, server_default=""),
        sa.Column("qty", sa.Integer(), nullable=False, server_default=sa.text("1")),
        sa.Column("line_total_amount", sa.Float(), nullable=False, server_default=sa.text("0")),
        sa.Column("variant_snapshot", sa.Text(), nullable=False, server_default=""),
    ):
        added = _add_column_if_missing(bind, "order_items", column)
        if added and column.server_default is not None:
            added_with_defaults.append(column.name)

    for source_column, target_column in (
        ("name", "name_snapshot"),
        ("price", "price_snapshot"),
        ("image_url", "image_snapshot"),
        ("unit", "unit_snapshot"),
        ("sku", "sku_snapshot"),
        ("quantity", "qty"),
        ("total", "line_total_amount"),
        ("variant", "variant_snapshot"),
    ):
        if _has_column(bind, "order_items", source_column) and _has_column(bind, "order_items", target_column):
            if target_column in {"price_snapshot", "qty", "line_total_amount"}:
                op.execute(sa.text(f"UPDATE order_items SET {target_column} = COALESCE({target_column}, {source_column})"))
            else:
                op.execute(
                    sa.text(
                        f"""
                        UPDATE order_items
                        SET {target_column} = CASE
                            WHEN coalesce({target_column}, '') = '' THEN coalesce({source_column}, '')
                            ELSE {target_column}
                        END
                        """
                    )
                )

    for index_name, columns in (
        ("ix_order_items_order_id", ["order_id"]),
        ("ix_order_items_product_id", ["product_id"]),
    ):
        if all(_has_column(bind, "order_items", column_name) for column_name in columns):
            if not _has_index(bind, "order_items", index_name):
                op.create_index(index_name, "order_items", columns, unique=False)

    for column_name in added_with_defaults:
        op.alter_column("order_items", column_name, server_default=None)


def upgrade() -> None:
    bind = op.get_bind()

    if not _has_table(bind, "users"):
        return

    _reconcile_customer_addresses(bind)
    _reconcile_orders(bind)
    _reconcile_order_events(bind)
    _reconcile_order_items(bind)


def downgrade() -> None:
    return