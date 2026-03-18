## Relations
@tenancy/roadmap/implementation_plan_missing_features.md

## Raw Concept
**Task:**
Document subscription enforcement and suspension plan.

**Changes:**
- Planned: SubscriptionService for managing tenant subscription states.
- Planned: CheckTenantSubscription middleware for access control.
- Planned: CheckExpiredSubscriptions job for automated suspension.

**Files:**
- app/Services/SubscriptionService.php
- app/Http/Middleware/CheckTenantSubscription.php
- app/Jobs/CheckExpiredSubscriptions.php

**Flow:**
Daily Job -> Check Expiry -> Suspend Tenant -> Middleware blocks access -> User notified.

**Timestamp:** 2026-01-19T11:05:00Z

## Narrative
### Structure
- `app/Services/SubscriptionService.php`: `isSubscriptionActive()`, `suspendTenant()`.
- `app/Http/Middleware/CheckTenantSubscription.php`: Applied to all tenant routes.
- `app/Jobs/CheckExpiredSubscriptions.php`: Daily cleanup job.

### Dependencies
- `PaymentLog`: Source of truth for subscription dates.
- `SubscriptionService`: Core logic for status determination.

### Features
- Automated suspension for expired subscriptions (with grace period).
- Middleware to block access to suspended/expired tenants (402/403 errors).
- Daily scheduled job to update tenant statuses.
