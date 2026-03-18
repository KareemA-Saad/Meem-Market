## Relations
@tenancy/tenant_discovery/tenant_discovery_and_access_control.md

## Raw Concept
**Task:**
Document public discovery plan

**Changes:**
- Outlined public discovery mechanism for end-customers.

**Files:**
- app/Http/Controllers/Api/V1/PublicTenantController.php
- app/Http/Resources/PublicTenantResource.php

**Flow:**
Public Request -> PublicTenantController -> Filter Active/Public -> Return Safe Data

**Timestamp:** 2026-01-19

## Narrative
### Structure
- `GET /api/v1/tenants/public`: Publicly accessible endpoint.
- `PublicTenantResource`: Filters out sensitive owner/payment data.

### Dependencies
- `PublicTenantController` (new)
- `PublicTenantResource` (new)
- `tenants` table (public_listing flag)

### Features
- Public browsing of active tenants for end-customers.
- Search by name/description and filtering by theme/status.
- Restricted data exposure (safe public data only).
