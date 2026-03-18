## Relations
@tenancy/tenant_creation_flow/tenant_creation_flow.md
@tenancy/price_plans/price_plan_structure_and_features.md

## Raw Concept
**Task:**
Document tenant creation validation

**Changes:**
- Documented tenant creation validation rules

**Files:**
- app/Http/Requests/Tenant/CreateTenantRequest.php

**Flow:**
Request received -> Validated against rules -> `validatedData()` returns typed array for service layer

**Timestamp:** 2026-01-19

## Narrative
### Structure
- `subdomain`: required, string, 3-63 chars, regex `^[a-z0-9]([a-z0-9-]*[a-z0-9])?$`
- `plan_id`: required, integer, exists in `price_plans`
- `theme` / `theme_code`: nullable string

### Dependencies
- App\Http\Requests\Tenant\CreateTenantRequest
- PricePlan model
- Tenants table (for uniqueness check)

### Features
- Subdomain validation (regex, length, uniqueness)
- Plan existence check
- Optional theme selection
- Typed data extraction via `validatedData()`
