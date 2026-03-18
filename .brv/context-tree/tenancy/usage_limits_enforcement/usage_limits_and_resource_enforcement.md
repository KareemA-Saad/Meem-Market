## Relations
@tenancy/plan_structure/plan_feature_structure_and_limits.md
@tenancy/roadmap/implementation_plan_missing_features.md

## Raw Concept
**Task:**
Document usage limits enforcement plan.

**Changes:**
- Planned: PlanLimitService for checking module usage against plan limits.
- Planned: CheckModuleLimit middleware for automatic enforcement.
- Planned: Usage stats endpoint for transparency.

**Files:**
- app/Services/PlanLimitService.php
- app/Http/Middleware/CheckModuleLimit.php
- config/modules.php

**Flow:**
Request -> Middleware -> PlanLimitService -> Check Current Usage vs Plan Limit -> Allow/Block.

**Timestamp:** 2026-01-19T11:05:00Z

## Narrative
### Structure
- `app/Services/PlanLimitService.php`: Logic for `canCreateBlog()`, `canCreateProduct()`, etc.
- `app/Http/Middleware/CheckModuleLimit.php`: Intercepts POST requests to validate limits.
- `config/modules.php`: Maps 'blog' to 'blog_permission_feature', etc.

### Dependencies
- `config/modules.php`: Stores mapping between modules and plan columns.
- `PlanLimitService`: Central service for limit checking.

### Features
- Middleware-based enforcement for creation endpoints.
- Storage limit enforcement for media uploads.
- Usage stats endpoint for tenant admins to monitor their limits.
