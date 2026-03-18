## Relations
@compliance/security/rate_limiting_security_hardening.md

## Raw Concept
**Task:**
Implement comprehensive rate limiting across the API

**Changes:**
- Added 7 custom rate limiters to RouteServiceProvider
- Implemented custom JSON 429 responses for auth, sensitive, strict, and upload limiters
- Applied throttling to all API routes

**Files:**
- app/Providers/RouteServiceProvider.php
- routes/api.php

**Flow:**
Request -> throttle middleware -> RouteServiceProvider limiters -> (if exceeded) Custom JSON 429 response -> (if allowed) Controller

**Timestamp:** 2026-02-02

## Narrative
### Structure
- `app/Providers/RouteServiceProvider.php`: Limiter definitions
- `routes/api.php`: Middleware application

### Dependencies
- Integrated into `app/Providers/RouteServiceProvider.php`
- Applied via `throttle` middleware in `routes/api.php`

### Features
- 7 custom limiters: `api`(60/min), `public`(120/min), `auth`(30/min), `sensitive`(10/min), `strict`(5/min), `tenant`(100/min), `uploads`(20/min)
- Custom JSON 429 responses with `retry_after` header
- Tenant-aware limiting based on tenant ID and user combination
