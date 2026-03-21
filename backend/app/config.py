from __future__ import annotations

import json
from typing import List

from pydantic import AliasChoices, Field, field_validator, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    APP_ENV: str = Field(default="dev")
    LOG_LEVEL: str = Field(default="INFO")

    DATABASE_URL: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"

    JWT_SECRET: str = "dev-secret-change-me"
    JWT_ALG: str = "HS256"

    ACCESS_TOKEN_MINUTES: int = Field(
        default=60 * 24,
        validation_alias=AliasChoices("ACCESS_TOKEN_MINUTES", "ACCESS_TOKEN_EXPIRE_MINUTES"),
    )
    REFRESH_TOKEN_MINUTES: int = Field(default=60 * 24 * 30)
    REFRESH_TOKEN_BYTES: int = Field(default=48)

    RUN_DB_CREATE_ON_STARTUP: bool = Field(default=True)
    SECURITY_HEADERS_ENABLED: bool = Field(default=True)

    # Mobile app does not need browser CORS restrictions.
    # Keep this configurable for future web/admin panels.
    CORS_ORIGINS: str | List[str] = Field(default="*")

    FCM_SERVICE_ACCOUNT_JSON: str | None = None
    FCM_SERVICE_ACCOUNT_FILE: str | None = None

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

    @property
    def is_prod(self) -> bool:
        return self.APP_ENV == "production"

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

        return self


settings = Settings()