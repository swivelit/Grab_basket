from __future__ import annotations

from datetime import time, datetime
from pydantic import BaseModel, EmailStr, Field
from .models import Role, PaymentMethod, PaymentStatus, OrderStatus


# ---------- Auth ----------
class RegisterIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=4, max_length=128)
    role: Role


class LoginIn(BaseModel):
    email: EmailStr
    password: str


class Token(BaseModel):
    access_token: str
    role: Role


# ---------- Device tokens ----------
class DeviceTokenIn(BaseModel):
    token: str = Field(min_length=10, max_length=512)
    platform: str = "unknown"


# ---------- Vendors / Products ----------
class VendorOut(BaseModel):
    id: int
    name: str
    description: str
    address: str
    lat: float | None
    lng: float | None
    delivery_radius_km: float
    is_open: bool
    open_time: time | None
    close_time: time | None

    class Config:
        from_attributes = True


class ProductOut(BaseModel):
    id: int
    vendor_id: int
    name: str
    description: str
    price: float
    is_available: bool

    class Config:
        from_attributes = True


class SellerVendorUpsertIn(BaseModel):
    name: str
    description: str = ""
    address: str = ""
    lat: float | None = None
    lng: float | None = None
    delivery_radius_km: float = 5.0
    is_open: bool = True
    open_time: time | None = None
    close_time: time | None = None


class ProductCreateIn(BaseModel):
    name: str
    description: str = ""
    price: float = Field(gt=0)
    is_available: bool = True


class ProductUpdateIn(BaseModel):
    name: str | None = None
    description: str | None = None
    price: float | None = Field(default=None, gt=0)
    is_available: bool | None = None


# ---------- Addresses ----------
class AddressCreateIn(BaseModel):
    label: str = "Home"
    line1: str
    line2: str = ""
    city: str = ""
    pincode: str = ""
    lat: float
    lng: float
    is_default: bool = False


class AddressOut(BaseModel):
    id: int
    label: str
    line1: str
    line2: str
    city: str
    pincode: str
    lat: float
    lng: float
    is_default: bool

    class Config:
        from_attributes = True


# ---------- Orders ----------
class OrderItemIn(BaseModel):
    product_id: int
    qty: int = Field(gt=0)


class OrderCreateIn(BaseModel):
    vendor_id: int
    items: list[OrderItemIn]
    delivery_address_id: int | None = None
    delivery_lat: float | None = None
    delivery_lng: float | None = None
    payment_method: PaymentMethod = PaymentMethod.COD


class OrderItemOut(BaseModel):
    product_id: int
    name_snapshot: str
    price_snapshot: float
    qty: int

    class Config:
        from_attributes = True


class OrderOut(BaseModel):
    id: int
    vendor_id: int
    customer_id: int
    partner_id: int | None
    status: OrderStatus
    subtotal_amount: float
    delivery_fee: float
    total_amount: float
    payment_method: PaymentMethod
    payment_status: PaymentStatus
    created_at: datetime
    items: list[OrderItemOut]
    delivery_lat: float | None
    delivery_lng: float | None

    class Config:
        from_attributes = True


class OrderStatusUpdateIn(BaseModel):
    status: OrderStatus


# ---------- Partner live tracking ----------
class PartnerAvailabilityIn(BaseModel):
    is_available: bool


class PartnerLocationIn(BaseModel):
    lat: float
    lng: float
    heading: float | None = None
    speed: float | None = None


class PartnerLocationOut(BaseModel):
    lat: float
    lng: float
    heading: float | None
    speed: float | None
    created_at: datetime
