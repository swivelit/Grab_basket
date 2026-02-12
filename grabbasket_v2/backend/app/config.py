from __future__ import annotations

import json
from typing import List

from pydantic import Field, field_validator
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Centralized runtime configuration.

    Keep this as the *single* source of truth for settings.
    A few legacy modules still import from `app.settings`; those are now thin wrappers.
    """

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # Environment
    APP_ENV: str = Field(default="dev", description="dev / staging / prod")
    LOG_LEVEL: str = Field(default="INFO")

    # DB
    DATABASE_URL: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"

    # Auth
    JWT_SECRET: str = "dev-secret-change-me"
    JWT_ALG: str = "HS256"
    ACCESS_TOKEN_MINUTES: int = 60 * 24 * 7  # 7 days

    # CORS
    # Provide as JSON list (e.g. ["https://example.com"]) or comma-separated.
    CORS_ORIGINS: List[str] = Field(default_factory=lambda: ["*"])

    # Optional FCM
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
                    if isinstance(parsed, list) and parsed:
                        return [str(x) for x in parsed]
                except Exception:
                    pass
            return [x.strip() for x in s.split(",") if x.strip()]
        return ["*"]

    @property
    def is_prod(self) -> bool:
        return self.APP_ENV.lower() == "prod"


settings = Settings()
