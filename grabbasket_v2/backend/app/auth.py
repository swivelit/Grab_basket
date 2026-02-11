from datetime import datetime, timedelta
from jose import jwt
from passlib.context import CryptContext
from .settings import settings


pwd_context = CryptContext(schemes=["bcrypt"], deprecated="auto")


def hash_password(password: str) -> str:
    # bcrypt max 72 bytes (passlib will throw), keep it safe:
    password = password[:72]
    return pwd_context.hash(password)


def verify_password(password: str, password_hash: str) -> bool:
    password = password[:72]
    return pwd_context.verify(password, password_hash)


def create_access_token(subject: str, role: str) -> str:
    exp = datetime.utcnow() + timedelta(minutes=settings.jwt_exp_minutes)
    to_encode = {"sub": subject, "role": role, "exp": exp}
    return jwt.encode(to_encode, settings.jwt_secret, algorithm=settings.jwt_algorithm)
