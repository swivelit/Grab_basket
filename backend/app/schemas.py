from __future__ import annotations

from datetime import datetime, time
from typing import Optional, List

from pydantic import BaseModel, EmailStr, Field, field_validator


# ---------- Auth ----------
class RegisterIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=6, max_length=72)
    role: str  # CUSTOMER / SELLER / PARTNER / ADMIN


class LoginIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=6, max_length=72)


class Token(BaseModel):
    access_token: str
    refresh_token: Optional[str] = None
    token_type: str = "bearer"
    access_token_expires_in: int
    refresh_token_expires_in: Optional[int] = None
    role: str


class RefreshTokenIn(BaseModel):
    refresh_token: str = Field(min_length=20, max_length=4096)


class LogoutIn(BaseModel):
    refresh_token: str = Field(min_length=20, max_length=4096)


# ---------- FCM ----------
class FcmRegisterIn(BaseModel):
    token: str = Field(min_length=10, max_length=4096)
    platform: str = Field(default="unknown", max_length=32)


# ---------- Vendor / Product ----------
class VendorOut(BaseModel):
    id: int
    name: str
    description: str
    address: str
    lat: Optional[float] = None
    lng: Optional[float] = None
    delivery_radius_km: float
    is_open: bool
    open_time: Optional[time] = None
    close_time: Optional[time] = None

    # computed (optional)
    distance_km: Optional[float] = None
    can_deliver: Optional[bool] = None
    open_now: Optional[bool] = None

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


class ProductCreateIn(BaseModel):
    name: str = Field(min_length=2, max_length=120)
    description: str = Field(default="", max_length=2000)
    price: float = Field(gt=0, le=200000)
    is_available: bool = True


class ProductUpdateIn(BaseModel):
    name: Optional[str] = Field(default=None, min_length=2, max_length=120)
    description: Optional[str] = Field(default=None, max_length=2000)
    price: Optional[float] = Field(default=None, gt=0, le=200000)
    is_available: Optional[bool] = None


class VendorUpdateIn(BaseModel):
    name: Optional[str] = Field(default=None, min_length=2, max_length=120)
    description: Optional[str] = Field(default=None, max_length=5000)
    address: Optional[str] = Field(default=None, max_length=500)
    lat: Optional[float] = Field(default=None, ge=-90, le=90)
    lng: Optional[float] = Field(default=None, ge=-180, le=180)
    delivery_radius_km: Optional[float] = Field(default=None, gt=0, le=50)
    is_open: Optional[bool] = None
    open_time: Optional[time] = None
    close_time: Optional[time] = None


# ---------- Address ----------
class AddressCreateIn(BaseModel):
    label: str = Field(default="Home", min_length=1, max_length=32)
    line1: str = Field(min_length=3, max_length=200)
    line2: str = Field(default="", max_length=200)
    city: str = Field(default="", max_length=64)
    pincode: str = Field(default="", max_length=12)
    lat: float = Field(ge=-90, le=90)
    lng: float = Field(ge=-180, le=180)
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
    qty: int = Field(ge=1, le=50)


class OrderCreateIn(BaseModel):
    vendor_id: int
    items: List[OrderItemIn]
    delivery_address_id: Optional[int] = None
    payment_method: str = Field(default="COD", max_length=16)  # COD / UPI / CARD

    @field_validator("payment_method", mode="before")
    @classmethod
    def validate_payment_method(cls, value):
        normalized = str(value or "COD").strip().upper()
        allowed = {"COD", "UPI", "CARD"}
        if normalized not in allowed:
            raise ValueError(f"payment_method must be one of: {', '.join(sorted(allowed))}")
        return normalized


class PaymentVerifyIn(BaseModel):
    payment_method: str = Field(max_length=16)
    reference: str = Field(min_length=4, max_length=128)
    amount: Optional[float] = Field(default=None, gt=0)
    upi_id: Optional[str] = Field(default=None, max_length=120)
    card_holder_name: Optional[str] = Field(default=None, max_length=120)
    card_last4: Optional[str] = Field(default=None, min_length=4, max_length=4)

    @field_validator("payment_method", mode="before")
    @classmethod
    def validate_payment_method(cls, value):
        normalized = str(value or "").strip().upper()
        allowed = {"UPI", "CARD"}
        if normalized not in allowed:
            raise ValueError(f"payment_method must be one of: {', '.join(sorted(allowed))}")
        return normalized

    @field_validator("reference", mode="before")
    @classmethod
    def normalize_reference(cls, value):
        return str(value or "").strip().upper()

    @field_validator("upi_id", mode="before")
    @classmethod
    def normalize_upi_id(cls, value):
        return str(value or "").strip().lower() or None

    @field_validator("card_holder_name", mode="before")
    @classmethod
    def normalize_card_holder_name(cls, value):
        return str(value or "").strip() or None

    @field_validator("card_last4", mode="before")
    @classmethod
    def normalize_card_last4(cls, value):
        raw = ''.join(ch for ch in str(value or '') if ch.isdigit())
        return raw[-4:] if raw else None


class PaymentVerifyOut(BaseModel):
    ok: bool
    payment_status: str
    payment_ref: Optional[str] = None
    verification_token: str
    order: "OrderOut"


class OrderItemOut(BaseModel):
    product_id: int
    name_snapshot: str
    price_snapshot: float
    qty: int

    class Config:
        from_attributes = True


class OrderEventOut(BaseModel):
    status: str
    note: str
    actor_user_id: Optional[int] = None
    created_at: datetime

    class Config:
        from_attributes = True


class OrderOut(BaseModel):
    id: int
    vendor_id: int
    customer_id: int
    partner_id: Optional[int]
    status: str

    delivery_address_id: Optional[int] = None
    delivery_lat: Optional[float] = None
    delivery_lng: Optional[float] = None

    delivery_fee: float
    subtotal_amount: float
    total_amount: float

    payment_method: str
    payment_status: str
    payment_ref: Optional[str] = None

    items: List[OrderItemOut]
    events: List[OrderEventOut] = []

    class Config:
        from_attributes = True


class OrderTrackingOut(BaseModel):
    order: OrderOut
    partner_latest_location: Optional[dict] = None  # {lat,lng,heading,speed,created_at}


# ---------- Partner ----------
class PartnerLocationIn(BaseModel):
    lat: float = Field(ge=-90, le=90)
    lng: float = Field(ge=-180, le=180)
    heading: Optional[float] = Field(default=None, ge=0, le=360)
    speed: Optional[float] = Field(default=None, ge=0, le=100)