## Relations
@tenancy/tenant_creation_flow/tenant_creation_flow.md

## Raw Concept
**Task:**
Document tenant discovery and identification of public access gap

**Changes:**
- Documented the restricted nature of the current tenant discovery endpoint.

**Files:**
- app/Http/Controllers/Api/V1/Landlord/TenantController.php

**Flow:**
Request (with api_user token) -> TenantController@index -> Filtered by User ID -> User's Tenants returned

**Timestamp:** 2026-01-19

## Narrative
### Structure
- `GET /api/v1/tenants`: Returns a collection of tenants owned by the authenticated `api_user`.
- `GET /api/v1/tenants/{id}`: Returns details of a specific tenant, restricted to the owner.
- `api_user` guard is mandatory for all current tenant discovery routes.

### Dependencies
- `auth:api_user` guard
- `TenantService::getUserTenants()`
- Landlord `TenantController`

### Features
- Private tenant listing: Owners can view and manage only their own tenants.
- Database status tracking: Owners can check if their tenant's database is ready.
- Context switching: Owners can generate tenant-scoped tokens.
