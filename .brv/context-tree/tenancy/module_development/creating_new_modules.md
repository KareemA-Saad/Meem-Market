## Relations
@tenancy/tenant_service/tenant_service_implementation.md
@tenancy/plan_structure/plan_feature_structure_and_limits.md

## Raw Concept
**Task:**
Document the process for creating and enabling new modules.

**Changes:**
- Defined the process for adding a new module using RealEstate as an example.

**Files:**
- app/Services/TenantService.php
- app/Models/PricePlan.php

**Flow:**
Create Module Folder -> Add Plan Column -> Create Migrations -> TenantService Auto-detects and Migrates.

**Timestamp:** 2026-01-19T11:15:00Z

## Narrative
### Structure
- `Modules/RealEstate/`: Folder containing Models, Controllers, Migrations, and Routes.
- `Modules/RealEstate/Database/Migrations`: Location for module-specific tables (e.g., properties, listings).
- `price_plans` table: Add `real_estate_permission_feature` column.

### Dependencies
- `TenantService`: Handles migration detection and execution.
- `PricePlan`: Needs new columns for new modules.

### Features
- **Independence**: New modules (e.g., RealEstate) can work alongside existing ones (Blog, Product, etc.).
- **Separation of Concerns**: Module controls functionality; Theme controls appearance.
- **Auto-Detection**: `TenantService` automatically runs migrations if the corresponding plan feature is set.
