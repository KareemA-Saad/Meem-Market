## Relations
@tenancy/tenant_service/tenant_service_implementation.md
@tenancy/architecture/core_concepts_and_limits.md

## Raw Concept
**Task:**
Document the process for creating new modules.

**Changes:**
- Added guide for creating new modules using RealEstate as an example.

**Files:**
- app/Services/TenantService.php
- app/Models/PricePlan.php

**Flow:**
Create Module -> Add Plan Column -> Add Migrations -> TenantService Auto-Migration.

**Timestamp:** 2026-01-19T11:20:00Z

## Narrative
### Structure
### Example: Adding RealEstate Module
1. **Folder Structure**: Create `Modules/RealEstate` with Models, Controllers, Migrations, and Routes.
2. **Plan Column**: Add `real_estate_permission_feature` (integer) to the `price_plans` table.
3. **Migrations**: Place tenant-specific tables (properties, listings) in `Modules/RealEstate/Database/Migrations`.
4. **Logic**: `TenantService` will detect the plan feature and run these migrations.
5. **Theme**: The user can choose a matching theme (Theme-realestate) or any other existing theme.

### Dependencies
- nwidart/laravel-modules
- TenantService migration logic

### Features
- Module Independence: New modules like 'RealEstate' can be added without affecting existing ones.
- Functional vs Visual: Modules provide the backend/frontend logic, while Themes provide the styling.
- Auto-Detection: TenantService automatically picks up new module migrations if the corresponding plan column is set.
