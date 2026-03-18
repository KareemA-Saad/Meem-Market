## Raw Concept
**Task:**
Implement dashboard statistics and full user CRUD management API.

**Changes:**
- Added DashboardController for stats and quick-draft functionality.
- Added UserController for user CRUD, including bulk actions and role filtering.
- Implemented content reassignment logic on user deletion.

**Files:**
- app/Http/Controllers/Api/V1/Admin/DashboardController.php
- app/Http/Controllers/Api/V1/Admin/UserController.php
- app/Http/Resources/V1/Admin/UserCollection.php

**Flow:**
GET /dashboard/stats -> Aggregate counts (posts, pages, comments) -> DashboardResource

**Timestamp:** 2026-02-18

## Narrative
### Structure
UserController handles user listing with filter tabs for roles. Bulk actions support deletion and role changes.

### Features
Dashboard stats include post/page/comment counts and recent activity lists. User management requires specific capabilities (list_users, create_users, etc.).

### Rules
Role change only allowed if current user can promote_users. Reassign content on user deletion via reassign_to parameter.
