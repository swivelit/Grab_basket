from __future__ import annotations

from datetime import datetime, timezone
from sqlalchemy import (
    Boolean,
    Column,
    DateTime,
    Float,
    ForeignKey,
    Index,
    Integer,
    String,
    Text,
    Time,
    UniqueConstraint,
)
from sqlalchemy.orm import relationship

from .db import Base

def utc_now() -> datetime:
    return datetime.now(timezone.utc)




class User(Base):
    __tablename__ = "users"
    id = Column(Integer, primary_key=True)

    email = Column(String(255), unique=True, nullable=False, index=True)
    password_hash = Column(String(255), nullable=False)

    # CUSTOMER / SELLER / PARTNER / ADMIN
    role = Column(String(32), nullable=False)

    full_name = Column(String(120), default="", nullable=False)
    phone = Column(String(24), default="", nullable=False, index=True)
    avatar_url = Column(String(2048), default="", nullable=False)
    is_active = Column(Boolean, default=True, nullable=False)
    is_partner_available = Column(Boolean, default=False, nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    vendor = relationship("Vendor", back_populates="seller", uselist=False)
    customer_addresses = relationship(
        "CustomerAddress",
        back_populates="customer",
        cascade="all, delete-orphan",
    )
    partner_locations = relationship(
        "PartnerLocation",
        back_populates="partner",
        cascade="all, delete-orphan",
    )
    fcm_tokens = relationship("FcmToken", back_populates="user", cascade="all, delete-orphan")
    refresh_tokens = relationship(
        "RefreshToken",
        back_populates="user",
        cascade="all, delete-orphan",
    )


class FcmToken(Base):
    __tablename__ = "fcm_tokens"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    token = Column(String(512), nullable=False, unique=True)
    platform = Column(String(32), default="unknown", nullable=False)  # android/ios/web
    app_version = Column(String(64), default="", nullable=False)
    device_name = Column(String(255), default="", nullable=False)
    device_id = Column(String(255), default="", nullable=False, index=True)
    last_seen_at = Column(DateTime, default=utc_now, nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)

    user = relationship("User", back_populates="fcm_tokens")


class RefreshToken(Base):
    __tablename__ = "refresh_tokens"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    token_hash = Column(String(128), nullable=False, unique=True, index=True)
    token_family = Column(String(64), nullable=False, index=True)
    user_agent = Column(String(512), default="", nullable=False)
    ip_address = Column(String(64), default="", nullable=False)
    device_id = Column(String(255), default="", nullable=False, index=True)
    expires_at = Column(DateTime, nullable=False, index=True)
    last_used_at = Column(DateTime, nullable=True)
    revoked_at = Column(DateTime, nullable=True, index=True)
    created_at = Column(DateTime, default=utc_now, nullable=False)

    user = relationship("User", back_populates="refresh_tokens")


class Vendor(Base):
    __tablename__ = "vendors"
    id = Column(Integer, primary_key=True)
    seller_id = Column(Integer, ForeignKey("users.id"), nullable=False, unique=True)

    name = Column(String(200), nullable=False, index=True)
    description = Column(Text, default="", nullable=False)
    address = Column(Text, default="", nullable=False)
    slug = Column(String(220), default="", nullable=False, index=True)

    logo_image_url = Column(String(2048), default="", nullable=False)
    cover_image_url = Column(String(2048), default="", nullable=False)
    banner_image_url = Column(String(2048), default="", nullable=False)
    cuisine_tags = Column(Text, default="", nullable=False)  # comma-separated for now
    price_bucket = Column(String(8), default="", nullable=False)  # $, $$, $$$ or custom

    support_phone = Column(String(24), default="", nullable=False)
    support_email = Column(String(255), default="", nullable=False)
    gstin = Column(String(32), default="", nullable=False)

    # Store geo + delivery radius (km)
    lat = Column(Float, nullable=True)
    lng = Column(Float, nullable=True)
    delivery_radius_km = Column(Float, default=5.0, nullable=False)

    # Commercial / operational settings
    min_order_amount = Column(Float, default=0.0, nullable=False)
    packaging_fee = Column(Float, default=0.0, nullable=False)
    estimated_delivery_time_min = Column(Integer, default=30, nullable=False)
    avg_prep_time_min = Column(Integer, default=15, nullable=False)
    avg_rating = Column(Float, default=0.0, nullable=False)
    total_ratings = Column(Integer, default=0, nullable=False)

    # Store timings
    is_open = Column(Boolean, default=True, nullable=False)
    is_accepting_orders = Column(Boolean, default=True, nullable=False)
    is_busy = Column(Boolean, default=False, nullable=False)
    accepts_cod = Column(Boolean, default=True, nullable=False)
    open_time = Column(Time, nullable=True)   # e.g. 09:00
    close_time = Column(Time, nullable=True)  # e.g. 23:00

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    seller = relationship("User", back_populates="vendor")
    products = relationship("Product", back_populates="vendor", cascade="all, delete-orphan")
    orders = relationship("Order", back_populates="vendor")


class Product(Base):
    __tablename__ = "products"
    id = Column(Integer, primary_key=True)
    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=False, index=True)

    name = Column(String(200), nullable=False, index=True)
    description = Column(Text, default="", nullable=False)
    category = Column(String(120), default="", nullable=False, index=True)
    subcategory = Column(String(120), default="", nullable=False)
    sku = Column(String(64), default="", nullable=False, index=True)
    barcode = Column(String(64), default="", nullable=False, index=True)

    image_url = Column(String(2048), default="", nullable=False)
    thumbnail_url = Column(String(2048), default="", nullable=False)
    badge_text = Column(String(64), default="", nullable=False)
    unit_label = Column(String(64), default="", nullable=False)  # e.g. 500 g / 1 L / 1 pc

    price = Column(Float, nullable=False)
    original_price = Column(Float, nullable=True)
    tax_rate_percent = Column(Float, default=0.0, nullable=False)

    is_available = Column(Boolean, default=True, nullable=False)
    is_featured = Column(Boolean, default=False, nullable=False)
    stock_qty = Column(Integer, default=0, nullable=False)
    max_qty_per_order = Column(Integer, default=20, nullable=False)
    sort_order = Column(Integer, default=0, nullable=False)

    avg_rating = Column(Float, default=0.0, nullable=False)
    total_ratings = Column(Integer, default=0, nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    vendor = relationship("Vendor", back_populates="products")


class CustomerAddress(Base):
    __tablename__ = "customer_addresses"
    id = Column(Integer, primary_key=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)

    label = Column(String(64), default="Home", nullable=False)
    recipient_name = Column(String(120), default="", nullable=False)
    contact_phone = Column(String(24), default="", nullable=False)

    line1 = Column(Text, nullable=False)
    line2 = Column(Text, default="", nullable=False)
    landmark = Column(String(255), default="", nullable=False)
    city = Column(String(64), default="", nullable=False)
    pincode = Column(String(16), default="", nullable=False)
    delivery_instructions = Column(Text, default="", nullable=False)

    lat = Column(Float, nullable=False)
    lng = Column(Float, nullable=False)

    is_default = Column(Boolean, default=False, nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    customer = relationship("User", back_populates="customer_addresses")


class PartnerLocation(Base):
    __tablename__ = "partner_locations"
    id = Column(Integer, primary_key=True)
    partner_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)

    lat = Column(Float, nullable=False)
    lng = Column(Float, nullable=False)
    heading = Column(Float, nullable=True)
    speed = Column(Float, nullable=True)
    accuracy_meters = Column(Float, nullable=True)
    battery_level = Column(Float, nullable=True)
    is_mocked = Column(Boolean, default=False, nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)

    partner = relationship("User", back_populates="partner_locations")


class Order(Base):
    __tablename__ = "orders"
    id = Column(Integer, primary_key=True)

    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=False, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    partner_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)

    # stages:
    # CREATED -> ACCEPTED_BY_SELLER -> ASSIGNED_TO_PARTNER -> READY_FOR_PICKUP -> PICKED_UP -> DELIVERED
    # optional: CANCELLED_* / REJECTED_*
    status = Column(String(64), default="CREATED", nullable=False, index=True)

    order_source = Column(String(32), default="APP", nullable=False)  # APP / ADMIN / WEB / API
    customer_note = Column(Text, default="", nullable=False)
    seller_note = Column(Text, default="", nullable=False)
    internal_note = Column(Text, default="", nullable=False)
    cancellation_reason = Column(Text, default="", nullable=False)

    # Delivery details
    delivery_address_id = Column(Integer, ForeignKey("customer_addresses.id"), nullable=True)
    delivery_lat = Column(Float, nullable=True)
    delivery_lng = Column(Float, nullable=True)
    delivery_eta_minutes = Column(Integer, nullable=True)
    delivery_distance_km = Column(Float, nullable=True)

    subtotal_amount = Column(Float, default=0.0, nullable=False)
    delivery_fee = Column(Float, default=0.0, nullable=False)
    packaging_fee = Column(Float, default=0.0, nullable=False)
    tax_amount = Column(Float, default=0.0, nullable=False)
    discount_amount = Column(Float, default=0.0, nullable=False)
    total_amount = Column(Float, default=0.0, nullable=False)

    # Payments
    payment_method = Column(String(32), default="COD", nullable=False)  # COD / UPI / GATEWAY
    payment_status = Column(String(32), default="PENDING", nullable=False)  # PENDING / PAID / FAILED
    payment_provider = Column(String(32), default="", nullable=False)
    payment_ref = Column(String(128), nullable=True)
    refund_status = Column(String(32), default="NOT_APPLICABLE", nullable=False)
    refund_ref = Column(String(128), nullable=True)
    idempotency_key = Column(String(128), nullable=True, index=True)

    accepted_at = Column(DateTime, nullable=True)
    assigned_at = Column(DateTime, nullable=True)
    ready_for_pickup_at = Column(DateTime, nullable=True)
    picked_up_at = Column(DateTime, nullable=True)
    delivered_at = Column(DateTime, nullable=True)
    cancelled_at = Column(DateTime, nullable=True)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    vendor = relationship("Vendor", back_populates="orders")
    items = relationship(
        "OrderItem",
        back_populates="order",
        cascade="all, delete-orphan",
        order_by="OrderItem.id",
    )
    delivery_address = relationship("CustomerAddress", foreign_keys=[delivery_address_id])
    events = relationship(
        "OrderEvent",
        back_populates="order",
        cascade="all, delete-orphan",
        order_by="OrderEvent.created_at",
    )


class OrderEvent(Base):
    __tablename__ = "order_events"
    id = Column(Integer, primary_key=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, index=True)

    status = Column(String(64), nullable=False)
    note = Column(Text, default="", nullable=False)
    actor_user_id = Column(Integer, ForeignKey("users.id"), nullable=True)
    metadata_json = Column(Text, default="", nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)

    order = relationship("Order", back_populates="events")
    actor_user = relationship("User", foreign_keys=[actor_user_id])


class OrderItem(Base):
    __tablename__ = "order_items"
    id = Column(Integer, primary_key=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, index=True)

    product_id = Column(Integer, ForeignKey("products.id"), nullable=False, index=True)
    name_snapshot = Column(String(200), nullable=False)
    price_snapshot = Column(Float, nullable=False)
    image_snapshot = Column(String(2048), default="", nullable=False)
    unit_snapshot = Column(String(64), default="", nullable=False)
    sku_snapshot = Column(String(64), default="", nullable=False)
    qty = Column(Integer, nullable=False)
    line_total_amount = Column(Float, default=0.0, nullable=False)
    variant_snapshot = Column(Text, default="", nullable=False)

    order = relationship("Order", back_populates="items")
    product = relationship("Product")


class OrderReview(Base):
    __tablename__ = "order_reviews"
    id = Column(Integer, primary_key=True)

    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, unique=True, index=True)
    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=False, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)

    rating = Column(Integer, nullable=False)  # 1..5
    review_text = Column(Text, default="", nullable=False)
    tags = Column(Text, default="", nullable=False)  # comma-separated for now

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)

    order = relationship("Order", foreign_keys=[order_id])
    vendor = relationship("Vendor", foreign_keys=[vendor_id])
    customer = relationship("User", foreign_keys=[customer_id])


class SupportTicket(Base):
    __tablename__ = "support_tickets"
    id = Column(Integer, primary_key=True)

    order_id = Column(Integer, ForeignKey("orders.id"), nullable=True, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=True, index=True)

    category = Column(String(64), default="GENERAL", nullable=False)
    status = Column(String(32), default="OPEN", nullable=False, index=True)
    subject = Column(String(255), default="", nullable=False)
    message = Column(Text, default="", nullable=False)
    resolution_note = Column(Text, default="", nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)
    closed_at = Column(DateTime, nullable=True)

    order = relationship("Order", foreign_keys=[order_id])
    customer = relationship("User", foreign_keys=[customer_id])
    vendor = relationship("Vendor", foreign_keys=[vendor_id])


class Coupon(Base):
    __tablename__ = "coupons"
    id = Column(Integer, primary_key=True)

    code = Column(String(64), nullable=False, unique=True, index=True)
    title = Column(String(120), default="", nullable=False)
    description = Column(Text, default="", nullable=False)
    discount_type = Column(String(24), default="FLAT", nullable=False)  # FLAT / PERCENT
    discount_value = Column(Float, default=0.0, nullable=False)
    max_discount_amount = Column(Float, default=0.0, nullable=False)
    min_order_amount = Column(Float, default=0.0, nullable=False)
    active = Column(Boolean, default=True, nullable=False, index=True)

    valid_from = Column(DateTime, nullable=True)
    valid_to = Column(DateTime, nullable=True)
    usage_limit_global = Column(Integer, default=0, nullable=False)  # 0 means unlimited
    usage_limit_per_user = Column(Integer, default=1, nullable=False)

    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)


class CouponRedemption(Base):
    __tablename__ = "coupon_redemptions"
    id = Column(Integer, primary_key=True)

    coupon_id = Column(Integer, ForeignKey("coupons.id"), nullable=False, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=True, index=True)
    discount_amount = Column(Float, default=0.0, nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)

    coupon = relationship("Coupon", foreign_keys=[coupon_id])
    customer = relationship("User", foreign_keys=[customer_id])
    order = relationship("Order", foreign_keys=[order_id])


class LoyaltyMembership(Base):
    __tablename__ = "loyalty_memberships"
    id = Column(Integer, primary_key=True)

    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, unique=True, index=True)
    tier = Column(String(32), default="BASIC", nullable=False)
    points_balance = Column(Integer, default=0, nullable=False)
    active = Column(Boolean, default=True, nullable=False)
    joined_at = Column(DateTime, default=utc_now, nullable=False)
    renewed_at = Column(DateTime, nullable=True)
    expires_at = Column(DateTime, nullable=True)

    customer = relationship("User", foreign_keys=[customer_id])


class MoneyLedgerEntry(Base):
    __tablename__ = "money_ledger_entries"
    id = Column(Integer, primary_key=True)

    order_id = Column(Integer, ForeignKey("orders.id"), nullable=True, index=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    counterparty_user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    event_type = Column(String(64), nullable=False, index=True)
    flow_direction = Column(String(16), nullable=False, index=True)  # CREDIT / DEBIT
    amount = Column(Float, nullable=False)
    currency = Column(String(8), default="INR", nullable=False)
    provider_ref = Column(String(128), default="", nullable=False, index=True)
    idempotency_key = Column(String(128), default="", nullable=False, index=True)
    metadata_json = Column(Text, default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)


class PaymentReconciliationReport(Base):
    __tablename__ = "payment_reconciliation_reports"
    id = Column(Integer, primary_key=True)

    provider = Column(String(32), nullable=False, index=True)
    report_date = Column(DateTime, nullable=False, index=True)
    status = Column(String(24), default="PENDING", nullable=False, index=True)
    file_uri = Column(String(2048), default="", nullable=False)
    mismatch_count = Column(Integer, default=0, nullable=False)
    processed_at = Column(DateTime, nullable=True)
    created_at = Column(DateTime, default=utc_now, nullable=False)


class WebhookDelivery(Base):
    __tablename__ = "webhook_deliveries"
    __table_args__ = (UniqueConstraint("provider", "event_id", name="uq_webhook_provider_event"),)
    id = Column(Integer, primary_key=True)

    provider = Column(String(32), nullable=False, index=True)
    event_id = Column(String(128), nullable=False, index=True)
    event_type = Column(String(64), default="", nullable=False, index=True)
    signature_hash = Column(String(128), default="", nullable=False, index=True)
    received_at = Column(DateTime, default=utc_now, nullable=False)
    processed_at = Column(DateTime, nullable=True)
    status = Column(String(24), default="RECEIVED", nullable=False, index=True)
    payload_json = Column(Text, default="", nullable=False)
    error_message = Column(Text, default="", nullable=False)
    replay_count = Column(Integer, default=0, nullable=False)
    dead_lettered_at = Column(DateTime, nullable=True)


class RefundCase(Base):
    __tablename__ = "refund_cases"
    id = Column(Integer, primary_key=True)

    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, index=True)
    payment_ref = Column(String(128), default="", nullable=False, index=True)
    amount = Column(Float, nullable=False)
    reason = Column(Text, default="", nullable=False)
    status = Column(String(32), default="REQUESTED", nullable=False, index=True)
    attempts = Column(Integer, default=0, nullable=False)
    next_retry_at = Column(DateTime, nullable=True, index=True)
    requested_by_user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)


class DisputeCase(Base):
    __tablename__ = "dispute_cases"
    id = Column(Integer, primary_key=True)

    order_id = Column(Integer, ForeignKey("orders.id"), nullable=True, index=True)
    provider_dispute_id = Column(String(128), nullable=False, unique=True, index=True)
    status = Column(String(32), default="OPEN", nullable=False, index=True)
    amount = Column(Float, nullable=False)
    reason = Column(Text, default="", nullable=False)
    due_by = Column(DateTime, nullable=True)
    evidence_json = Column(Text, default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)


class PayoutRecord(Base):
    __tablename__ = "payout_records"
    id = Column(Integer, primary_key=True)

    beneficiary_user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    beneficiary_type = Column(String(24), nullable=False, index=True)  # SELLER / PARTNER
    period_start = Column(DateTime, nullable=False)
    period_end = Column(DateTime, nullable=False)
    gross_amount = Column(Float, nullable=False)
    commission_amount = Column(Float, default=0.0, nullable=False)
    net_amount = Column(Float, nullable=False)
    status = Column(String(24), default="PENDING", nullable=False, index=True)
    settlement_ref = Column(String(128), default="", nullable=False, index=True)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    settled_at = Column(DateTime, nullable=True)


class MoneyAuditTrail(Base):
    __tablename__ = "money_audit_trail"
    id = Column(Integer, primary_key=True)
    ledger_entry_id = Column(Integer, ForeignKey("money_ledger_entries.id"), nullable=False, index=True)
    action = Column(String(64), nullable=False)
    actor_user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    source_system = Column(String(64), default="backend", nullable=False)
    before_json = Column(Text, default="", nullable=False)
    after_json = Column(Text, default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)


class AuthChallenge(Base):
    __tablename__ = "auth_challenges"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    challenge_type = Column(String(32), nullable=False, index=True)  # PHONE_OTP / EMAIL_VERIFY / PASSWORD_RESET
    target = Column(String(255), default="", nullable=False, index=True)
    code_hash = Column(String(128), nullable=False)
    status = Column(String(24), default="PENDING", nullable=False, index=True)
    expires_at = Column(DateTime, nullable=False, index=True)
    attempts = Column(Integer, default=0, nullable=False)
    metadata_json = Column(Text, default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    verified_at = Column(DateTime, nullable=True)


class AuthRiskEvent(Base):
    __tablename__ = "auth_risk_events"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    email = Column(String(255), default="", nullable=False, index=True)
    ip_address = Column(String(64), default="", nullable=False, index=True)
    user_agent = Column(String(512), default="", nullable=False)
    event_type = Column(String(48), nullable=False, index=True)  # LOGIN / SIGNUP / CAPTCHA / RATE_LIMIT
    risk_score = Column(Integer, default=0, nullable=False)
    reason = Column(Text, default="", nullable=False)
    blocked = Column(Boolean, default=False, nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)


class UserBlocklist(Base):
    __tablename__ = "user_blocklist"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    email = Column(String(255), default="", nullable=False, index=True)
    device_id = Column(String(255), default="", nullable=False, index=True)
    reason = Column(Text, default="", nullable=False)
    active = Column(Boolean, default=True, nullable=False, index=True)
    blocked_by_user_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    expires_at = Column(DateTime, nullable=True)


class AsyncJob(Base):
    __tablename__ = "async_jobs"
    id = Column(Integer, primary_key=True)
    queue_name = Column(String(64), nullable=False, index=True)
    job_type = Column(String(64), nullable=False, index=True)
    status = Column(String(24), default="QUEUED", nullable=False, index=True)
    payload_json = Column(Text, default="", nullable=False)
    attempts = Column(Integer, default=0, nullable=False)
    max_attempts = Column(Integer, default=5, nullable=False)
    run_after = Column(DateTime, default=utc_now, nullable=False, index=True)
    last_error = Column(Text, default="", nullable=False)
    dead_letter_reason = Column(Text, default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    updated_at = Column(DateTime, default=utc_now, onupdate=utc_now, nullable=False)


class ComplianceArtifact(Base):
    __tablename__ = "compliance_artifacts"
    id = Column(Integer, primary_key=True)
    artifact_type = Column(String(64), nullable=False, index=True)  # PRIVACY_POLICY / TERMS / PLAY_DATA_SAFETY
    version = Column(String(32), nullable=False)
    uri = Column(String(2048), default="", nullable=False)
    active = Column(Boolean, default=True, nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)


class PrivacyRequest(Base):
    __tablename__ = "privacy_requests"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    request_type = Column(String(32), nullable=False, index=True)  # ACCOUNT_DELETE / DATA_EXPORT
    status = Column(String(24), default="PENDING", nullable=False, index=True)
    output_uri = Column(String(2048), default="", nullable=False)
    created_at = Column(DateTime, default=utc_now, nullable=False)
    processed_at = Column(DateTime, nullable=True)


# Helpful indexes for production-ish query patterns
Index("ix_users_role_created", User.role, User.created_at)
Index("ix_partner_locations_partner_created", PartnerLocation.partner_id, PartnerLocation.created_at)
Index("ix_order_events_order_created", OrderEvent.order_id, OrderEvent.created_at)
Index("ix_orders_status_created", Order.status, Order.created_at)
Index("ix_orders_vendor_created", Order.vendor_id, Order.created_at)
Index("ix_orders_customer_created", Order.customer_id, Order.created_at)
Index("ix_orders_partner_created", Order.partner_id, Order.created_at)
Index("ix_orders_payment_status_created", Order.payment_status, Order.created_at)
Index("ix_products_vendor_available_sort", Product.vendor_id, Product.is_available, Product.sort_order)
Index("ix_products_vendor_category", Product.vendor_id, Product.category)
Index("ix_vendors_open_accepting", Vendor.is_open, Vendor.is_accepting_orders)
Index("ix_refresh_tokens_user_created", RefreshToken.user_id, RefreshToken.created_at)
Index("ix_order_reviews_vendor_created", OrderReview.vendor_id, OrderReview.created_at)
Index("ix_support_tickets_customer_created", SupportTicket.customer_id, SupportTicket.created_at)
Index("ix_coupon_redemptions_coupon_customer", CouponRedemption.coupon_id, CouponRedemption.customer_id)
Index("ix_ledger_order_event_created", MoneyLedgerEntry.order_id, MoneyLedgerEntry.event_type, MoneyLedgerEntry.created_at)
Index("ix_jobs_queue_status_run_after", AsyncJob.queue_name, AsyncJob.status, AsyncJob.run_after)
Index("ix_webhooks_provider_event", WebhookDelivery.provider, WebhookDelivery.event_id)
Index("ix_auth_challenges_target_type", AuthChallenge.target, AuthChallenge.challenge_type)
