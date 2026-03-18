## Relations
@appointments/module_overview/appointment_module_overview.md

## Raw Concept
**Task:**
Implement core booking logic and status management

**Changes:**
- Implemented transactional booking creation logic
- Added complex pricing calculation with tax and sub-appointments
- Implemented slot availability validation in service layer

**Files:**
- Modules/Appointment/Services/AppointmentBookingService.php
- Modules/Appointment/Services/SlotAvailabilityService.php

**Flow:**
createBooking -> checkSlot -> calculatePricing -> DB Transaction -> createLog -> return fresh booking

**Timestamp:** 2026-01-12

## Narrative
### Structure
- `Modules/Appointment/Services/AppointmentBookingService.php`
- `Modules/Appointment/Entities/AppointmentPaymentLog.php`
- `Modules/Appointment/Entities/AppointmentPaymentAdditionalLog.php`

### Dependencies
- `SlotAvailabilityService`: For conflict detection and past-date checks
- `DB`: Transactional integrity for booking creation
- `Auth`: User association for authenticated bookings

### Features
- Pricing: Calculates subtotal, sub-appointments, coupon discounts, and tax (inclusive/exclusive)
- Status Flow: `pending` -> `confirmed` (on payment/manual approval) -> `complete`
- Rescheduling: Validates new slot availability and prevents past-date moves
- Manual Payments: Support for file attachments and admin approval flow
