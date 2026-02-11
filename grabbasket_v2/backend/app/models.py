from __future__ import annotations

from datetime import datetime, time
from sqlalchemy import (
    Boolean,
    Column,
    DateTime,
    Float,
    ForeignKey,
    Integer,
    String,
    Text,
    Time,
    Index,
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

    is_partner_available = Column(Boolean, default=False, nullable=False)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    vendor = relationship("Vendor", back_populates="seller", uselist=False)
    customer_addresses = relationship("CustomerAddress", back_populates="customer", cascade="all, delete-orphan")
    partner_locations = relationship("PartnerLocation", back_populates="partner", cascade="all, delete-orphan")
    fcm_tokens = relationship("FcmToken", back_populates="user", cascade="all, delete-orphan")


class FcmToken(Base):
    __tablename__ = "fcm_tokens"
    id = Column(Integer, primary_key=True)
    user_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    token = Column(String(512), nullable=False, unique=True)
    platform = Column(String(32), default="unknown", nullable=False)  # android/ios/web
    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    user = relationship("User", back_populates="fcm_tokens")


class Vendor(Base):
    __tablename__ = "vendors"
    id = Column(Integer, primary_key=True)
    seller_id = Column(Integer, ForeignKey("users.id"), nullable=False, unique=True)

    name = Column(String(200), nullable=False)
    description = Column(Text, default="", nullable=False)
    address = Column(Text, default="", nullable=False)

    # Store geo + delivery radius (km)
    lat = Column(Float, nullable=True)
    lng = Column(Float, nullable=True)
    delivery_radius_km = Column(Float, default=5.0, nullable=False)

    # Store timings
    is_open = Column(Boolean, default=True, nullable=False)
    open_time = Column(Time, nullable=True)   # e.g. 09:00
    close_time = Column(Time, nullable=True)  # e.g. 23:00

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    seller = relationship("User", back_populates="vendor")
    products = relationship("Product", back_populates="vendor", cascade="all, delete-orphan")
    orders = relationship("Order", back_populates="vendor")


class Product(Base):
    __tablename__ = "products"
    id = Column(Integer, primary_key=True)
    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=False, index=True)

    name = Column(String(200), nullable=False)
    description = Column(Text, default="", nullable=False)
    price = Column(Float, nullable=False)
    is_available = Column(Boolean, default=True, nullable=False)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    vendor = relationship("Vendor", back_populates="products")


class CustomerAddress(Base):
    __tablename__ = "customer_addresses"
    id = Column(Integer, primary_key=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)

    label = Column(String(64), default="Home", nullable=False)
    line1 = Column(Text, nullable=False)
    line2 = Column(Text, default="", nullable=False)
    city = Column(String(64), default="", nullable=False)
    pincode = Column(String(16), default="", nullable=False)

    lat = Column(Float, nullable=False)
    lng = Column(Float, nullable=False)

    is_default = Column(Boolean, default=False, nullable=False)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    customer = relationship("User", back_populates="customer_addresses")


class PartnerLocation(Base):
    __tablename__ = "partner_locations"
    id = Column(Integer, primary_key=True)
    partner_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)

    lat = Column(Float, nullable=False)
    lng = Column(Float, nullable=False)
    heading = Column(Float, nullable=True)
    speed = Column(Float, nullable=True)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    partner = relationship("User", back_populates="partner_locations")


class Order(Base):
    __tablename__ = "orders"
    id = Column(Integer, primary_key=True)

    vendor_id = Column(Integer, ForeignKey("vendors.id"), nullable=False, index=True)
    customer_id = Column(Integer, ForeignKey("users.id"), nullable=False, index=True)
    partner_id = Column(Integer, ForeignKey("users.id"), nullable=True, index=True)

    # stages:
    # CREATED -> ACCEPTED_BY_SELLER -> ASSIGNED_TO_PARTNER -> PICKED_UP -> DELIVERED
    # optional: CANCELLED_*
    status = Column(String(64), default="CREATED", nullable=False)

    # Delivery details
    delivery_address_id = Column(Integer, ForeignKey("customer_addresses.id"), nullable=True)
    delivery_lat = Column(Float, nullable=True)
    delivery_lng = Column(Float, nullable=True)

    subtotal_amount = Column(Float, default=0.0, nullable=False)
    delivery_fee = Column(Float, default=0.0, nullable=False)
    total_amount = Column(Float, default=0.0, nullable=False)

    # Payments
    payment_method = Column(String(32), default="COD", nullable=False)  # COD / UPI / GATEWAY
    payment_status = Column(String(32), default="PENDING", nullable=False)  # PENDING / PAID / FAILED
    payment_ref = Column(String(128), nullable=True)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    vendor = relationship("Vendor", back_populates="orders")
    items = relationship("OrderItem", back_populates="order", cascade="all, delete-orphan")
    delivery_address = relationship("CustomerAddress", foreign_keys=[delivery_address_id])
    events = relationship("OrderEvent", back_populates="order", cascade="all, delete-orphan")


class OrderEvent(Base):
    __tablename__ = "order_events"
    id = Column(Integer, primary_key=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, index=True)

    status = Column(String(64), nullable=False)
    note = Column(Text, default="", nullable=False)
    actor_user_id = Column(Integer, ForeignKey("users.id"), nullable=True)

    created_at = Column(DateTime, default=datetime.utcnow, nullable=False)

    order = relationship("Order", back_populates="events")


class OrderItem(Base):
    __tablename__ = "order_items"
    id = Column(Integer, primary_key=True)
    order_id = Column(Integer, ForeignKey("orders.id"), nullable=False, index=True)

    product_id = Column(Integer, ForeignKey("products.id"), nullable=False)
    name_snapshot = Column(String(200), nullable=False)
    price_snapshot = Column(Float, nullable=False)
    qty = Column(Integer, nullable=False)

    order = relationship("Order", back_populates="items")


Index("ix_partner_locations_partner_created", PartnerLocation.partner_id, PartnerLocation.created_at)
Index("ix_order_events_order_created", OrderEvent.order_id, OrderEvent.created_at)
