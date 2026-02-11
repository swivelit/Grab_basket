from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", env_file_encoding="utf-8", extra="ignore")

    database_url: str = "postgresql+psycopg://postgres:postgres@db:5432/grabbasket"

    jwt_secret: str = "dev-secret-change-me"
    jwt_algorithm: str = "HS256"
    jwt_exp_minutes: int = 60 * 24 * 7  # 7 days

    # Delivery fee base (you can improve later)
    base_delivery_fee: float = 25.0

    # FCM (optional)
    firebase_service_account_json: str | None = None  # set env FIREBASE_SERVICE_ACCOUNT_JSON

    # Admin panel simple config
    admin_panel_enabled: bool = True


settings = Settings()
