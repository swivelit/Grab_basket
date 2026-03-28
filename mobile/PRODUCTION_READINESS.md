# GrabBasket Production Readiness (Swiggy-level Gap Plan)

## Are we at Swiggy standards today?
Short answer: **not yet**.

This repo now has stronger trust UI, telemetry hooks, flags, and governance surfaces.  
But Swiggy-level production readiness requires stronger execution in the areas below.

## Missing capabilities (critical)

1. **Reliability / SRE**
   - SLOs for checkout, payment success, rider assignment latency, and ETA accuracy.
   - On-call runbooks + alert routing with severity mapping.
   - Regional failover and queue backpressure handling.

2. **Discovery + ranking quality**
   - Real-time ranking service (contextual bandits / LTR).
   - Strict offline evaluation + online A/B experimentation.
   - Strong cold-start treatment for new stores/items.

3. **Growth systems**
   - CRM orchestration (push/WA/email) with cohort-level suppression rules.
   - Lifecycle automation (win-back, churn rescue, lapsed reorder flows).
   - Offer abuse prevention and budget pacing.

4. **Trust and marketplace integrity**
   - Merchant quality scoring (cancellation, prep variance, issue rate).
   - Refund/claims automation with fraud checks.
   - Proactive issue prediction (late prep, rider delay) and customer comms.

5. **Release governance**
   - Mandatory staged rollout percentages by platform + geography.
   - Crash-free user gates before moving from staging/canary → prod.
   - Auto rollback policy tied to crash and checkout KPIs.

## What this codebase now supports

- Centralized feature flags in `src/constants/feature-flags.js`.
- Centralized analytics taxonomy in `src/constants/analytics-taxonomy.js`.
- Screen-level trust/growth/governance visibility and event instrumentation.
- Environment-aware governance UI on account + delivery orders.
- Runtime release-governance evaluation on app boot (`src/lib/release-governance.js`) with optional hard gate enforcement via `EXPO_PUBLIC_ENFORCE_RELEASE_GATES`.

## Next production steps (recommended order)

1. Build `/health` + synthetic probes for API/payment/tracking.
2. Add experiment framework with assignment + exposure logging.
3. Implement canary rollout controller and auto rollback thresholds.
4. Add analytics QA contracts (event schema validation in CI).
5. Add error budgets and SLO dashboards for each user-critical flow.
