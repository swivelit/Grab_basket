from __future__ import annotations

from datetime import datetime, time
from typing import Any, Optional

from pydantic import BaseModel, ConfigDict, EmailStr, Field, field_validator, model_validator


ALLOWED_REGISTER_ROLES = {"CUSTOMER", "SELLER", "PARTNER", "ADMIN"}
ALLOWED_PAYMENT_METHODS_CREATE = {"COD", "UPI", "CARD"}
ALLOWED_PAYMENT_METHODS_VERIFY = {"UPI", "CARD"}
ALLOWED_FCM_PLATFORMS = {"android", "ios", "web", "unknown"}


class ORMModel(BaseModel):
    model_config = ConfigDict(from_attributes=True)


def normalize_phone_value(value: Any, *, allow_blank: bool = False) -> str:
    raw = str(value or "").strip()
    if not raw:
        if allow_blank:
            return ""
        raise ValueError("phone is required")

    digits = "".join(ch for ch in raw if ch.isdigit())
    if not digits:
        if allow_blank:
            return ""
        raise ValueError("phone is required")

    if len(digits) == 10:
        digits = f"91{digits}"
    elif len(digits) == 11 and digits.startswith("0"):
        digits = f"91{digits[-10:]}"

    if len(digits) < 10 or len(digits) > 15:
        raise ValueError("phone must contain 10 to 15 digits")

    return f"+{digits}"


# ---------- Auth ----------
class RegisterIn(BaseModel):
    email: EmailStr
    phone: str = Field(min_length=10, max_length=24)
    password: str = Field(min_length=6, max_length=72)
    role: str  # CUSTOMER / SELLER / PARTNER / ADMIN
    device_id: str = Field(default="", max_length=255)

    @field_validator("phone", mode="before")
    @classmethod
    def normalize_phone(cls, value: Any) -> str:
        return normalize_phone_value(value)

    @field_validator("role", mode="before")
    @classmethod
    def normalize_role(cls, value: Any) -> str:
        role = str(value or "").strip().upper()
        if role not in ALLOWED_REGISTER_ROLES:
            raise ValueError(f"role must be one of: {', '.join(sorted(ALLOWED_REGISTER_ROLES))}")
        return role


class LoginIn(BaseModel):
    email: Optional[EmailStr] = None
    phone: Optional[str] = Field(default=None, max_length=24)
    password: str = Field(min_length=6, max_length=72)
    device_id: str = Field(default="", max_length=255)

    @field_validator("phone", mode="before")
    @classmethod
    def normalize_phone(cls, value: Any) -> Optional[str]:
        normalized = normalize_phone_value(value, allow_blank=True)
        return normalized or None

    @model_validator(mode="after")
    def validate_identifier(self):
        if self.email or self.phone:
            return self
        raise ValueError("email or phone is required")


class Token(BaseModel):
    access_token: str
    refresh_token: Optional[str] = None
    token_type: str = "bearer"
    access_token_expires_in: int
    refresh_token_expires_in: Optional[int] = None
    role: str


class RefreshTokenIn(BaseModel):
    refresh_token: str = Field(min_length=20, max_length=4096)
    device_id: str = Field(default="", max_length=255)


class LogoutIn(BaseModel):
    refresh_token: str = Field(min_length=20, max_length=4096)
    device_id: str = Field(default="", max_length=255)


class AuthChallengeCreateIn(BaseModel):
    challenge_type: str = Field(pattern="^(EMAIL_VERIFY|PASSWORD_RESET|PHONE_OTP)$")
    target: str = Field(min_length=3, max_length=255)


class AuthChallengeVerifyIn(BaseModel):
    challenge_id: int
    code: str = Field(min_length=4, max_length=16)


# ---------- FCM ----------
class FcmRegisterIn(BaseModel):
    token: str = Field(min_length=10, max_length=4096)
    platform: str = Field(default="unknown", max_length=32)

    @field_validator("platform", mode="before")
    @classmethod
    def normalize_platform(cls, value: Any) -> str:
        platform = str(value or "unknown").strip().lower()
        if platform not in ALLOWED_FCM_PLATFORMS:
            return "unknown"
        return platform


# ---------- Vendor / Product ----------
class VendorOut(ORMModel):
    id: int
    seller_id: int

    name: str
    description: str
    address: str
    slug: str = ""

    logo_image_url: str = ""
    cover_image_url: str = ""
    banner_image_url: str = ""
    cuisine_tags: str = ""
    price_bucket: str = ""

    support_phone: str = ""
    support_email: str = ""
    gstin: str = ""

    lat: Optional[float] = None
    lng: Optional[float] = None
    delivery_radius_km: float = 5.0

    min_order_amount: float = 0.0
    packaging_fee: float = 0.0
    estimated_delivery_time_min: int = 30
    avg_prep_time_min: int = 15
    avg_rating: float = 0.0
    total_ratings: int = 0

    is_open: bool
    is_accepting_orders: bool = True
    is_busy: bool = False
    accepts_cod: bool = True
    open_time: Optional[time] = None
    close_time: Optional[time] = None

    created_at: datetime
    updated_at: datetime

    # computed / annotated by router
    distance_km: Optional[float] = None
    can_deliver: Optional[bool] = None
    open_now: Optional[bool] = None


class ProductOut(ORMModel):
    id: int
    vendor_id: int

    name: str
    description: str
    category: str = ""
    subcategory: str = ""
    sku: str = ""
    barcode: str = ""

    image_url: str = ""
    thumbnail_url: str = ""
    badge_text: str = ""
    unit_label: str = ""

    price: float
    original_price: Optional[float] = None
    tax_rate_percent: float = 0.0

    is_available: bool
    is_featured: bool = False
    stock_qty: int = 0
    max_qty_per_order: int = 20
    sort_order: int = 0

    avg_rating: float = 0.0
    total_ratings: int = 0

    created_at: datetime
    updated_at: datetime


class ProductCreateIn(BaseModel):
    name: str = Field(min_length=2, max_length=200)
    description: str = Field(default="", max_length=4000)
    category: str = Field(default="", max_length=120)
    subcategory: str = Field(default="", max_length=120)
    sku: str = Field(default="", max_length=64)
    barcode: str = Field(default="", max_length=64)

    image_url: str = Field(default="", max_length=2048)
    thumbnail_url: str = Field(default="", max_length=2048)
    badge_text: str = Field(default="", max_length=64)
    unit_label: str = Field(default="", max_length=64)

    price: float = Field(gt=0, le=200000)
    original_price: Optional[float] = Field(default=None, gt=0, le=200000)
    tax_rate_percent: float = Field(default=0.0, ge=0, le=100)

    is_available: bool = True
    is_featured: bool = False
    stock_qty: int = Field(default=0, ge=0, le=1000000)
    max_qty_per_order: int = Field(default=20, ge=1, le=200)
    sort_order: int = Field(default=0, ge=0, le=1000000)

    @field_validator("name", "description", "category", "subcategory", "sku", "barcode", "badge_text", "unit_label", mode="before")
    @classmethod
    def normalize_text_fields(cls, value: Any) -> str:
        return str(value or "").strip()

    @field_validator("image_url", "thumbnail_url", mode="before")
    @classmethod
    def normalize_urls(cls, value: Any) -> str:
        return str(value or "").strip()


class ProductUpdateIn(BaseModel):
    name: Optional[str] = Field(default=None, min_length=2, max_length=200)
    description: Optional[str] = Field(default=None, max_length=4000)
    category: Optional[str] = Field(default=None, max_length=120)
    subcategory: Optional[str] = Field(default=None, max_length=120)
    sku: Optional[str] = Field(default=None, max_length=64)
    barcode: Optional[str] = Field(default=None, max_length=64)

    image_url: Optional[str] = Field(default=None, max_length=2048)
    thumbnail_url: Optional[str] = Field(default=None, max_length=2048)
    badge_text: Optional[str] = Field(default=None, max_length=64)
    unit_label: Optional[str] = Field(default=None, max_length=64)

    price: Optional[float] = Field(default=None, gt=0, le=200000)
    original_price: Optional[float] = Field(default=None, gt=0, le=200000)
    tax_rate_percent: Optional[float] = Field(default=None, ge=0, le=100)

    is_available: Optional[bool] = None
    is_featured: Optional[bool] = None
    stock_qty: Optional[int] = Field(default=None, ge=0, le=1000000)
    max_qty_per_order: Optional[int] = Field(default=None, ge=1, le=200)
    sort_order: Optional[int] = Field(default=None, ge=0, le=1000000)

    @field_validator("name", "description", "category", "subcategory", "sku", "barcode", "badge_text", "unit_label", mode="before")
    @classmethod
    def normalize_optional_text_fields(cls, value: Any) -> Optional[str]:
        if value is None:
            return None
        return str(value).strip()

    @field_validator("image_url", "thumbnail_url", mode="before")
    @classmethod
    def normalize_optional_urls(cls, value: Any) -> Optional[str]:
        if value is None:
            return None
        return str(value).strip()


class VendorUpdateIn(BaseModel):
    name: Optional[str] = Field(default=None, min_length=2, max_length=200)
    description: Optional[str] = Field(default=None, max_length=5000)
    address: Optional[str] = Field(default=None, max_length=500)

    slug: Optional[str] = Field(default=None, max_length=220)

    logo_image_url: Optional[str] = Field(default=None, max_length=2048)
    cover_image_url: Optional[str] = Field(default=None, max_length=2048)
    banner_image_url: Optional[str] = Field(default=None, max_length=2048)
    cuisine_tags: Optional[str] = Field(default=None, max_length=1000)
    price_bucket: Optional[str] = Field(default=None, max_length=8)

    support_phone: Optional[str] = Field(default=None, max_length=24)
    support_email: Optional[str] = Field(default=None, max_length=255)
    gstin: Optional[str] = Field(default=None, max_length=32)

    lat: Optional[float] = Field(default=None, ge=-90, le=90)
    lng: Optional[float] = Field(default=None, ge=-180, le=180)
    delivery_radius_km: Optional[float] = Field(default=None, gt=0, le=100)

    min_order_amount: Optional[float] = Field(default=None, ge=0, le=200000)
    packaging_fee: Optional[float] = Field(default=None, ge=0, le=50000)
    estimated_delivery_time_min: Optional[int] = Field(default=None, ge=1, le=300)
    avg_prep_time_min: Optional[int] = Field(default=None, ge=1, le=300)

    is_open: Optional[bool] = None
    is_accepting_orders: Optional[bool] = None
    is_busy: Optional[bool] = None
    accepts_cod: Optional[bool] = None

    open_time: Optional[time] = None
    close_time: Optional[time] = None

    @field_validator(
        "name",
        "description",
        "address",
        "slug",
        "logo_image_url",
        "cover_image_url",
        "banner_image_url",
        "cuisine_tags",
        "price_bucket",
        "support_phone",
        "support_email",
        "gstin",
        mode="before",
    )
    @classmethod
    def normalize_vendor_text_fields(cls, value: Any) -> Optional[str]:
        if value is None:
            return None
        return str(value).strip()


# ---------- Address ----------
class AddressCreateIn(BaseModel):
    label: str = Field(default="Home", min_length=1, max_length=64)
    recipient_name: str = Field(default="", max_length=120)
    contact_phone: str = Field(default="", max_length=24)

    line1: str = Field(min_length=3, max_length=200)
    line2: str = Field(default="", max_length=200)
    landmark: str = Field(default="", max_length=255)
    city: str = Field(default="", max_length=64)
    pincode: str = Field(default="", max_length=16)
    delivery_instructions: str = Field(default="", max_length=1000)

    lat: float = Field(ge=-90, le=90)
    lng: float = Field(ge=-180, le=180)

    is_default: bool = False

    @field_validator(
        "label",
        "recipient_name",
        "contact_phone",
        "line1",
        "line2",
        "landmark",
        "city",
        "pincode",
        "delivery_instructions",
        mode="before",
    )
    @classmethod
    def normalize_address_fields(cls, value: Any) -> str:
        return str(value or "").strip()


class AddressOut(ORMModel):
    id: int
    customer_id: int

    label: str
    recipient_name: str = ""
    contact_phone: str = ""

    line1: str
    line2: str = ""
    landmark: str = ""
    city: str = ""
    pincode: str = ""
    delivery_instructions: str = ""

    lat: float
    lng: float

    is_default: bool

    created_at: datetime
    updated_at: datetime


# ---------- Orders ----------
class OrderItemIn(BaseModel):
    product_id: int
    qty: int = Field(ge=1, le=50)


class OrderCreateIn(BaseModel):
    vendor_id: int
    items: list[OrderItemIn]
    delivery_address_id: Optional[int] = None
    payment_method: str = Field(default="COD", max_length=16)  # COD / UPI / CARD

    @field_validator("payment_method", mode="before")
    @classmethod
    def validate_payment_method(cls, value: Any) -> str:
        normalized = str(value or "COD").strip().upper()
        if normalized not in ALLOWED_PAYMENT_METHODS_CREATE:
            raise ValueError(
                f"payment_method must be one of: {', '.join(sorted(ALLOWED_PAYMENT_METHODS_CREATE))}"
            )
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
    def validate_payment_method(cls, value: Any) -> str:
        normalized = str(value or "").strip().upper()
        if normalized not in ALLOWED_PAYMENT_METHODS_VERIFY:
            raise ValueError(
                f"payment_method must be one of: {', '.join(sorted(ALLOWED_PAYMENT_METHODS_VERIFY))}"
            )
        return normalized

    @field_validator("reference", mode="before")
    @classmethod
    def normalize_reference(cls, value: Any) -> str:
        return str(value or "").strip().upper()

    @field_validator("upi_id", mode="before")
    @classmethod
    def normalize_upi_id(cls, value: Any) -> Optional[str]:
        text = str(value or "").strip().lower()
        return text or None

    @field_validator("card_holder_name", mode="before")
    @classmethod
    def normalize_card_holder_name(cls, value: Any) -> Optional[str]:
        text = str(value or "").strip()
        return text or None

    @field_validator("card_last4", mode="before")
    @classmethod
    def normalize_card_last4(cls, value: Any) -> Optional[str]:
        raw = "".join(ch for ch in str(value or "") if ch.isdigit())
        return raw[-4:] if raw else None


class PaymentCheckoutSessionIn(BaseModel):
    return_url: Optional[str] = Field(default=None, max_length=1024)

    @field_validator("return_url", mode="before")
    @classmethod
    def normalize_return_url(cls, value: Any) -> Optional[str]:
        text = str(value or "").strip()
        return text or None


class OrderItemOut(ORMModel):
    id: int
    product_id: int

    name_snapshot: str
    price_snapshot: float
    image_snapshot: str = ""
    unit_snapshot: str = ""
    sku_snapshot: str = ""
    qty: int
    line_total_amount: float = 0.0
    variant_snapshot: str = ""


class OrderEventOut(ORMModel):
    id: int
    status: str
    note: str
    actor_user_id: Optional[int] = None
    metadata_json: str = ""
    created_at: datetime


class OrderOut(ORMModel):
    id: int

    vendor_id: int
    customer_id: int
    partner_id: Optional[int] = None

    status: str
    order_source: str = "APP"
    customer_note: str = ""
    seller_note: str = ""
    internal_note: str = ""
    cancellation_reason: str = ""

    delivery_address_id: Optional[int] = None
    delivery_lat: Optional[float] = None
    delivery_lng: Optional[float] = None
    delivery_eta_minutes: Optional[int] = None
    delivery_distance_km: Optional[float] = None

    subtotal_amount: float = 0.0
    delivery_fee: float = 0.0
    packaging_fee: float = 0.0
    tax_amount: float = 0.0
    discount_amount: float = 0.0
    total_amount: float = 0.0

    payment_method: str
    payment_status: str
    payment_provider: str = ""
    payment_ref: Optional[str] = None
    refund_status: str = "NOT_APPLICABLE"
    refund_ref: Optional[str] = None

    accepted_at: Optional[datetime] = None
    assigned_at: Optional[datetime] = None
    ready_for_pickup_at: Optional[datetime] = None
    picked_up_at: Optional[datetime] = None
    delivered_at: Optional[datetime] = None
    cancelled_at: Optional[datetime] = None

    created_at: datetime
    updated_at: datetime

    items: list[OrderItemOut] = Field(default_factory=list)
    events: list[OrderEventOut] = Field(default_factory=list)


class PaymentVerifyOut(BaseModel):
    ok: bool
    payment_status: str
    payment_ref: Optional[str] = None
    verification_token: str
    order: OrderOut


class PaymentCheckoutSessionOut(BaseModel):
    ok: bool
    provider: str
    checkout_url: str
    provider_reference: str
    payment_status: str
    checkout_status: str
    expires_at: Optional[datetime] = None
    order: OrderOut


class PaymentStatusOut(BaseModel):
    ok: bool
    provider: str
    payment_status: str
    checkout_status: str
    should_retry: bool
    provider_reference: Optional[str] = None
    provider_payment_id: Optional[str] = None
    order: OrderOut


class OrderTrackingOut(BaseModel):
    order: OrderOut
    partner_latest_location: Optional[dict[str, Any]] = None  # {lat,lng,heading,speed,created_at}


# ---------- Partner ----------
class PartnerLocationIn(BaseModel):
    lat: float = Field(ge=-90, le=90)
    lng: float = Field(ge=-180, le=180)
    heading: Optional[float] = Field(default=None, ge=0, le=360)
    speed: Optional[float] = Field(default=None, ge=0, le=100)


class PartnerLocationOut(ORMModel):
    id: int
    partner_id: int
    lat: float
    lng: float
    heading: Optional[float] = None
    speed: Optional[float] = None
    accuracy_meters: Optional[float] = None
    battery_level: Optional[float] = None
    is_mocked: bool = False
    created_at: datetime


# ---------- Reviews ----------
class OrderReviewCreateIn(BaseModel):
    order_id: int
    rating: int = Field(ge=1, le=5)
    review_text: str = Field(default="", max_length=4000)
    tags: list[str] = Field(default_factory=list, max_length=12)

    @field_validator("review_text", mode="before")
    @classmethod
    def normalize_review_text(cls, value: Any) -> str:
        return str(value or "").strip()

    @field_validator("tags", mode="before")
    @classmethod
    def normalize_tags(cls, value: Any) -> list[str]:
        if value is None:
            return []
        if isinstance(value, str):
            raw_items = [part.strip() for part in value.split(",")]
        elif isinstance(value, list):
            raw_items = [str(item or "").strip() for item in value]
        else:
            return []
        cleaned: list[str] = []
        for item in raw_items:
            if not item:
                continue
            if item not in cleaned:
                cleaned.append(item[:64])
        return cleaned[:12]


class OrderReviewOut(ORMModel):
    id: int
    order_id: int
    vendor_id: int
    customer_id: int
    rating: int
    review_text: str = ""
    tags: str = ""
    created_at: datetime
    updated_at: datetime


# ---------- Support ----------
class SupportTicketCreateIn(BaseModel):
    order_id: Optional[int] = None
    category: str = Field(default="GENERAL", max_length=64)
    subject: str = Field(default="", max_length=255)
    message: str = Field(min_length=4, max_length=4000)

    @field_validator("category", "subject", "message", mode="before")
    @classmethod
    def normalize_ticket_fields(cls, value: Any) -> str:
        return str(value or "").strip()


class SupportTicketOut(ORMModel):
    id: int
    order_id: Optional[int] = None
    customer_id: int
    vendor_id: Optional[int] = None
    category: str
    status: str
    subject: str
    message: str
    resolution_note: str = ""
    created_at: datetime
    updated_at: datetime
    closed_at: Optional[datetime] = None


# ---------- Offers / Coupons ----------
class CouponOut(ORMModel):
    id: int
    code: str
    title: str = ""
    description: str = ""
    discount_type: str
    discount_value: float
    max_discount_amount: float = 0.0
    min_order_amount: float = 0.0
    active: bool
    valid_from: Optional[datetime] = None
    valid_to: Optional[datetime] = None
    usage_limit_global: int = 0
    usage_limit_per_user: int = 1
    created_at: datetime
    updated_at: datetime


class CouponApplyIn(BaseModel):
    code: str = Field(min_length=2, max_length=64)
    order_amount: float = Field(gt=0, le=500000)

    @field_validator("code", mode="before")
    @classmethod
    def normalize_coupon_code(cls, value: Any) -> str:
        return str(value or "").strip().upper()


class CouponApplyOut(BaseModel):
    ok: bool
    code: str
    discount_amount: float
    final_amount: float
    message: str

# ---------- Loyalty ----------
class LoyaltyMembershipOut(ORMModel):
    id: int
    customer_id: int
    tier: str
    points_balance: int
    active: bool
    joined_at: datetime
    renewed_at: Optional[datetime] = None
    expires_at: Optional[datetime] = None