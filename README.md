# GrabBasket (Expo + FastAPI)

GrabBasket is a multi-role grocery delivery platform with:
- **Backend API**: FastAPI + PostgreSQL + JWT auth (CUSTOMER / SELLER / PARTNER / ADMIN).
- **Mobile app**: Expo + React Native (single codebase with role-aware navigation shells).

> This repository is already Expo/React Native based. Older Flutter references have been removed to keep onboarding and release docs accurate.

---

## 1) Backend: run locally (Docker)

### Prereqs
- Docker Desktop

### Start
```bash
cd backend
docker compose up --build
```

API endpoints:
- http://localhost:8000
- Swagger docs: http://localhost:8000/docs

### Seed demo data
```bash
cd backend
docker compose exec api python -m app.seed
```

### Hermetic backend verification
- Python 3.11+ is expected for local backend runs. CI uses Python 3.12.
- `GRABBASKET_DISABLE_DOTENV=1` disables loading `backend/.env` so local production-like settings cannot leak into migrations or tests.

```bash
cd backend
./scripts/verify_backend.sh
```

What it does:
- creates or reuses `backend/.venv`
- installs `backend/requirements.txt`
- sets `APP_ENV=development`
- sets `GRABBASKET_DISABLE_DOTENV=1`
- sets `DATABASE_URL=sqlite:///backend/.verify.db`
- sets `RUN_DB_CREATE_ON_STARTUP=false`
- sets `PYTHONDONTWRITEBYTECODE=1`
- runs `alembic upgrade head`
- runs `python -m unittest discover -s tests -p "test_*.py" -v`

### Root verification entrypoint
- `make backend-verify` runs `backend/scripts/verify_backend.sh`
- `make mobile-verify` runs `cd mobile && npm run lint`
- `make verify` runs both in sequence

---

## 2) Mobile app (Expo / React Native)

### Prereqs
- Node.js 20+
- npm 10+
- Expo CLI tooling (via `npx expo ...`)
- Android Studio / Xcode for native simulators (optional)

### Install + run
```bash
cd mobile
npm install
npm run dev
```

Other scripts:
```bash
npm run lint
npm run test
npm run test:watch
```

For release-candidate local verification, only `npm run lint` is required from `mobile/`. Node.js and npm must be installed locally.

### Local APK builds

Debug APKs default to `EXPO_PUBLIC_APP_ENV=development` even if `mobile/.env` contains production settings.

```bash
./build-apk.sh
EXPO_PUBLIC_APP_ENV=development ./build-apk.sh
BUILD_TYPE=release ./build-apk.sh
```

Release APKs always use production app config and must provide:
- `EXPO_PUBLIC_ALLOW_CLEARTEXT=false`
- `EXPO_PUBLIC_EAS_PROJECT_ID=...`

---

## 3) Roadmap foundations now in code

The backend now includes foundational primitives for the production hardening roadmap:

- **Realtime & dispatch**
  - SSE stream endpoint for order timeline events (`/platform/orders/{order_id}/timeline/stream`).
  - Route/ETA recalculation endpoint using traffic multiplier + prep variance (`/platform/dispatch/recalculate`).
  - Stale rider-location scan that escalates and queues reassignment jobs (`/platform/dispatch/stale-location-scan`).

- **Jobs and resilience**
  - Generic async job queue table (`async_jobs`) with retries / dead-letter fields.
  - Job enqueue endpoint (`/platform/jobs/enqueue`) for push, reconciliation, retry, timeout, and CRM nudge workflows.

- **Payments & risk control tables**
  - Ledger, reconciliation reports, webhook deliveries (replay/dead-letter fields), refunds, disputes, payouts, and money audit trail.
  - Auth/risk data structures for OTP/email/password-reset challenges, suspicious login/risk events, and admin block lists.

- **Compliance support tables**
  - Privacy policy/terms/data-safety artifact registry.
  - Account deletion / data export request tracking.

---

## 4) Deployment + production safety notes

- Keep secrets in environment variables / secret manager; rotate keys on a schedule and after incident triggers.
- Run Alembic migrations in CI/CD before app rollouts.
- Use worker processes for async job execution and enforce retry/backoff/dead-letter policies.
- Ensure Play Store data safety and legal documents are published and versioned from `compliance_artifacts`.

---

## 5) Next recommended implementation steps

1. Wire Redis + background workers to actively consume `async_jobs`.
2. Add gateway reconciliation import jobs and mismatch auto-remediation.
3. Implement console UIs for refund/fraud/order intervention workflows.
4. Expand automated test suites:
   - backend unit + integration + race-condition tests,
   - API contract tests,
   - end-to-end checkout tests,
   - spike/load tests.
5. Publish production runbooks and SLO/incident docs.
