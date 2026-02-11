from fastapi import APIRouter, Depends, HTTPException
from sqlalchemy.orm import Session
from ..database import get_db, engine
from ..models import Base, User, Role
from ..schemas import RegisterIn, LoginIn, Token
from ..auth import hash_password, verify_password, create_access_token

router = APIRouter(prefix="/auth", tags=["auth"])

# Create tables (MVP). For production, use Alembic migrations.
Base.metadata.create_all(bind=engine)

@router.post("/register", response_model=Token)
def register(data: RegisterIn, db: Session = Depends(get_db)):
    existing = db.query(User).filter(User.email == data.email).first()
    if existing:
        raise HTTPException(status_code=400, detail="Email already registered")

    # Validate role
    try:
        role = Role(data.role).value
    except Exception:
        raise HTTPException(status_code=400, detail="Invalid role")

    user = User(email=data.email, password_hash=hash_password(data.password), role=role)
    db.add(user)
    db.commit()
    token = create_access_token(subject=user.email, role=user.role)
    return Token(access_token=token, role=user.role)

@router.post("/login", response_model=Token)
def login(data: LoginIn, db: Session = Depends(get_db)):
    user = db.query(User).filter(User.email == data.email).first()
    if not user or not verify_password(data.password, user.password_hash):
        raise HTTPException(status_code=401, detail="Invalid credentials")
    token = create_access_token(subject=user.email, role=user.role)
    return Token(access_token=token, role=user.role)
