from __future__ import annotations

from datetime import datetime, time
from typing import Optional, List

from pydantic import BaseModel, EmailStr, Field


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
    role: str


# ---------- FCM ----------
class FcmRegisterIn(BaseModel):
    token: str
    platform: str = "unknown"


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
    name: str
    description: str = ""
    price: float
    is_available: bool = True


class ProductUpdateIn(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    price: Optional[float] = None
    is_available: Optional[bool] = None


class VendorUpdateIn(BaseModel):
    name: Optional[str] = None
    description: Optional[str] = None
    address: Optional[str] = None
    lat: Optional[float] = None
    lng: Optional[float] = None
    delivery_radius_km: Optional[float] = None
    is_open: Optional[bool] = None
    open_time: Optional[time] = None
    close_time: Optional[time] = None


# ---------- Address ----------
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
    qty: int = Field(ge=1, le=50)


class OrderCreateIn(BaseModel):
    vendor_id: int
    items: List[OrderItemIn]
    delivery_address_id: Optional[int] = None
    payment_method: str = "COD"  # COD / UPI


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
    lat: float
    lng: float
    heading: Optional[float] = None
    speed: Optional[float] = None
