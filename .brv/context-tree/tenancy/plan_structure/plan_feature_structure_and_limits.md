## Relations
@tenancy/tenant_service/tenant_service_implementation.md

## Raw Concept
**Task:**
Clarify how plan features trigger module installation.

**Changes:**
- Explicitly linked plan columns to module installation/enablement logic.

**Files:**
- app/Models/PricePlan.php
- app/Services/TenantService.php

**Flow:**
Plan Definition (Integer Limit) -> TenantService detected as Enabled -> Module Migrations Run.

**Timestamp:** 2026-01-19T11:15:00Z

## Narrative
### Structure
- `PricePlan.php`: Stores hard limits and module enablement triggers as integer/nullable columns.
- `PlanFeature.php`: Stores descriptive features for UI presentation.

### Dependencies
- PricePlan model (Central database)
- PlanFeature model (Central database)
- Tenant creation flow (Independent of theme)

### Features
- **Module Installation Trigger**: Plan permission columns (e.g., `product_create_permission`) act as triggers for module installation.
- **Integer vs NULL**: A non-null integer value enables the module and sets its limit. A `NULL` value prevents the module from being installed/migrated for that tenant.
- **Dual-layer Control**: Integer limits for items (e.g., 20 blogs) and UI display features for marketing.
- **Theme Independence**: Theme selection is visual and separate from functional module enablement.
