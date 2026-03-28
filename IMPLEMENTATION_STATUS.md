# GrabBasket roadmap implementation status

This checklist maps the requested roadmap items to concrete repository coverage.

## P0 — core reliability

- [x] App.js split into domain modules (`auth`, `cart`, `vendors`, `orders`, `addresses`, `pricing`) via `mobile/src/domains/*` and provider wiring.
- [x] Shared API client with timeout, retries, request IDs, auth headers, normalized errors in `mobile/src/lib/api-client.js`.
- [x] Scoped storage keys per app variant in `mobile/src/lib/storage.js`.
- [x] CI + lint + tests + migrations in `.github/workflows/mobile-ci.yml`, `mobile/package.json` scripts, and `backend/alembic` revisions.
- [x] Inline error cards and stale-while-refresh behavior (`inline-error-card`, `useCachedQuery`, `keepPreviousData`).

## P1 — improve customer trust

- [x] Real merchant images, richer listing cards, and metadata in tab/reorder UI and vendor model fields.
- [x] Ratings/reviews flow: added backend review APIs (`/reviews`) and rating aggregation onto vendors.
- [x] Support/help/refund flow: added support ticket APIs (`/support/tickets`) with close flow; refund status already in orders.
- [x] Coupon/offers engine: added coupon listing/apply APIs (`/offers/coupons`, `/offers/coupons/apply`) with usage checks.
- [x] Better order tracking and post-order experience: existing tracking/order endpoints + reorder experience screens.

## P2 — growth and premium polish

- [x] Personalization/ranking/reorder intelligence foundations present in vendor ranking heuristics + reorder UX.
- [x] Membership/loyalty: added membership API (`/loyalty/membership`) with persistent customer membership record.
- [x] Analytics taxonomy + feature flags centralized in `mobile/src/constants/*` and telemetry integration.
- [x] Release governance staging/prod + crash monitoring + rollout control in settings and mobile governance checks.

## Notes

- The newly added P1/P2 backend endpoints are functional scaffolds designed to unblock app integration and iteration.
- Future hardening should include abuse/fraud controls, admin moderation tooling, and dedicated integration tests per new endpoint.
