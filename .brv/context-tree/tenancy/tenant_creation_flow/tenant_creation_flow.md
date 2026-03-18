## Relations
@tenancy/architecture/architecture_overview.md
@tenancy/subscription_flow/subscription_initiation_flow.md
@tenancy/tenant_service/tenant_service_implementation.md

## Raw Concept
**Task:**
Document tenant creation flow

**Changes:**
- Documented the self-service tenant creation flow

**Files:**
- app/Http/Controllers/Tenant/TenantController.php
- app/Services/TenantService.php
- app/Jobs/CreateTenantDatabase.php

**Flow:**
1. User registers (POST /api/v1/auth/register) -> 2. User selects plan (GET /api/v1/plans) -> 3. User creates tenant (POST /api/v1/my-tenants) -> 4. Async background DB setup

**Timestamp:** 2026-01-19

## Narrative
### Structure
- POST /api/v1/auth/register: User registration
- GET /api/v1/plans: Browse price plans
- POST /api/v1/my-tenants: Create tenant with subdomain, plan_id, theme, theme_code

### Dependencies
- Authentication (api_user guard)
- Price Plans
- Tenant Service
- Async background jobs for database setup

### Features
- Self-service tenant creation (no admin approval required)
- Async database setup
- Subdomain-based tenancy
