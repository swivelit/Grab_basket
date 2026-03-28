"""baseline schema

Revision ID: 20260328_0001
Revises:
Create Date: 2026-03-28 00:00:00.000000
"""

from __future__ import annotations

import sqlalchemy as sa
from alembic import op

revision = "20260328_0001"
down_revision = None
branch_labels = None
depends_on = None


def upgrade() -> None:

    op.create_table(
        'async_jobs',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('queue_name', sa.String(length=64), nullable=False),
        sa.Column('job_type', sa.String(length=64), nullable=False),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('payload_json', sa.Text(), nullable=False),
        sa.Column('attempts', sa.Integer(), nullable=False),
        sa.Column('max_attempts', sa.Integer(), nullable=False),
        sa.Column('run_after', sa.DateTime(), nullable=False),
        sa.Column('last_error', sa.Text(), nullable=False),
        sa.Column('dead_letter_reason', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_async_jobs_job_type', 'async_jobs', ['job_type'], unique=False)
    op.create_index('ix_async_jobs_queue_name', 'async_jobs', ['queue_name'], unique=False)
    op.create_index('ix_async_jobs_run_after', 'async_jobs', ['run_after'], unique=False)
    op.create_index('ix_async_jobs_status', 'async_jobs', ['status'], unique=False)
    op.create_index('ix_jobs_queue_status_run_after', 'async_jobs', ['queue_name', 'status', 'run_after'], unique=False)

    op.create_table(
        'compliance_artifacts',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('artifact_type', sa.String(length=64), nullable=False),
        sa.Column('version', sa.String(length=32), nullable=False),
        sa.Column('uri', sa.String(length=2048), nullable=False),
        sa.Column('active', sa.Boolean(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_compliance_artifacts_artifact_type', 'compliance_artifacts', ['artifact_type'], unique=False)

    op.create_table(
        'coupons',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('code', sa.String(length=64), nullable=False, unique=True),
        sa.Column('title', sa.String(length=120), nullable=False),
        sa.Column('description', sa.Text(), nullable=False),
        sa.Column('discount_type', sa.String(length=24), nullable=False),
        sa.Column('discount_value', sa.Float(), nullable=False),
        sa.Column('max_discount_amount', sa.Float(), nullable=False),
        sa.Column('min_order_amount', sa.Float(), nullable=False),
        sa.Column('active', sa.Boolean(), nullable=False),
        sa.Column('valid_from', sa.DateTime()),
        sa.Column('valid_to', sa.DateTime()),
        sa.Column('usage_limit_global', sa.Integer(), nullable=False),
        sa.Column('usage_limit_per_user', sa.Integer(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_coupons_active', 'coupons', ['active'], unique=False)
    op.create_index('ix_coupons_code', 'coupons', ['code'], unique=True)

    op.create_table(
        'payment_reconciliation_reports',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('provider', sa.String(length=32), nullable=False),
        sa.Column('report_date', sa.DateTime(), nullable=False),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('file_uri', sa.String(length=2048), nullable=False),
        sa.Column('mismatch_count', sa.Integer(), nullable=False),
        sa.Column('processed_at', sa.DateTime()),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_payment_reconciliation_reports_provider', 'payment_reconciliation_reports', ['provider'], unique=False)
    op.create_index('ix_payment_reconciliation_reports_report_date', 'payment_reconciliation_reports', ['report_date'], unique=False)
    op.create_index('ix_payment_reconciliation_reports_status', 'payment_reconciliation_reports', ['status'], unique=False)

    op.create_table(
        'users',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('email', sa.String(length=255), nullable=False, unique=True),
        sa.Column('password_hash', sa.String(length=255), nullable=False),
        sa.Column('role', sa.String(length=32), nullable=False),
        sa.Column('full_name', sa.String(length=120), nullable=False),
        sa.Column('phone', sa.String(length=24), nullable=False),
        sa.Column('avatar_url', sa.String(length=2048), nullable=False),
        sa.Column('is_active', sa.Boolean(), nullable=False),
        sa.Column('is_partner_available', sa.Boolean(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_users_email', 'users', ['email'], unique=True)
    op.create_index('ix_users_phone', 'users', ['phone'], unique=False)
    op.create_index('ix_users_role_created', 'users', ['role', 'created_at'], unique=False)

    op.create_table(
        'webhook_deliveries',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('provider', sa.String(length=32), nullable=False),
        sa.Column('event_id', sa.String(length=128), nullable=False),
        sa.Column('event_type', sa.String(length=64), nullable=False),
        sa.Column('signature_hash', sa.String(length=128), nullable=False),
        sa.Column('received_at', sa.DateTime(), nullable=False),
        sa.Column('processed_at', sa.DateTime()),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('payload_json', sa.Text(), nullable=False),
        sa.Column('error_message', sa.Text(), nullable=False),
        sa.Column('replay_count', sa.Integer(), nullable=False),
        sa.Column('dead_lettered_at', sa.DateTime()),
        sa.UniqueConstraint('provider', 'event_id', name='uq_webhook_provider_event'),
    )
    op.create_index('ix_webhook_deliveries_event_id', 'webhook_deliveries', ['event_id'], unique=False)
    op.create_index('ix_webhook_deliveries_event_type', 'webhook_deliveries', ['event_type'], unique=False)
    op.create_index('ix_webhook_deliveries_provider', 'webhook_deliveries', ['provider'], unique=False)
    op.create_index('ix_webhook_deliveries_signature_hash', 'webhook_deliveries', ['signature_hash'], unique=False)
    op.create_index('ix_webhook_deliveries_status', 'webhook_deliveries', ['status'], unique=False)
    op.create_index('ix_webhooks_provider_event', 'webhook_deliveries', ['provider', 'event_id'], unique=False)

    op.create_table(
        'auth_challenges',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('challenge_type', sa.String(length=32), nullable=False),
        sa.Column('target', sa.String(length=255), nullable=False),
        sa.Column('code_hash', sa.String(length=128), nullable=False),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('expires_at', sa.DateTime(), nullable=False),
        sa.Column('attempts', sa.Integer(), nullable=False),
        sa.Column('metadata_json', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('verified_at', sa.DateTime()),
    )
    op.create_index('ix_auth_challenges_challenge_type', 'auth_challenges', ['challenge_type'], unique=False)
    op.create_index('ix_auth_challenges_expires_at', 'auth_challenges', ['expires_at'], unique=False)
    op.create_index('ix_auth_challenges_status', 'auth_challenges', ['status'], unique=False)
    op.create_index('ix_auth_challenges_target', 'auth_challenges', ['target'], unique=False)
    op.create_index('ix_auth_challenges_target_type', 'auth_challenges', ['target', 'challenge_type'], unique=False)
    op.create_index('ix_auth_challenges_user_id', 'auth_challenges', ['user_id'], unique=False)

    op.create_table(
        'auth_risk_events',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('email', sa.String(length=255), nullable=False),
        sa.Column('ip_address', sa.String(length=64), nullable=False),
        sa.Column('user_agent', sa.String(length=512), nullable=False),
        sa.Column('event_type', sa.String(length=48), nullable=False),
        sa.Column('risk_score', sa.Integer(), nullable=False),
        sa.Column('reason', sa.Text(), nullable=False),
        sa.Column('blocked', sa.Boolean(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_auth_risk_events_email', 'auth_risk_events', ['email'], unique=False)
    op.create_index('ix_auth_risk_events_event_type', 'auth_risk_events', ['event_type'], unique=False)
    op.create_index('ix_auth_risk_events_ip_address', 'auth_risk_events', ['ip_address'], unique=False)
    op.create_index('ix_auth_risk_events_user_id', 'auth_risk_events', ['user_id'], unique=False)

    op.create_table(
        'customer_addresses',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('label', sa.String(length=64), nullable=False),
        sa.Column('recipient_name', sa.String(length=120), nullable=False),
        sa.Column('contact_phone', sa.String(length=24), nullable=False),
        sa.Column('line1', sa.Text(), nullable=False),
        sa.Column('line2', sa.Text(), nullable=False),
        sa.Column('landmark', sa.String(length=255), nullable=False),
        sa.Column('city', sa.String(length=64), nullable=False),
        sa.Column('pincode', sa.String(length=16), nullable=False),
        sa.Column('delivery_instructions', sa.Text(), nullable=False),
        sa.Column('lat', sa.Float(), nullable=False),
        sa.Column('lng', sa.Float(), nullable=False),
        sa.Column('is_default', sa.Boolean(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_customer_addresses_customer_id', 'customer_addresses', ['customer_id'], unique=False)

    op.create_table(
        'fcm_tokens',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('token', sa.String(length=512), nullable=False, unique=True),
        sa.Column('platform', sa.String(length=32), nullable=False),
        sa.Column('app_version', sa.String(length=64), nullable=False),
        sa.Column('device_name', sa.String(length=255), nullable=False),
        sa.Column('device_id', sa.String(length=255), nullable=False),
        sa.Column('last_seen_at', sa.DateTime(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.UniqueConstraint('token'),
    )
    op.create_index('ix_fcm_tokens_device_id', 'fcm_tokens', ['device_id'], unique=False)
    op.create_index('ix_fcm_tokens_user_id', 'fcm_tokens', ['user_id'], unique=False)

    op.create_table(
        'loyalty_memberships',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False, unique=True),
        sa.Column('tier', sa.String(length=32), nullable=False),
        sa.Column('points_balance', sa.Integer(), nullable=False),
        sa.Column('active', sa.Boolean(), nullable=False),
        sa.Column('joined_at', sa.DateTime(), nullable=False),
        sa.Column('renewed_at', sa.DateTime()),
        sa.Column('expires_at', sa.DateTime()),
    )
    op.create_index('ix_loyalty_memberships_customer_id', 'loyalty_memberships', ['customer_id'], unique=True)

    op.create_table(
        'partner_locations',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('partner_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('lat', sa.Float(), nullable=False),
        sa.Column('lng', sa.Float(), nullable=False),
        sa.Column('heading', sa.Float()),
        sa.Column('speed', sa.Float()),
        sa.Column('accuracy_meters', sa.Float()),
        sa.Column('battery_level', sa.Float()),
        sa.Column('is_mocked', sa.Boolean(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_partner_locations_partner_created', 'partner_locations', ['partner_id', 'created_at'], unique=False)
    op.create_index('ix_partner_locations_partner_id', 'partner_locations', ['partner_id'], unique=False)

    op.create_table(
        'payout_records',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('beneficiary_user_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('beneficiary_type', sa.String(length=24), nullable=False),
        sa.Column('period_start', sa.DateTime(), nullable=False),
        sa.Column('period_end', sa.DateTime(), nullable=False),
        sa.Column('gross_amount', sa.Float(), nullable=False),
        sa.Column('commission_amount', sa.Float(), nullable=False),
        sa.Column('net_amount', sa.Float(), nullable=False),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('settlement_ref', sa.String(length=128), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('settled_at', sa.DateTime()),
    )
    op.create_index('ix_payout_records_beneficiary_type', 'payout_records', ['beneficiary_type'], unique=False)
    op.create_index('ix_payout_records_beneficiary_user_id', 'payout_records', ['beneficiary_user_id'], unique=False)
    op.create_index('ix_payout_records_settlement_ref', 'payout_records', ['settlement_ref'], unique=False)
    op.create_index('ix_payout_records_status', 'payout_records', ['status'], unique=False)

    op.create_table(
        'privacy_requests',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('request_type', sa.String(length=32), nullable=False),
        sa.Column('status', sa.String(length=24), nullable=False),
        sa.Column('output_uri', sa.String(length=2048), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('processed_at', sa.DateTime()),
    )
    op.create_index('ix_privacy_requests_request_type', 'privacy_requests', ['request_type'], unique=False)
    op.create_index('ix_privacy_requests_status', 'privacy_requests', ['status'], unique=False)
    op.create_index('ix_privacy_requests_user_id', 'privacy_requests', ['user_id'], unique=False)

    op.create_table(
        'refresh_tokens',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('token_hash', sa.String(length=128), nullable=False, unique=True),
        sa.Column('token_family', sa.String(length=64), nullable=False),
        sa.Column('user_agent', sa.String(length=512), nullable=False),
        sa.Column('ip_address', sa.String(length=64), nullable=False),
        sa.Column('device_id', sa.String(length=255), nullable=False),
        sa.Column('expires_at', sa.DateTime(), nullable=False),
        sa.Column('last_used_at', sa.DateTime()),
        sa.Column('revoked_at', sa.DateTime()),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_refresh_tokens_device_id', 'refresh_tokens', ['device_id'], unique=False)
    op.create_index('ix_refresh_tokens_expires_at', 'refresh_tokens', ['expires_at'], unique=False)
    op.create_index('ix_refresh_tokens_revoked_at', 'refresh_tokens', ['revoked_at'], unique=False)
    op.create_index('ix_refresh_tokens_token_family', 'refresh_tokens', ['token_family'], unique=False)
    op.create_index('ix_refresh_tokens_token_hash', 'refresh_tokens', ['token_hash'], unique=True)
    op.create_index('ix_refresh_tokens_user_created', 'refresh_tokens', ['user_id', 'created_at'], unique=False)
    op.create_index('ix_refresh_tokens_user_id', 'refresh_tokens', ['user_id'], unique=False)

    op.create_table(
        'user_blocklist',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('email', sa.String(length=255), nullable=False),
        sa.Column('device_id', sa.String(length=255), nullable=False),
        sa.Column('reason', sa.Text(), nullable=False),
        sa.Column('active', sa.Boolean(), nullable=False),
        sa.Column('blocked_by_user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('expires_at', sa.DateTime()),
    )
    op.create_index('ix_user_blocklist_active', 'user_blocklist', ['active'], unique=False)
    op.create_index('ix_user_blocklist_blocked_by_user_id', 'user_blocklist', ['blocked_by_user_id'], unique=False)
    op.create_index('ix_user_blocklist_device_id', 'user_blocklist', ['device_id'], unique=False)
    op.create_index('ix_user_blocklist_email', 'user_blocklist', ['email'], unique=False)
    op.create_index('ix_user_blocklist_user_id', 'user_blocklist', ['user_id'], unique=False)

    op.create_table(
        'vendors',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('seller_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False, unique=True),
        sa.Column('name', sa.String(length=200), nullable=False),
        sa.Column('description', sa.Text(), nullable=False),
        sa.Column('address', sa.Text(), nullable=False),
        sa.Column('slug', sa.String(length=220), nullable=False),
        sa.Column('logo_image_url', sa.String(length=2048), nullable=False),
        sa.Column('cover_image_url', sa.String(length=2048), nullable=False),
        sa.Column('banner_image_url', sa.String(length=2048), nullable=False),
        sa.Column('cuisine_tags', sa.Text(), nullable=False),
        sa.Column('price_bucket', sa.String(length=8), nullable=False),
        sa.Column('support_phone', sa.String(length=24), nullable=False),
        sa.Column('support_email', sa.String(length=255), nullable=False),
        sa.Column('gstin', sa.String(length=32), nullable=False),
        sa.Column('lat', sa.Float()),
        sa.Column('lng', sa.Float()),
        sa.Column('delivery_radius_km', sa.Float(), nullable=False),
        sa.Column('min_order_amount', sa.Float(), nullable=False),
        sa.Column('packaging_fee', sa.Float(), nullable=False),
        sa.Column('estimated_delivery_time_min', sa.Integer(), nullable=False),
        sa.Column('avg_prep_time_min', sa.Integer(), nullable=False),
        sa.Column('avg_rating', sa.Float(), nullable=False),
        sa.Column('total_ratings', sa.Integer(), nullable=False),
        sa.Column('is_open', sa.Boolean(), nullable=False),
        sa.Column('is_accepting_orders', sa.Boolean(), nullable=False),
        sa.Column('is_busy', sa.Boolean(), nullable=False),
        sa.Column('accepts_cod', sa.Boolean(), nullable=False),
        sa.Column('open_time', sa.Time()),
        sa.Column('close_time', sa.Time()),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
        sa.UniqueConstraint('seller_id'),
    )
    op.create_index('ix_vendors_name', 'vendors', ['name'], unique=False)
    op.create_index('ix_vendors_open_accepting', 'vendors', ['is_open', 'is_accepting_orders'], unique=False)
    op.create_index('ix_vendors_slug', 'vendors', ['slug'], unique=False)

    op.create_table(
        'orders',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('vendor_id', sa.Integer(), sa.ForeignKey('vendors.id'), nullable=False),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('partner_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('status', sa.String(length=64), nullable=False),
        sa.Column('order_source', sa.String(length=32), nullable=False),
        sa.Column('customer_note', sa.Text(), nullable=False),
        sa.Column('seller_note', sa.Text(), nullable=False),
        sa.Column('internal_note', sa.Text(), nullable=False),
        sa.Column('cancellation_reason', sa.Text(), nullable=False),
        sa.Column('delivery_address_id', sa.Integer(), sa.ForeignKey('customer_addresses.id')),
        sa.Column('delivery_lat', sa.Float()),
        sa.Column('delivery_lng', sa.Float()),
        sa.Column('delivery_eta_minutes', sa.Integer()),
        sa.Column('delivery_distance_km', sa.Float()),
        sa.Column('subtotal_amount', sa.Float(), nullable=False),
        sa.Column('delivery_fee', sa.Float(), nullable=False),
        sa.Column('packaging_fee', sa.Float(), nullable=False),
        sa.Column('tax_amount', sa.Float(), nullable=False),
        sa.Column('discount_amount', sa.Float(), nullable=False),
        sa.Column('total_amount', sa.Float(), nullable=False),
        sa.Column('payment_method', sa.String(length=32), nullable=False),
        sa.Column('payment_status', sa.String(length=32), nullable=False),
        sa.Column('payment_provider', sa.String(length=32), nullable=False),
        sa.Column('payment_ref', sa.String(length=128)),
        sa.Column('refund_status', sa.String(length=32), nullable=False),
        sa.Column('refund_ref', sa.String(length=128)),
        sa.Column('idempotency_key', sa.String(length=128)),
        sa.Column('accepted_at', sa.DateTime()),
        sa.Column('assigned_at', sa.DateTime()),
        sa.Column('ready_for_pickup_at', sa.DateTime()),
        sa.Column('picked_up_at', sa.DateTime()),
        sa.Column('delivered_at', sa.DateTime()),
        sa.Column('cancelled_at', sa.DateTime()),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_orders_customer_created', 'orders', ['customer_id', 'created_at'], unique=False)
    op.create_index('ix_orders_customer_id', 'orders', ['customer_id'], unique=False)
    op.create_index('ix_orders_idempotency_key', 'orders', ['idempotency_key'], unique=False)
    op.create_index('ix_orders_partner_created', 'orders', ['partner_id', 'created_at'], unique=False)
    op.create_index('ix_orders_partner_id', 'orders', ['partner_id'], unique=False)
    op.create_index('ix_orders_payment_status_created', 'orders', ['payment_status', 'created_at'], unique=False)
    op.create_index('ix_orders_status', 'orders', ['status'], unique=False)
    op.create_index('ix_orders_status_created', 'orders', ['status', 'created_at'], unique=False)
    op.create_index('ix_orders_vendor_created', 'orders', ['vendor_id', 'created_at'], unique=False)
    op.create_index('ix_orders_vendor_id', 'orders', ['vendor_id'], unique=False)

    op.create_table(
        'products',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('vendor_id', sa.Integer(), sa.ForeignKey('vendors.id'), nullable=False),
        sa.Column('name', sa.String(length=200), nullable=False),
        sa.Column('description', sa.Text(), nullable=False),
        sa.Column('category', sa.String(length=120), nullable=False),
        sa.Column('subcategory', sa.String(length=120), nullable=False),
        sa.Column('sku', sa.String(length=64), nullable=False),
        sa.Column('barcode', sa.String(length=64), nullable=False),
        sa.Column('image_url', sa.String(length=2048), nullable=False),
        sa.Column('thumbnail_url', sa.String(length=2048), nullable=False),
        sa.Column('badge_text', sa.String(length=64), nullable=False),
        sa.Column('unit_label', sa.String(length=64), nullable=False),
        sa.Column('price', sa.Float(), nullable=False),
        sa.Column('original_price', sa.Float()),
        sa.Column('tax_rate_percent', sa.Float(), nullable=False),
        sa.Column('is_available', sa.Boolean(), nullable=False),
        sa.Column('is_featured', sa.Boolean(), nullable=False),
        sa.Column('stock_qty', sa.Integer(), nullable=False),
        sa.Column('max_qty_per_order', sa.Integer(), nullable=False),
        sa.Column('sort_order', sa.Integer(), nullable=False),
        sa.Column('avg_rating', sa.Float(), nullable=False),
        sa.Column('total_ratings', sa.Integer(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_products_barcode', 'products', ['barcode'], unique=False)
    op.create_index('ix_products_category', 'products', ['category'], unique=False)
    op.create_index('ix_products_name', 'products', ['name'], unique=False)
    op.create_index('ix_products_sku', 'products', ['sku'], unique=False)
    op.create_index('ix_products_vendor_available_sort', 'products', ['vendor_id', 'is_available', 'sort_order'], unique=False)
    op.create_index('ix_products_vendor_category', 'products', ['vendor_id', 'category'], unique=False)
    op.create_index('ix_products_vendor_id', 'products', ['vendor_id'], unique=False)

    op.create_table(
        'coupon_redemptions',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('coupon_id', sa.Integer(), sa.ForeignKey('coupons.id'), nullable=False),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id')),
        sa.Column('discount_amount', sa.Float(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_coupon_redemptions_coupon_customer', 'coupon_redemptions', ['coupon_id', 'customer_id'], unique=False)
    op.create_index('ix_coupon_redemptions_coupon_id', 'coupon_redemptions', ['coupon_id'], unique=False)
    op.create_index('ix_coupon_redemptions_customer_id', 'coupon_redemptions', ['customer_id'], unique=False)
    op.create_index('ix_coupon_redemptions_order_id', 'coupon_redemptions', ['order_id'], unique=False)

    op.create_table(
        'dispute_cases',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id')),
        sa.Column('provider_dispute_id', sa.String(length=128), nullable=False, unique=True),
        sa.Column('status', sa.String(length=32), nullable=False),
        sa.Column('amount', sa.Float(), nullable=False),
        sa.Column('reason', sa.Text(), nullable=False),
        sa.Column('due_by', sa.DateTime()),
        sa.Column('evidence_json', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_dispute_cases_order_id', 'dispute_cases', ['order_id'], unique=False)
    op.create_index('ix_dispute_cases_provider_dispute_id', 'dispute_cases', ['provider_dispute_id'], unique=True)
    op.create_index('ix_dispute_cases_status', 'dispute_cases', ['status'], unique=False)

    op.create_table(
        'money_ledger_entries',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id')),
        sa.Column('user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('counterparty_user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('event_type', sa.String(length=64), nullable=False),
        sa.Column('flow_direction', sa.String(length=16), nullable=False),
        sa.Column('amount', sa.Float(), nullable=False),
        sa.Column('currency', sa.String(length=8), nullable=False),
        sa.Column('provider_ref', sa.String(length=128), nullable=False),
        sa.Column('idempotency_key', sa.String(length=128), nullable=False),
        sa.Column('metadata_json', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_ledger_order_event_created', 'money_ledger_entries', ['order_id', 'event_type', 'created_at'], unique=False)
    op.create_index('ix_money_ledger_entries_counterparty_user_id', 'money_ledger_entries', ['counterparty_user_id'], unique=False)
    op.create_index('ix_money_ledger_entries_event_type', 'money_ledger_entries', ['event_type'], unique=False)
    op.create_index('ix_money_ledger_entries_flow_direction', 'money_ledger_entries', ['flow_direction'], unique=False)
    op.create_index('ix_money_ledger_entries_idempotency_key', 'money_ledger_entries', ['idempotency_key'], unique=False)
    op.create_index('ix_money_ledger_entries_order_id', 'money_ledger_entries', ['order_id'], unique=False)
    op.create_index('ix_money_ledger_entries_provider_ref', 'money_ledger_entries', ['provider_ref'], unique=False)
    op.create_index('ix_money_ledger_entries_user_id', 'money_ledger_entries', ['user_id'], unique=False)

    op.create_table(
        'order_events',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id'), nullable=False),
        sa.Column('status', sa.String(length=64), nullable=False),
        sa.Column('note', sa.Text(), nullable=False),
        sa.Column('actor_user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('metadata_json', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_order_events_order_created', 'order_events', ['order_id', 'created_at'], unique=False)
    op.create_index('ix_order_events_order_id', 'order_events', ['order_id'], unique=False)

    op.create_table(
        'order_items',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id'), nullable=False),
        sa.Column('product_id', sa.Integer(), sa.ForeignKey('products.id'), nullable=False),
        sa.Column('name_snapshot', sa.String(length=200), nullable=False),
        sa.Column('price_snapshot', sa.Float(), nullable=False),
        sa.Column('image_snapshot', sa.String(length=2048), nullable=False),
        sa.Column('unit_snapshot', sa.String(length=64), nullable=False),
        sa.Column('sku_snapshot', sa.String(length=64), nullable=False),
        sa.Column('qty', sa.Integer(), nullable=False),
        sa.Column('line_total_amount', sa.Float(), nullable=False),
        sa.Column('variant_snapshot', sa.Text(), nullable=False),
    )
    op.create_index('ix_order_items_order_id', 'order_items', ['order_id'], unique=False)
    op.create_index('ix_order_items_product_id', 'order_items', ['product_id'], unique=False)

    op.create_table(
        'order_reviews',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id'), nullable=False, unique=True),
        sa.Column('vendor_id', sa.Integer(), sa.ForeignKey('vendors.id'), nullable=False),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('rating', sa.Integer(), nullable=False),
        sa.Column('review_text', sa.Text(), nullable=False),
        sa.Column('tags', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_order_reviews_customer_id', 'order_reviews', ['customer_id'], unique=False)
    op.create_index('ix_order_reviews_order_id', 'order_reviews', ['order_id'], unique=True)
    op.create_index('ix_order_reviews_vendor_created', 'order_reviews', ['vendor_id', 'created_at'], unique=False)
    op.create_index('ix_order_reviews_vendor_id', 'order_reviews', ['vendor_id'], unique=False)

    op.create_table(
        'refund_cases',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id'), nullable=False),
        sa.Column('payment_ref', sa.String(length=128), nullable=False),
        sa.Column('amount', sa.Float(), nullable=False),
        sa.Column('reason', sa.Text(), nullable=False),
        sa.Column('status', sa.String(length=32), nullable=False),
        sa.Column('attempts', sa.Integer(), nullable=False),
        sa.Column('next_retry_at', sa.DateTime()),
        sa.Column('requested_by_user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_refund_cases_next_retry_at', 'refund_cases', ['next_retry_at'], unique=False)
    op.create_index('ix_refund_cases_order_id', 'refund_cases', ['order_id'], unique=False)
    op.create_index('ix_refund_cases_payment_ref', 'refund_cases', ['payment_ref'], unique=False)
    op.create_index('ix_refund_cases_requested_by_user_id', 'refund_cases', ['requested_by_user_id'], unique=False)
    op.create_index('ix_refund_cases_status', 'refund_cases', ['status'], unique=False)

    op.create_table(
        'support_tickets',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('order_id', sa.Integer(), sa.ForeignKey('orders.id')),
        sa.Column('customer_id', sa.Integer(), sa.ForeignKey('users.id'), nullable=False),
        sa.Column('vendor_id', sa.Integer(), sa.ForeignKey('vendors.id')),
        sa.Column('category', sa.String(length=64), nullable=False),
        sa.Column('status', sa.String(length=32), nullable=False),
        sa.Column('subject', sa.String(length=255), nullable=False),
        sa.Column('message', sa.Text(), nullable=False),
        sa.Column('resolution_note', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
        sa.Column('updated_at', sa.DateTime(), nullable=False),
        sa.Column('closed_at', sa.DateTime()),
    )
    op.create_index('ix_support_tickets_customer_created', 'support_tickets', ['customer_id', 'created_at'], unique=False)
    op.create_index('ix_support_tickets_customer_id', 'support_tickets', ['customer_id'], unique=False)
    op.create_index('ix_support_tickets_order_id', 'support_tickets', ['order_id'], unique=False)
    op.create_index('ix_support_tickets_status', 'support_tickets', ['status'], unique=False)
    op.create_index('ix_support_tickets_vendor_id', 'support_tickets', ['vendor_id'], unique=False)

    op.create_table(
        'money_audit_trail',
        sa.Column('id', sa.Integer(), primary_key=True, nullable=False),
        sa.Column('ledger_entry_id', sa.Integer(), sa.ForeignKey('money_ledger_entries.id'), nullable=False),
        sa.Column('action', sa.String(length=64), nullable=False),
        sa.Column('actor_user_id', sa.Integer(), sa.ForeignKey('users.id')),
        sa.Column('source_system', sa.String(length=64), nullable=False),
        sa.Column('before_json', sa.Text(), nullable=False),
        sa.Column('after_json', sa.Text(), nullable=False),
        sa.Column('created_at', sa.DateTime(), nullable=False),
    )
    op.create_index('ix_money_audit_trail_actor_user_id', 'money_audit_trail', ['actor_user_id'], unique=False)
    op.create_index('ix_money_audit_trail_ledger_entry_id', 'money_audit_trail', ['ledger_entry_id'], unique=False)



def downgrade() -> None:
    op.drop_index('ix_money_audit_trail_ledger_entry_id', table_name='money_audit_trail')
    op.drop_index('ix_money_audit_trail_actor_user_id', table_name='money_audit_trail')
    op.drop_table('money_audit_trail')
    op.drop_index('ix_support_tickets_vendor_id', table_name='support_tickets')
    op.drop_index('ix_support_tickets_status', table_name='support_tickets')
    op.drop_index('ix_support_tickets_order_id', table_name='support_tickets')
    op.drop_index('ix_support_tickets_customer_id', table_name='support_tickets')
    op.drop_index('ix_support_tickets_customer_created', table_name='support_tickets')
    op.drop_table('support_tickets')
    op.drop_index('ix_refund_cases_status', table_name='refund_cases')
    op.drop_index('ix_refund_cases_requested_by_user_id', table_name='refund_cases')
    op.drop_index('ix_refund_cases_payment_ref', table_name='refund_cases')
    op.drop_index('ix_refund_cases_order_id', table_name='refund_cases')
    op.drop_index('ix_refund_cases_next_retry_at', table_name='refund_cases')
    op.drop_table('refund_cases')
    op.drop_index('ix_order_reviews_vendor_id', table_name='order_reviews')
    op.drop_index('ix_order_reviews_vendor_created', table_name='order_reviews')
    op.drop_index('ix_order_reviews_order_id', table_name='order_reviews')
    op.drop_index('ix_order_reviews_customer_id', table_name='order_reviews')
    op.drop_table('order_reviews')
    op.drop_index('ix_order_items_product_id', table_name='order_items')
    op.drop_index('ix_order_items_order_id', table_name='order_items')
    op.drop_table('order_items')
    op.drop_index('ix_order_events_order_id', table_name='order_events')
    op.drop_index('ix_order_events_order_created', table_name='order_events')
    op.drop_table('order_events')
    op.drop_index('ix_money_ledger_entries_user_id', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_provider_ref', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_order_id', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_idempotency_key', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_flow_direction', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_event_type', table_name='money_ledger_entries')
    op.drop_index('ix_money_ledger_entries_counterparty_user_id', table_name='money_ledger_entries')
    op.drop_index('ix_ledger_order_event_created', table_name='money_ledger_entries')
    op.drop_table('money_ledger_entries')
    op.drop_index('ix_dispute_cases_status', table_name='dispute_cases')
    op.drop_index('ix_dispute_cases_provider_dispute_id', table_name='dispute_cases')
    op.drop_index('ix_dispute_cases_order_id', table_name='dispute_cases')
    op.drop_table('dispute_cases')
    op.drop_index('ix_coupon_redemptions_order_id', table_name='coupon_redemptions')
    op.drop_index('ix_coupon_redemptions_customer_id', table_name='coupon_redemptions')
    op.drop_index('ix_coupon_redemptions_coupon_id', table_name='coupon_redemptions')
    op.drop_index('ix_coupon_redemptions_coupon_customer', table_name='coupon_redemptions')
    op.drop_table('coupon_redemptions')
    op.drop_index('ix_products_vendor_id', table_name='products')
    op.drop_index('ix_products_vendor_category', table_name='products')
    op.drop_index('ix_products_vendor_available_sort', table_name='products')
    op.drop_index('ix_products_sku', table_name='products')
    op.drop_index('ix_products_name', table_name='products')
    op.drop_index('ix_products_category', table_name='products')
    op.drop_index('ix_products_barcode', table_name='products')
    op.drop_table('products')
    op.drop_index('ix_orders_vendor_id', table_name='orders')
    op.drop_index('ix_orders_vendor_created', table_name='orders')
    op.drop_index('ix_orders_status_created', table_name='orders')
    op.drop_index('ix_orders_status', table_name='orders')
    op.drop_index('ix_orders_payment_status_created', table_name='orders')
    op.drop_index('ix_orders_partner_id', table_name='orders')
    op.drop_index('ix_orders_partner_created', table_name='orders')
    op.drop_index('ix_orders_idempotency_key', table_name='orders')
    op.drop_index('ix_orders_customer_id', table_name='orders')
    op.drop_index('ix_orders_customer_created', table_name='orders')
    op.drop_table('orders')
    op.drop_index('ix_vendors_slug', table_name='vendors')
    op.drop_index('ix_vendors_open_accepting', table_name='vendors')
    op.drop_index('ix_vendors_name', table_name='vendors')
    op.drop_table('vendors')
    op.drop_index('ix_user_blocklist_user_id', table_name='user_blocklist')
    op.drop_index('ix_user_blocklist_email', table_name='user_blocklist')
    op.drop_index('ix_user_blocklist_device_id', table_name='user_blocklist')
    op.drop_index('ix_user_blocklist_blocked_by_user_id', table_name='user_blocklist')
    op.drop_index('ix_user_blocklist_active', table_name='user_blocklist')
    op.drop_table('user_blocklist')
    op.drop_index('ix_refresh_tokens_user_id', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_user_created', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_token_hash', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_token_family', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_revoked_at', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_expires_at', table_name='refresh_tokens')
    op.drop_index('ix_refresh_tokens_device_id', table_name='refresh_tokens')
    op.drop_table('refresh_tokens')
    op.drop_index('ix_privacy_requests_user_id', table_name='privacy_requests')
    op.drop_index('ix_privacy_requests_status', table_name='privacy_requests')
    op.drop_index('ix_privacy_requests_request_type', table_name='privacy_requests')
    op.drop_table('privacy_requests')
    op.drop_index('ix_payout_records_status', table_name='payout_records')
    op.drop_index('ix_payout_records_settlement_ref', table_name='payout_records')
    op.drop_index('ix_payout_records_beneficiary_user_id', table_name='payout_records')
    op.drop_index('ix_payout_records_beneficiary_type', table_name='payout_records')
    op.drop_table('payout_records')
    op.drop_index('ix_partner_locations_partner_id', table_name='partner_locations')
    op.drop_index('ix_partner_locations_partner_created', table_name='partner_locations')
    op.drop_table('partner_locations')
    op.drop_index('ix_loyalty_memberships_customer_id', table_name='loyalty_memberships')
    op.drop_table('loyalty_memberships')
    op.drop_index('ix_fcm_tokens_user_id', table_name='fcm_tokens')
    op.drop_index('ix_fcm_tokens_device_id', table_name='fcm_tokens')
    op.drop_table('fcm_tokens')
    op.drop_index('ix_customer_addresses_customer_id', table_name='customer_addresses')
    op.drop_table('customer_addresses')
    op.drop_index('ix_auth_risk_events_user_id', table_name='auth_risk_events')
    op.drop_index('ix_auth_risk_events_ip_address', table_name='auth_risk_events')
    op.drop_index('ix_auth_risk_events_event_type', table_name='auth_risk_events')
    op.drop_index('ix_auth_risk_events_email', table_name='auth_risk_events')
    op.drop_table('auth_risk_events')
    op.drop_index('ix_auth_challenges_user_id', table_name='auth_challenges')
    op.drop_index('ix_auth_challenges_target_type', table_name='auth_challenges')
    op.drop_index('ix_auth_challenges_target', table_name='auth_challenges')
    op.drop_index('ix_auth_challenges_status', table_name='auth_challenges')
    op.drop_index('ix_auth_challenges_expires_at', table_name='auth_challenges')
    op.drop_index('ix_auth_challenges_challenge_type', table_name='auth_challenges')
    op.drop_table('auth_challenges')
    op.drop_index('ix_webhooks_provider_event', table_name='webhook_deliveries')
    op.drop_index('ix_webhook_deliveries_status', table_name='webhook_deliveries')
    op.drop_index('ix_webhook_deliveries_signature_hash', table_name='webhook_deliveries')
    op.drop_index('ix_webhook_deliveries_provider', table_name='webhook_deliveries')
    op.drop_index('ix_webhook_deliveries_event_type', table_name='webhook_deliveries')
    op.drop_index('ix_webhook_deliveries_event_id', table_name='webhook_deliveries')
    op.drop_table('webhook_deliveries')
    op.drop_index('ix_users_role_created', table_name='users')
    op.drop_index('ix_users_phone', table_name='users')
    op.drop_index('ix_users_email', table_name='users')
    op.drop_table('users')
    op.drop_index('ix_payment_reconciliation_reports_status', table_name='payment_reconciliation_reports')
    op.drop_index('ix_payment_reconciliation_reports_report_date', table_name='payment_reconciliation_reports')
    op.drop_index('ix_payment_reconciliation_reports_provider', table_name='payment_reconciliation_reports')
    op.drop_table('payment_reconciliation_reports')
    op.drop_index('ix_coupons_code', table_name='coupons')
    op.drop_index('ix_coupons_active', table_name='coupons')
    op.drop_table('coupons')
    op.drop_index('ix_compliance_artifacts_artifact_type', table_name='compliance_artifacts')
    op.drop_table('compliance_artifacts')
    op.drop_index('ix_jobs_queue_status_run_after', table_name='async_jobs')
    op.drop_index('ix_async_jobs_status', table_name='async_jobs')
    op.drop_index('ix_async_jobs_run_after', table_name='async_jobs')
    op.drop_index('ix_async_jobs_queue_name', table_name='async_jobs')
    op.drop_index('ix_async_jobs_job_type', table_name='async_jobs')
    op.drop_table('async_jobs')
