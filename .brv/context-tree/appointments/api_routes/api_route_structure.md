## Relations
@appointments/module_overview/appointment_module_overview.md

## Raw Concept
**Task:**
Define Appointment Module API routes

**Changes:**
- Defined multi-level API route structure for public, user, and admin access
- Integrated tenancy and feature-based middleware into routing

**Files:**
- Modules/Appointment/Routes/api.php

**Flow:**
Public -> /appointments/* | Auth User -> /appointments/my-bookings/* | Admin -> /admin/appointments/*

**Timestamp:** 2026-01-12

## Narrative
### Structure
- File: `Modules/Appointment/Routes/api.php`
- Prefix: `v1/tenant/{tenant}`
- Sub-prefixes: `appointments` (public/user), `admin/appointments` (admin)

### Dependencies
- `tenancy.token`: Resolves tenant context from headers
- `tenant.context`: Ensures valid database connection
- `auth:sanctum`: Required for user/admin routes
- `feature:appointment`: Plan-based access control

### Features
- Public: Browse categories, featured appointments, check availability
- User: My bookings, cancel/reschedule my bookings
- Admin: Full CRUD on services, categories, schedules, and bookings
- Webhooks: Payment callback support (`booking/payment-callback`)
