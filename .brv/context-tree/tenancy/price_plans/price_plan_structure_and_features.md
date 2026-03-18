## Relations
@tenancy/plan_structure/plan_feature_structure_and_limits.md
@tenancy/tenant_creation_flow/tenant_creation_validation.md

## Raw Concept
**Task:**
Clarify price plan logic, feature enforcement, and creation limits.

**Changes:**
- Clarified that plans control features per tenant, not tenant counts.
- Added `max_tenants` field for limiting tenant creation per user subscription.

**Files:**
- app/Models/PricePlan.php
- app/Models/PaymentLog.php
- app/Http/Controllers/Api/V1/Landlord/UserDashboardController.php

**Flow:**
User chooses plan -> PaymentLog record created -> Tenant features enabled based on plan columns -> Creation blocked if `max_tenants` reached.

**Timestamp:** 2026-01-19T11:10:00Z

## Narrative
### Structure
- `PricePlan` model: Central definition of subscription tiers and creation limits.
- `PaymentLog`: Links tenants to their active plans.

### Dependencies
- PaymentLog for tracking subscription history.
- PlanLimitService for enforcing module limits.
- **Abuse Prevention**: `max_tenants` field in `price_plans` table.

### Features
- Plans define what modules are enabled and their respective item limits.
- **Logic**: Plans control feature counts per tenant, NOT the number of tenants a user can create (unless `max_tenants` is implemented).
- **Tenant Creation Limit**: The `max_tenants` column (default 1) defines how many tenants a user can create under a specific subscription.
- Example: A 'Basic Plan' with `blog_permission_feature=20` and `max_tenants=1` allows a user to create one tenant with up to 20 blog articles.
