## Raw Concept
**Task:**
Implement authentication API replicating WordPress login logic using Laravel Sanctum.

**Changes:**
- Added AuthController with login, logout, forgot-password, reset-password, register, and profile management.
- Implemented rate limiting (5/min) for login attempts.
- Added password reset and new user registration mailables.

**Files:**
- app/Http/Controllers/Api/V1/Admin/AuthController.php
- app/Http/Requests/Admin/LoginRequest.php
- app/Http/Resources/V1/Admin/UserResource.php
- app/Mail/PasswordResetMail.php
- app/Mail/NewUserRegistrationMail.php

**Flow:**
POST /login -> Validate -> Sanctum Token -> UserResource Response

**Timestamp:** 2026-02-18

## Narrative
### Structure
Authentication is handled via Sanctum tokens. FormRequests handle validation, and UserResource transforms the user model for API responses.

### Features
WP-aligned error messages, 24hr password reset token expiry, and capability-to-token-ability mapping.

### Rules
Rate limit: 5/min per IP for login. Only allow registration if users_can_register option is enabled.
