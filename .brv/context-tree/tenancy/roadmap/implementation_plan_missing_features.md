## Relations
@tenancy/subscription_flow/subscription_initiation_flow.md
@tenancy/usage_limits_enforcement/usage_limits_and_resource_enforcement.md

## Raw Concept
**Task:**
Document the implementation plan for missing features.

**Changes:**
- Planned: Subscription status checker and middleware.
- Planned: Module limit checker and middleware.
- Planned: Public tenant discovery endpoints.
- Planned: Theme-plan logic decoupling.
- Planned: Tenant creation limits and rate limiting.

**Files:**
- docs/IMPLEMENTATION_PLAN_MISSING_FEATURES.md

**Flow:**
Phase 1: Critical (Subscription/Limits) -> Phase 2: Important (Expiration/Stats) -> Phase 3: Enhancement (Discovery/Suspension).

**Timestamp:** 2026-01-19T11:00:00Z

## Narrative
### Structure
- `app/Services/SubscriptionService.php`
- `app/Services/PlanLimitService.php`
- `app/Http/Middleware/CheckTenantSubscription.php`
- `app/Http/Middleware/CheckModuleLimit.php`
- `app/Http/Controllers/Api/V1/PublicTenantController.php`

### Dependencies
- Roadmap for completing the multi-tenant SaaS platform.
- Excludes payment integration (manual activation assumed).

### Features
- Subscription Enforcement: Service, middleware, and scheduled job for expiration/suspension.
- Usage Limits Enforcement: Service and middleware for module-specific item limits.
- Public Tenant Discovery: Public endpoints and resources for business discovery.
- Tenant Creation Protection: `max_tenants` limit per plan and rate limiting.
