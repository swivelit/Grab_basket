from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Central application configuration.

    Values can be overridden via environment variables (or a .env file for local dev).
    """

    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    # Runtime
    APP_ENV: str = "dev"  # dev / staging / prod
    LOG_LEVEL: str = "INFO"

    # DB + Auth
    DATABASE_URL: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"
    JWT_SECRET: str = "dev-secret-change-me"
    JWT_ALG: str = "HS256"
    ACCESS_TOKEN_MINUTES: int = 60 * 24 * 7  # 7 days

    # CORS (use a JSON array in env, e.g. CORS_ORIGINS='["https://example.com"]')
    CORS_ORIGINS: list[str] = ["*"]

    # Optional FCM:
    # Either provide a JSON string or mount a file and set FCM_SERVICE_ACCOUNT_FILE
    FCM_SERVICE_ACCOUNT_JSON: str | None = None
    FCM_SERVICE_ACCOUNT_FILE: str | None = None


settings = Settings()
