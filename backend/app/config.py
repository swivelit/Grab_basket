from __future__ import annotations

import json
import os
from typing import List
from urllib.parse import urlparse

from pydantic import AliasChoices, Field, field_validator, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


def _settings_env_file():
    disable_dotenv = str(os.getenv("GRABBASKET_DISABLE_DOTENV", "")).strip().lower()
    if disable_dotenv in {"1", "true", "yes", "on"}:
        return None

    env_file_override = os.getenv("GRABBASKET_ENV_FILE")
    if env_file_override is not None:
        value = str(env_file_override).strip()
        return value or None

    return ".env"


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    APP_ENV: str = Field(default="dev")
    LOG_LEVEL: str = Field(default="INFO")
    LOG_JSON: bool = Field(default=False)

    DATABASE_URL: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"

    JWT_SECRET: str = "dev-secret-change-me"
    JWT_ALG: str = "HS256"

    ACCESS_TOKEN_MINUTES: int = Field(
        default=60 * 24,
        validation_alias=AliasChoices("ACCESS_TOKEN_MINUTES", "ACCESS_TOKEN_EXPIRE_MINUTES"),
    )
    REFRESH_TOKEN_MINUTES: int = Field(default=60 * 24 * 30)
    REFRESH_TOKEN_BYTES: int = Field(default=48)

    RUN_DB_CREATE_ON_STARTUP: bool = Field(default=False)
    SECURITY_HEADERS_ENABLED: bool = Field(default=True)

    # Mobile app does not need browser CORS restrictions.
    # Keep this configurable for future web/admin panels.
    CORS_ORIGINS: str | List[str] = Field(default="*")

    FCM_SERVICE_ACCOUNT_JSON: str | None = None
    FCM_SERVICE_ACCOUNT_FILE: str | None = None

    PUBLIC_BASE_URL: str | None = None

    RAZORPAY_KEY_ID: str | None = None
    RAZORPAY_KEY_SECRET: str | None = None
    RAZORPAY_WEBHOOK_SECRET: str | None = None
    PAYMENT_LINK_EXPIRE_MINUTES: int = Field(default=30)
    PAYMENT_LINK_REUSE_MINUTES: int = Field(default=15)
    RELEASE_STAGED_ROLLOUT_PERCENT: int = Field(default=10, ge=1, le=100)
    RELEASE_CRASH_FREE_MIN_PERCENT: float = Field(default=99.5, ge=90.0, le=100.0)
    RELEASE_CHECKOUT_SUCCESS_MIN_PERCENT: float = Field(default=98.0, ge=80.0, le=100.0)
    RELEASE_AUTO_ROLLBACK_ENABLED: bool = Field(default=True)

    @field_validator("APP_ENV", mode="before")
    @classmethod
    def _normalize_app_env(cls, v):
        value = str(v or "dev").strip().lower()
        aliases = {
            "prod": "production",
            "release": "production",
            "dev": "development",
            "debug": "development",
            "stage": "staging",
        }
        return aliases.get(value, value or "development")

    @field_validator("DATABASE_URL", mode="before")
    @classmethod
    def _normalize_database_url(cls, v):
        if not isinstance(v, str):
            return v

        s = v.strip()

        if s.startswith("postgresql+psycopg://"):
            return s

        if s.startswith("postgres://"):
            return "postgresql+psycopg://" + s[len("postgres://"):]

        if s.startswith("postgresql://"):
            return "postgresql+psycopg://" + s[len("postgresql://"):]

        return s

    @field_validator("CORS_ORIGINS", mode="before")
    @classmethod
    def _parse_cors(cls, v):
        if v is None:
            return ["*"]

        if isinstance(v, list):
            items = [str(x).strip() for x in v if str(x).strip()]
            return items or ["*"]

        if isinstance(v, str):
            s = v.strip()

            if not s:
                return ["*"]

            if s == "*":
                return ["*"]

            if s.startswith("["):
                try:
                    parsed = json.loads(s)
                    if isinstance(parsed, list):
                        items = [str(x).strip() for x in parsed if str(x).strip()]
                        return items or ["*"]
                except Exception:
                    pass

            items = [x.strip() for x in s.split(",") if x.strip()]
            return items or ["*"]

        return ["*"]

    @field_validator("PUBLIC_BASE_URL", mode="before")
    @classmethod
    def _normalize_public_base_url(cls, v):
        text = str(v or "").strip()
        return text.rstrip("/") or None

    @field_validator("RAZORPAY_KEY_ID", "RAZORPAY_KEY_SECRET", "RAZORPAY_WEBHOOK_SECRET", mode="before")
    @classmethod
    def _normalize_optional_secret(cls, v):
        text = str(v or "").strip()
        return text or None

    @property
    def is_prod(self) -> bool:
        return self.APP_ENV == "production"

    @property
    def razorpay_enabled(self) -> bool:
        return bool(self.RAZORPAY_KEY_ID and self.RAZORPAY_KEY_SECRET)

    @property
    def push_ready(self) -> bool:
        return bool(self.FCM_SERVICE_ACCOUNT_JSON or self.FCM_SERVICE_ACCOUNT_FILE)

    @property
    def payment_webhook_ready(self) -> bool:
        return bool(self.razorpay_enabled and self.PUBLIC_BASE_URL and self.RAZORPAY_WEBHOOK_SECRET)

    @property
    def release_readiness_report(self) -> dict:
        errors: list[str] = []
        warnings: list[str] = []

        if self.razorpay_enabled and not self.PUBLIC_BASE_URL:
            message = (
                "PUBLIC_BASE_URL is missing. Hosted Razorpay callbacks may use an internal/private backend URL instead of the public API domain."
            )
            if self.is_prod:
                errors.append(message)
            else:
                warnings.append(message)

        if self.razorpay_enabled and not self.RAZORPAY_WEBHOOK_SECRET:
            message = "RAZORPAY_WEBHOOK_SECRET is missing. Razorpay webhook verification is disabled."
            if self.is_prod:
                errors.append(message)
            else:
                warnings.append(message)

        if not self.push_ready:
            warnings.append(
                "FCM service-account credentials are not configured. Expo push tokens will still work, but direct native FCM sends are disabled."
            )

        if self.is_prod and self.RELEASE_STAGED_ROLLOUT_PERCENT >= 100:
            warnings.append("RELEASE_STAGED_ROLLOUT_PERCENT is 100 in production. Canary protection is effectively disabled.")

        if self.is_prod and not self.RELEASE_AUTO_ROLLBACK_ENABLED:
            errors.append("RELEASE_AUTO_ROLLBACK_ENABLED is false in production.")

        return {
            "errors": errors,
            "warnings": warnings,
            "components": {
                "payments": {
                    "razorpay_enabled": self.razorpay_enabled,
                    "public_base_url_configured": bool(self.PUBLIC_BASE_URL),
                    "webhook_secret_configured": bool(self.RAZORPAY_WEBHOOK_SECRET),
                    "webhook_ready": self.payment_webhook_ready,
                },
                "push": {
                    "fcm_service_account_configured": self.push_ready,
                },
                "release_governance": {
                    "staged_rollout_percent": self.RELEASE_STAGED_ROLLOUT_PERCENT,
                    "crash_free_min_percent": self.RELEASE_CRASH_FREE_MIN_PERCENT,
                    "checkout_success_min_percent": self.RELEASE_CHECKOUT_SUCCESS_MIN_PERCENT,
                    "auto_rollback_enabled": self.RELEASE_AUTO_ROLLBACK_ENABLED,
                },
            },
        }

    @model_validator(mode="after")
    def _validate_prod_safety(self):
        if self.is_prod:
            weak = {"dev-secret-change-me", "change-me", "change-me-in-prod"}
            if self.JWT_SECRET in weak or len(self.JWT_SECRET) < 32:
                raise ValueError("JWT_SECRET must be strong in production")
            if self.RUN_DB_CREATE_ON_STARTUP:
                raise ValueError("RUN_DB_CREATE_ON_STARTUP must be false in production")

        if self.ACCESS_TOKEN_MINUTES < 5:
            raise ValueError("ACCESS_TOKEN_MINUTES must be at least 5 minutes")
        if self.REFRESH_TOKEN_MINUTES <= self.ACCESS_TOKEN_MINUTES:
            raise ValueError("REFRESH_TOKEN_MINUTES must be greater than ACCESS_TOKEN_MINUTES")
        if self.REFRESH_TOKEN_BYTES < 32:
            raise ValueError("REFRESH_TOKEN_BYTES must be at least 32")

        if self.PAYMENT_LINK_EXPIRE_MINUTES < 15:
            raise ValueError("PAYMENT_LINK_EXPIRE_MINUTES must be at least 15 minutes")
        if self.PAYMENT_LINK_REUSE_MINUTES < 1:
            raise ValueError("PAYMENT_LINK_REUSE_MINUTES must be at least 1 minute")
        if self.PAYMENT_LINK_REUSE_MINUTES > self.PAYMENT_LINK_EXPIRE_MINUTES:
            raise ValueError("PAYMENT_LINK_REUSE_MINUTES cannot exceed PAYMENT_LINK_EXPIRE_MINUTES")

        if self.PUBLIC_BASE_URL:
            parsed = urlparse(self.PUBLIC_BASE_URL)
            if parsed.scheme not in {"http", "https"} or not parsed.netloc:
                raise ValueError("PUBLIC_BASE_URL must be a valid absolute http(s) URL")
            if self.is_prod and parsed.scheme != "https":
                raise ValueError("PUBLIC_BASE_URL must use HTTPS in production")

        if self.is_prod and self.razorpay_enabled and not self.PUBLIC_BASE_URL:
            raise ValueError(
                "PUBLIC_BASE_URL must be configured in production when Razorpay is enabled so hosted callbacks can return through the public API domain"
            )

        if self.is_prod and self.razorpay_enabled and not self.RAZORPAY_WEBHOOK_SECRET:
            raise ValueError("RAZORPAY_WEBHOOK_SECRET must be configured in production when Razorpay is enabled")

        return self

settings = Settings(_env_file=_settings_env_file())
