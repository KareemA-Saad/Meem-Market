## Raw Concept
**Task:**
Initialize Laravel 12 API-only project with WordPress-aligned database schema and core services.

**Changes:**
- Created migrations for users, user_meta, posts, post_meta, terms, term_taxonomy, term_relationships, term_meta, comments, comment_meta, options, and links.
- Implemented OptionService for request-cached option management.
- Implemented RoleService for capability-based access control.
- Added CheckCapability middleware for route-level authorization.
- Created ApiController base with standardized JSON responses.

**Files:**
- app/Models/User.php
- app/Models/Post.php
- app/Models/Option.php
- app/Services/OptionService.php
- app/Services/RoleService.php
- app/Http/Middleware/CheckCapability.php
- app/Http/Controllers/Api/V1/Admin/ApiController.php

**Flow:**
Request -> Middleware (CheckCapability) -> Controller -> Service (Option/Role) -> Model -> Response

**Timestamp:** 2026-02-18

## Narrative
### Structure
The project follows an API-only architecture with a v1/admin prefix for CMS operations. Database tables are prefixed/aligned with WordPress schema to support migration.

### Dependencies
Laravel Sanctum for token auth, MySQL utf8mb4 for database.

### Features
Standardized success/error JSON formats, request-cached options, and capability-mapped roles (Administrator, Editor, Author, Contributor, Subscriber).

### Rules
1. Do NOT modify existing public controllers/models.
2. All new admin routes under /api/v1/admin/.
3. Standardized JSON response format: {success:bool, data:mixed, meta?:object}.
