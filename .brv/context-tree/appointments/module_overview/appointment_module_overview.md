## Relations
@tenancy/architecture/architecture_overview.md

## Raw Concept
**Task:**
Implement Appointment Module API for multi-tenant SaaS

**Changes:**
- Introduced complete service booking system for multi-tenant environment
- Implemented slot-based scheduling with timezone support
- Added package-level feature gating for appointments

**Files:**
- Modules/Appointment/Entities/Appointment.php
- Modules/Appointment/Services/AppointmentService.php
- Modules/Appointment/Http/Middleware/TenantContextMiddleware.php

**Flow:**
Request -> Middleware (Tenancy/Auth) -> Controller -> Service -> Model/DB

**Timestamp:** 2026-01-12

## Narrative
### Structure
- Location: `Modules/Appointment/`
- Entities: 13 models including `Appointment`, `SubAppointment`, `AppointmentDay`, `AppointmentPaymentLog`
- Services: `AppointmentService`, `ScheduleService`, `SlotAvailabilityService`, `AppointmentBookingService`
- Controllers: Organized by API V1 (Admin and Frontend)

### Dependencies
- Tenancy: `tenancy.token`, `tenant.context` middleware
- Package: `package.active`, `feature:appointment` middleware
- Timezone: `get_static_option('timezone')`
- Patterns: Service Layer + API Resources + Request Validators + OpenAPI attributes

### Features
- Multi-tenant SaaS support
- Service booking and scheduling
- Package limits enforcement
- Date-based slot availability with conflict detection
- Sub-appointment/Add-on support
- Tax calculation (Inclusive/Exclusive)
- Manual and Online payment support (stateless)
