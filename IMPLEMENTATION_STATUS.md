# GrabBasket roadmap implementation status

This checklist maps the requested roadmap items to concrete repository coverage.

## P0 — core reliability

- [x] Domain split + shared client patterns in `mobile/src/domains/*` and `mobile/src/lib/*`.
- [x] Existing backend auth/orders/payments scaffold.
- [x] Baseline CI/migrations/test scripts in repo.
- [x] SSE order timeline stream foundation (`/platform/orders/{id}/timeline/stream`).
- [x] Route + ETA recalculation foundation with traffic + prep variance (`/platform/dispatch/recalculate`).
- [x] Stale-location detection with reassignment escalation job enqueue (`/platform/dispatch/stale-location-scan`).

## P1 — payments, risk, and control plane foundations

- [x] Payment ledger table (`money_ledger_entries`).
- [x] Reconciliation report ingestion model (`payment_reconciliation_reports`).
- [x] Webhook replay/dead-letter tracking (`webhook_deliveries`) + replay-protection API (`/platform/webhooks/ingest`).
- [x] Refund/dispute/payout/audit data models (`refund_cases`, `dispute_cases`, `payout_records`, `money_audit_trail`).
- [x] Auth challenge and abuse-control models (`auth_challenges`, `auth_risk_events`, `user_blocklist`).
- [x] Async worker queue persistence (`async_jobs`) + enqueue API (`/platform/jobs/enqueue`).
- [x] Compliance + privacy request records (`compliance_artifacts`, `privacy_requests`).

## P2 — documentation and repo hygiene

- [x] Root README aligned with real mobile stack (Expo/React Native, not Flutter).
- [x] Production hardening roadmap section added to README.

## Remaining implementation work to fully match the requested list

The following are intentionally **not yet fully wired end-to-end** and remain planned:

- Redis-backed worker runtime, backoff executor, DLQ consumer, and scheduled jobs.
- OTP/email verification/password reset delivery providers and user-facing auth flows.
- CAPTCHA, device-binding enforcement, suspicious-login policy engine, and admin moderation console UX.
- Merchant/rider/refund/fraud operational consoles and dashboards in UI.
- Full automated test matrix (contract/integration/race/e2e/load).
- Merchant compliance automation and complete production deployment runbooks.
