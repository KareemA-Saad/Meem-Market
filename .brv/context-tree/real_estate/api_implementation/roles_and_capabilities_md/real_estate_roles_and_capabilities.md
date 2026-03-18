## Relations
@real_estate/api_routes/real_estate_api_structure.md
@authentication/jwt/jwt_authentication.md

## Raw Concept
**Task:**
Define and implement RealEstate module roles and capabilities.

**Changes:**
- Implemented role-based access control for RealEstate module via tiered routes and middleware.
- Added agent-specific dashboard and performance tracking.
- Enabled user-specific favorite/saved property management.

**Files:**
- Modules/RealEstate/Routes/api.php
- Modules/RealEstate/Http/Controllers/Agent/AgentDashboardController.php
- Modules/RealEstate/Http/Controllers/Frontend/SavedPropertyController.php

**Flow:**
Request -> Middleware (Auth/Role/Feature) -> Role-Specific Controller (Frontend/Admin/Agent)

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Routes/api.php
Modules/RealEstate/Http/Controllers/Frontend/PropertyController.php
Modules/RealEstate/Http/Controllers/Frontend/SavedPropertyController.php
Modules/RealEstate/Http/Controllers/Admin/PropertyController.php
Modules/RealEstate/Http/Controllers/Agent/AgentDashboardController.php

### Dependencies
- Tenancy middleware (tenancy.token, tenant.context)
- Auth middleware (auth:api_tenant_user, auth.tenant_admin)
- Feature/Package middleware (feature:realestate, package.active)

### Features
- Public: Browsing properties/compounds/areas, search/map, lookups, and inquiry submission.
- Authenticated User: Management of saved/favorite properties.
- Admin: Full CRUD, bulk actions, statistics, and media management for all module entities.
- Agent: Dashboard with assigned property/inquiry statistics, inquiry management, and performance tracking.
