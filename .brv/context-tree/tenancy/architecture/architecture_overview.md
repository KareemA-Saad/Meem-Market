## Relations
@tenancy/database_config.md
@tenancy/tenant_service.md

## Raw Concept
**Task:**
Establish API-first multi-tenancy architecture with UUID identification and service isolation.

**Changes:**
- Configured API-first identification resolvers (token, header, route, query)
- Enabled UUID generation for tenant IDs
- Activated core bootstrappers for service isolation
- Disabled default tenancy web routes

**Files:**
- config/tenancy.php
- app/Models/Tenant.php

**Flow:**
Request -> Identification (Token/Header) -> Tenancy Initialization -> Bootstrappers -> Tenant Context Execution

**Timestamp:** 2026-01-11

## Narrative
### Structure
Central configuration is located in config/tenancy.php. The system distinguishes between central (landlord) and tenant contexts using specialized bootstrappers.

### Dependencies
Uses stancl/tenancy package for Laravel. Bootstrappers are active for Database, Cache, Filesystem, and Queue to ensure multi-tenant isolation across core services.

### Features
Prioritizes API-first identification using Sanctum token abilities (tenant:{tenant_id}) and X-Tenant-ID headers. Web-specific routes are disabled ('routes' => false) to focus on headless API architecture. Uses Stancl\Tenancy\UUIDGenerator for secure, non-sequential tenant identifiers.
