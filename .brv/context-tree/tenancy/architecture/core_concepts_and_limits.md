## Relations
@tenancy/tenant_service/tenant_service_implementation.md
@tenancy/plan_structure/plan_feature_structure_and_limits.md

## Raw Concept
**Task:**
Clarify module enablement logic and thematic independence.

**Changes:**
- Added module enablement logic and independence from themes.

**Files:**
- app/Services/TenantService.php
- app/Models/PricePlan.php

**Flow:**
Plan Definition -> Module Detection -> Migration Execution -> Functional Tenant.

**Timestamp:** 2026-01-19T11:20:00Z

## Narrative
### Structure
- Functional logic is encapsulated in Modules.
- Visual logic is encapsulated in Themes.
- Subscription plans bridge the two by enabling specific modules.

### Dependencies
- TenantService for migration orchestration.
- PricePlan for permission definitions.

### Features
- Module Enablement: Controlled strictly by the PricePlan. Users have no manual control over enabling/disabling modules.
- Migration Scoping: Only migrations for modules included in the plan are executed during tenant setup.
- Feature-Module Mapping: Example: `product_create_permission=50` enables the Product module; `product_create_permission=NULL` disables it.
- Independence: Modules (Blog, Product, RealEstate) are independent and can coexist.
- Visual Separation: Themes are selected independently of functional modules.
