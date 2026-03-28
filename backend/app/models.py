from __future__ import annotations

from datetime import datetime
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
)
from sqlalchemy.orm import relationship

from .db import Base


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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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
    last_seen_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

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
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)

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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)
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

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow, nullable=False)


class CouponRedemption(Base):
    __tablename__ = "coupon_redemptions"
    id = Column(Integer, primary_key=True)

    coupon_id = Column(Integer, ForeignKey("coupons.id"), nullable=False, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=True, index=True)
    discount_amount = Column(Float, default=0.0, nullable=False)
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

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
    joined_at = Column(DateTime, default=datetime.utcnow, nullable=False)
    renewed_at = Column(DateTime, nullable=True)
    expires_at = Column(DateTime, nullable=True)

    customer = relationship("User", foreign_keys=[customer_id])


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
