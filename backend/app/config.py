from __future__ import annotations

import json
from typing import Annotated, List

from pydantic import AliasChoices, Field, field_validator, model_validator
from pydantic_settings import BaseSettings, NoDecode, SettingsConfigDict


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
        default=60 * 24 * 7,
        validation_alias=AliasChoices("ACCESS_TOKEN_MINUTES", "ACCESS_TOKEN_EXPIRE_MINUTES"),
    )

    CORS_ORIGINS: Annotated[List[str], NoDecode] = Field(default_factory=lambda: ["*"])

    FCM_SERVICE_ACCOUNT_JSON: str | None = None
    FCM_SERVICE_ACCOUNT_FILE: str | None = None

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
            return v
        if isinstance(v, str):
            s = v.strip()
            if not s:
                return ["*"]
            if s.startswith("["):
                try:
                    parsed = json.loads(s)
                    if isinstance(parsed, list):
                        return [str(x) for x in parsed if str(x).strip()]
                except Exception:
                    pass
            return [x.strip() for x in s.split(",") if x.strip()]
        return ["*"]

    @property
    def is_prod(self) -> bool:
        return self.APP_ENV.lower() == "prod"

    @model_validator(mode="after")
    def _validate_prod_safety(self):
        if self.is_prod:
            weak = {"dev-secret-change-me", "change-me", "change-me-in-prod"}
            if self.JWT_SECRET in weak or len(self.JWT_SECRET) < 16:
                raise ValueError("JWT_SECRET must be strong in production")
            if self.CORS_ORIGINS == ["*"]:
                raise ValueError("CORS_ORIGINS must not be '*' in production")
        return self


settings = Settings()