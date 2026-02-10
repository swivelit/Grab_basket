from pydantic import BaseModel, EmailStr, Field
from typing import List, Optional
from .models import Role, OrderStatus

class Token(BaseModel):
    access_token: str
    token_type: str = "bearer"
    role: Role

class RegisterIn(BaseModel):
    email: EmailStr
    password: str = Field(min_length=8)
    role: Role

class LoginIn(BaseModel):
    email: EmailStr
    password: str

class VendorOut(BaseModel):
    id: int
    name: str
    description: str
    address: str
    is_active: bool
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

class ProductCreate(BaseModel):
    name: str
    description: str = ""
    price: float = Field(gt=0)
    is_available: bool = True

class OrderItemIn(BaseModel):
    product_id: int
    qty: int = Field(gt=0)

class OrderCreate(BaseModel):
    vendor_id: int
    items: List[OrderItemIn]

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
    partner_id: Optional[int]
    status: OrderStatus
    total_amount: float
    delivery_fee: float
    items: List[OrderItemOut]
    class Config:
        from_attributes = True
