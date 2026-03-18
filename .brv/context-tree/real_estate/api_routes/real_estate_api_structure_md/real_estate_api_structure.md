## Relations
@real_estate/api_implementation/phase_2_controllers_and_api_endpoints.md
@tenancy/architecture/architecture_overview.md

## Raw Concept
**Task:**
Define RealEstate module API structure and access control

**Changes:**
- Defined tiered API route structure for RealEstate module
- Integrated tenancy and feature-based middleware for route protection

**Files:**
- Modules/RealEstate/Routes/api.php
- Modules/RealEstate/Providers/RouteServiceProvider.php

**Flow:**
Request -> Middleware (Tenancy/Auth/Feature) -> Controller

**Timestamp:** 2026-02-08

## Narrative
### Structure
Modules/RealEstate/Routes/api.php

### Dependencies
- Tenancy middleware (tenancy.token, tenant.context)
- Auth middleware (auth.tenant_admin, auth:api_tenant_user)
- Subscription/Feature middleware (package.active, feature:realestate)

### Features
- Tiered route structure: Public, Authenticated User, Admin, and Agent
- Versioned under v1/tenant/{tenant}
- Public endpoints for properties, compounds, areas, developers, lookups, search, map, and inquiries
- Authenticated user endpoints for saved properties
- Admin CRUD and management for all entities (properties, compounds, areas, etc.)
- Agent dashboard and inquiry management
- Nawy-style URL support ({id}-{slug}) for properties and compounds
