from pydantic_settings import BaseSettings, SettingsConfigDict
from typing import List

class Settings(BaseSettings):
    model_config = SettingsConfigDict(env_file=".env", extra="ignore")

    database_url: str = "postgresql+psycopg://grabbasket:grabbasket@localhost:5432/grabbasket"
    jwt_secret: str = "change-me"
    access_token_expire_minutes: int = 120
    cors_origins: str = "http://localhost:3000"

    def cors_origin_list(self) -> List[str]:
        return [o.strip() for o in self.cors_origins.split(",") if o.strip()]

settings = Settings()
