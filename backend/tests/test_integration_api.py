from __future__ import annotations

import unittest
from datetime import datetime

try:
    from fastapi.testclient import TestClient
except Exception:  # pragma: no cover - optional local dependency
    TestClient = None
from sqlalchemy import create_engine
from sqlalchemy.orm import sessionmaker

from app.db import Base, get_db
from app.main import app
from app.models import Order, OrderEvent, PartnerLocation, User, Vendor


@unittest.skipIf(TestClient is None, "fastapi testclient dependencies (httpx) are not installed")
class IntegrationApiTests(unittest.TestCase):
    @classmethod
    def setUpClass(cls):
        cls.engine = create_engine("sqlite://", connect_args={"check_same_thread": False})
        cls.Session = sessionmaker(autocommit=False, autoflush=False, bind=cls.engine)
        Base.metadata.create_all(bind=cls.engine)

        def override_get_db():
            db = cls.Session()
            try:
                yield db
            finally:
                db.close()

        app.dependency_overrides[get_db] = override_get_db
        cls.client = TestClient(app)

    @classmethod
    def tearDownClass(cls):
        app.dependency_overrides.clear()

    def _auth_headers(self, token: str) -> dict:
        return {"Authorization": f"Bearer {token}"}

    def _register(self, email: str, role: str = "CUSTOMER", device_id: str = "device-1"):
        return self.client.post(
            "/auth/register",
            json={"email": email, "password": "secret123", "role": role, "device_id": device_id},
        )

    def test_auth_register_login_refresh_logout_device_bound_flow(self):
        register = self._register("int_customer@example.com", device_id="ios-1")
        self.assertEqual(register.status_code, 200)
        session = register.json()

        refreshed = self.client.post(
            "/auth/refresh",
            json={"refresh_token": session["refresh_token"], "device_id": "ios-1"},
        )
        self.assertEqual(refreshed.status_code, 200)

        wrong_device = self.client.post(
            "/auth/refresh",
            json={"refresh_token": refreshed.json()["refresh_token"], "device_id": "other"},
        )
        self.assertEqual(wrong_device.status_code, 401)

        logout = self.client.post(
            "/auth/logout",
            json={"refresh_token": refreshed.json()["refresh_token"], "device_id": "ios-1"},
        )
        self.assertEqual(logout.status_code, 200)
        self.assertTrue(logout.json()["ok"])

    def test_auth_challenge_start_verify(self):
        start = self.client.post(
            "/auth/challenge/start",
            json={"challenge_type": "EMAIL_VERIFY", "target": "int_customer@example.com"},
        )
        self.assertEqual(start.status_code, 200)
        payload = start.json()
        verify = self.client.post(
            "/auth/challenge/verify",
            json={"challenge_id": payload["challenge_id"], "code": payload["dev_code"]},
        )
        self.assertEqual(verify.status_code, 200)
        self.assertEqual(verify.json()["status"], "VERIFIED")

    def test_route_intelligence_stale_scan_sse_smoke(self):
        admin = self._register("admin_route@example.com", role="ADMIN", device_id="admin-device").json()

        db = self.Session()
        seller = User(email="seller_route@example.com", password_hash="x", role="SELLER")
        customer = User(email="cust_route@example.com", password_hash="x", role="CUSTOMER")
        partner = User(email="partner_route@example.com", password_hash="x", role="PARTNER", is_partner_available=True)
        db.add_all([seller, customer, partner])
        db.flush()

        vendor = Vendor(seller_id=seller.id, name="Test Vendor", description="", address="Addr", is_open=True)
        db.add(vendor)
        db.flush()

        order = Order(
            vendor_id=vendor.id,
            customer_id=customer.id,
            partner_id=partner.id,
            status="ASSIGNED_TO_PARTNER",
            delivery_lat=12.97,
            delivery_lng=77.59,
        )
        db.add(order)
        db.flush()
        db.add(PartnerLocation(partner_id=partner.id, lat=12.95, lng=77.58, created_at=datetime.utcnow()))
        db.add(OrderEvent(order_id=order.id, status="CREATED", note="created"))
        db.commit()
        db.close()

        route = self.client.post(
            "/platform/dispatch/route-intelligence",
            headers=self._auth_headers(admin["access_token"]),
            json={"order_id": order.id, "rider_lat": 12.95, "rider_lng": 77.58},
        )
        self.assertEqual(route.status_code, 200)

        stale_scan = self.client.post(
            "/platform/dispatch/stale-location-scan",
            headers=self._auth_headers(admin["access_token"]),
            params={"stale_after_seconds": 60},
        )
        self.assertEqual(stale_scan.status_code, 200)

        with self.client.stream(
            "GET",
            f"/platform/orders/{order.id}/timeline/stream?since_id=0&poll_seconds=0.25",
            headers=self._auth_headers(admin["access_token"]),
        ) as stream:
            chunk = next(stream.iter_text())
            self.assertTrue("retry:" in chunk or "event:" in chunk)

    def test_webhook_ingest_idempotency_replay(self):
        admin = self._register("admin_webhook@example.com", role="ADMIN", device_id="admin-device2").json()
        payload = {
            "provider": "razorpay",
            "event_id": "evt_123",
            "event_type": "payment.captured",
            "payload": {"amount": 100},
        }

        first = self.client.post("/platform/webhooks/ingest", headers=self._auth_headers(admin["access_token"]), json=payload)
        self.assertEqual(first.status_code, 200)
        self.assertFalse(first.json()["duplicate"])

        second = self.client.post("/platform/webhooks/ingest", headers=self._auth_headers(admin["access_token"]), json=payload)
        self.assertEqual(second.status_code, 200)
        self.assertTrue(second.json()["duplicate"])


if __name__ == "__main__":
    unittest.main()
