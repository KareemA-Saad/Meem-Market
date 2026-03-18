## Raw Concept
**Task:**
Implement missing rate limiting for security hardening.

**Changes:**
- Identify unprotected endpoints in auth and public route groups.
- Apply `throttle` middleware to secure endpoints.

**Files:**
- routes/api.php

**Flow:**
Request -> Throttle Middleware -> Controller

**Timestamp:** 2026-02-02

## Narrative
### Structure
Middleware configuration in route files (`routes/api.php`).

### Dependencies
Standard Laravel RateLimiting middleware (throttle)

### Features
- Current: Only 2FA verification is throttled (5 attempts per minute).
- Status: CRITICAL BLOCKER. All auth and public endpoints are unprotected.
- Risk: High vulnerability to brute force, DDoS, and account enumeration.
- Implementation Plan: Apply throttle:60,1 to public routes and throttle:10,1 to sensitive auth endpoints.
- Effort: 2-4 hours.
