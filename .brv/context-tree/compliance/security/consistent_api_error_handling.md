## Relations
@compliance/security/consistent_api_error_handling.md

## Raw Concept
**Task:**
Implement consistent API error handling and exception management

**Changes:**
- Standardized API error response format
- Added specific handlers for common Laravel and Symfony exceptions
- Implemented database error code mapping to user-friendly messages
- Enhanced exception reporting with request context

**Files:**
- app/Exceptions/Handler.php

**Flow:**
Exception thrown -> Handler@render -> handleApiException -> match exception type -> jsonResponse -> Client

**Timestamp:** 2026-02-02

## Narrative
### Structure
- `app/Exceptions/Handler.php`: Centralized exception rendering and reporting logic

### Dependencies
- Extends `Illuminate\Foundation\Exceptions\Handler`
- Uses `Illuminate\Support\Facades\Log` for reporting
- Respects `APP_DEBUG` environment variable

### Features
- Consistent JSON API responses for all exception types
- Specialized handling for: Authentication (401), Authorization (403), Validation (422), ModelNotFound (404), TooManyRequests (429), QueryException (500)
- Database error mapping (Duplicate entry, Foreign key constraints, Connection errors)
- Debug information (stack traces, SQL) hidden in production
- Custom logging context (URL, method, user_id, IP) for reported exceptions
