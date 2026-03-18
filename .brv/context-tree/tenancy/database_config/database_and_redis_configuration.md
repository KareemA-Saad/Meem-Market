## Relations
@tenancy/architecture.md

## Raw Concept
**Task:**
Configure database connections and Redis isolation for multi-tenant environment.

**Changes:**
- Added 'tenant' connection template with null database for dynamic switching
- Added 'central' connection for landlord data isolation
- Implemented Redis prefixing for environment/tenant isolation

**Files:**
- config/database.php
- config/tenancy.php

**Flow:**
Tenant Switch -> Resolve Database Name -> Update 'tenant' connection -> Reconnect DB

**Timestamp:** 2026-01-11

## Narrative
### Structure
Connections are defined in config/database.php. Tenant databases are named using a 'tenant_' prefix followed by the tenant's UUID.

### Dependencies
Standard Laravel database configuration with custom connections for tenancy. Redis isolation depends on APP_NAME and REDIS_PREFIX settings.

### Features
Provides a 'tenant' connection template for dynamic database creation and a 'central' connection for the landlord database. Implements Redis isolation using prefixing based on the application name to prevent cross-tenant or cross-environment data contamination.
