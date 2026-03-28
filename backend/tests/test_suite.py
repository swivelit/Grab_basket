from __future__ import annotations

import json
import os
import unittest
from datetime import timedelta

os.environ.setdefault("APP_ENV", "development")
os.environ.setdefault("GRABBASKET_DISABLE_DOTENV", "1")
os.environ.setdefault("DATABASE_URL", "sqlite:///./test-suite.db")

from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from app.auth import hash_password, issue_auth_tokens, revoke_refresh_token, rotate_refresh_token
from app.models import AsyncJob, RefreshToken, User
from app.services.auth_security import create_challenge, verify_challenge
from app.time import utc_now
from app.worker import process_due_jobs


class BackendHardeningTests(unittest.TestCase):
    def setUp(self):
        from app.db import Base

        self.engine = create_engine("sqlite:///:memory:")
        Base.metadata.create_all(bind=self.engine)
        self.Session = sessionmaker(bind=self.engine)
        self.db = self.Session()

    def tearDown(self):
        self.db.close()

    def test_auth_login_refresh_logout_device_bound(self):
        user = User(email="a@example.com", password_hash=hash_password("secret123"), role="CUSTOMER")
        self.db.add(user)
        self.db.commit()
        self.db.refresh(user)

        issued = issue_auth_tokens(self.db, user, device_id="dev-1")
        self.db.commit()

        rotated = rotate_refresh_token(self.db, issued["refresh_token"], device_id="dev-1")
        self.db.commit()
        self.assertIn("refresh_token", rotated)

        with self.assertRaises(Exception):
            rotate_refresh_token(self.db, rotated["refresh_token"], device_id="dev-x")

        ok = revoke_refresh_token(self.db, rotated["refresh_token"], device_id="dev-1")
        self.assertTrue(ok)

    def test_auth_challenge_flow(self):
        row = create_challenge(self.db, challenge_type="EMAIL_VERIFY", target="user@example.com", code="123456")
        self.db.commit()
        verified = verify_challenge(self.db, challenge_id=row.id, code="123456")
        self.assertEqual(verified.status, "VERIFIED")

    def test_async_job_retry_dead_letter(self):
        job = AsyncJob(
            queue_name="default",
            job_type="unknown_job",
            status="QUEUED",
            payload_json=json.dumps({}),
            attempts=0,
            max_attempts=2,
            run_after=utc_now() - timedelta(seconds=1),
        )
        self.db.add(job)
        self.db.commit()

        process_due_jobs(db=self.db)
        self.db.refresh(job)
        self.assertEqual(job.status, "RETRY")

        job.run_after = utc_now() - timedelta(seconds=1)
        self.db.commit()
        process_due_jobs(db=self.db)
        self.db.refresh(job)
        self.assertEqual(job.status, "DEAD_LETTER")

    def test_refresh_tokens_store_device_id(self):
        user = User(email="b@example.com", password_hash=hash_password("secret123"), role="CUSTOMER")
        self.db.add(user)
        self.db.commit()
        self.db.refresh(user)
        issued = issue_auth_tokens(self.db, user, device_id="ios-1")
        self.db.commit()

        row = self.db.query(RefreshToken).filter(RefreshToken.user_id == user.id).first()
        self.assertEqual(row.device_id, "ios-1")
        self.assertTrue(bool(issued["access_token"]))


if __name__ == "__main__":
    unittest.main()
