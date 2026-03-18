## Relations
@tenancy/plan_structure/plan_feature_structure_and_limits.md
@tenancy/architecture/core_concepts_and_limits.md

## Raw Concept
**Task:**
Implement and document automated module enablement and migration logic.

**Changes:**
- Documented automated module enablement logic.
- Clarified that migrations are plan-dependent and handled by TenantService.

**Files:**
- app/Services/TenantService.php
- app/Jobs/CreateTenantDatabase.php

**Flow:**
Create Tenant -> Create DB -> runTenantMigrations() -> getEnabledModulesForTenant() -> Run Base + Plan-specific Module Migrations.

**Timestamp:** 2026-01-19T11:15:00Z

## Narrative
### Structure
- `app/Services/TenantService.php`: Central service for lifecycle and migration logic.
- `app/Jobs/CreateTenantDatabase.php`: Handles asynchronous database setup.

### Dependencies
- Integrates with `nwidart/laravel-modules` for feature-based migrations.
- Uses `PricePlan` and `PaymentLog` to determine active modules.

### Features
- **Automated Module Enablement**: Users cannot manually enable modules. The subscription plan automatically determines which modules are active.
- **Dynamic Migrations**: `runTenantMigrations()` checks plan permission features (e.g., `blog_permission_feature`) and only runs migrations for modules included in the plan.
- **Enabled Modules Discovery**: `getEnabledModulesForTenant()` reads plan features and returns an array of module names to install.
- **Installation Logic**: If a permission feature is an integer (e.g., 50), the module is enabled. If it is `NULL`, the module is not installed.
- **Scoped Access**: Generates Sanctum tokens with 'tenant:{id}' abilities.
- **Upgrades**: Supports running migrations for newly enabled modules during plan upgrades.
