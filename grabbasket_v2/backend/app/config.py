from __future__ import annotations

import json
from typing import List

from pydantic import AliasChoices, Field, field_validator, model_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Central application configuration.

    Values can be overridden via environment variables (or a .env file for local dev).

    Notes:
    - We support both ACCESS_TOKEN_MINUTES and the legacy ACCESS_TOKEN_EXPIRE_MINUTES.
    - CORS_ORIGINS may be provided as a JSON array or as a comma-separated string.
    """

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # Runtime
    APP_ENV: str = Field(default="dev", description="dev / staging / prod")
    LOG_LEVEL: str = Field(default="INFO")

    # DB + Auth
    DATABASE_URL: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"
    JWT_SECRET: str = "dev-secret-change-me"
    JWT_ALG: str = "HS256"

    # Backward-compatible env var: ACCESS_TOKEN_EXPIRE_MINUTES (used in some docker-compose files)
    ACCESS_TOKEN_MINUTES: int = Field(
        default=60 * 24 * 7,
        validation_alias=AliasChoices("ACCESS_TOKEN_MINUTES", "ACCESS_TOKEN_EXPIRE_MINUTES"),
        description="Access token expiry (minutes).",
    )

    # CORS (use a JSON array in env, e.g. CORS_ORIGINS='["https://example.com"]' OR comma-separated)
    CORS_ORIGINS: List[str] = Field(default_factory=lambda: ["*"])

    # Optional FCM:
    # Either provide a JSON string or mount a file and set FCM_SERVICE_ACCOUNT_FILE
    FCM_SERVICE_ACCOUNT_JSON: str | None = None
    FCM_SERVICE_ACCOUNT_FILE: str | None = None

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
                    # fall back to comma-separated
                    pass
            return [x.strip() for x in s.split(",") if x.strip()]
        return ["*"]

    @property
    def is_prod(self) -> bool:
        return self.APP_ENV.lower() == "prod"

    @model_validator(mode="after")
    def _validate_prod_safety(self):
        # Light production guardrails.
        if self.is_prod:
            weak = {"dev-secret-change-me", "change-me", "change-me-in-prod"}
            if self.JWT_SECRET in weak or len(self.JWT_SECRET) < 16:
                raise ValueError("JWT_SECRET must be set to a strong secret in production")
            if self.CORS_ORIGINS == ["*"]:
                raise ValueError("CORS_ORIGINS must not be '*' in production")
        return self


settings = Settings()
