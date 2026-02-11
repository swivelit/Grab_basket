from __future__ import annotations
from typing import List, Optional
from pydantic import BaseModel, EmailStr, Field


class Token(BaseModel):
    access_token: str
    role: str


class RegisterIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=6)
    role: str  # CUSTOMER / SELLER / PARTNER / ADMIN


class LoginIn(BaseModel):
    email: EmailStr
    password: str


class VendorOut(BaseModel):
    id: int
    name: str
    description: str
    address: str
    lat: Optional[float] = None
    lng: Optional[float] = None
    delivery_radius_km: float
    is_open: bool
    open_time: Optional[str] = None
    close_time: Optional[str] = None


class ProductOut(BaseModel):
    id: int
    vendor_id: int
    name: str
    description: str
    price: float
    is_available: bool


class ProductUpsert(BaseModel):
    name: str
    description: str = ""
    price: float
    is_available: bool = True


class AddressIn(BaseModel):
    label: str = "Home"
    line1: str
    line2: str = ""
    city: str = ""
    pincode: str = ""
    lat: float
    lng: float
    is_default: bool = False


class AddressOut(AddressIn):
    id: int


class OrderCreateItem(BaseModel):
    product_id: int
    qty: int = Field(ge=1)


class OrderCreateIn(BaseModel):
    vendor_id: int
    items: List[OrderCreateItem]
    delivery_address_id: int
    payment_method: str = "COD"  # COD / UPI


class OrderItemOut(BaseModel):
    product_id: int
    name_snapshot: str
    price_snapshot: float
    qty: int


class OrderOut(BaseModel):
    id: int
    vendor_id: int
    customer_id: int
    partner_id: Optional[int] = None
    status: str
    subtotal_amount: float
    delivery_fee: float
    total_amount: float
    payment_method: str
    payment_status: str
    payment_ref: Optional[str] = None
    delivery_lat: Optional[float] = None
    delivery_lng: Optional[float] = None
    items: List[OrderItemOut]


class PartnerLocationIn(BaseModel):
    lat: float
    lng: float
    heading: Optional[float] = None
    speed: Optional[float] = None


class PartnerLocationOut(PartnerLocationIn):
    created_at: str


class VendorSettingsIn(BaseModel):
    lat: Optional[float] = None
    lng: Optional[float] = None
    delivery_radius_km: float = 5.0
    is_open: bool = True
    open_time: Optional[str] = None  # "09:00"
    close_time: Optional[str] = None # "23:00"
