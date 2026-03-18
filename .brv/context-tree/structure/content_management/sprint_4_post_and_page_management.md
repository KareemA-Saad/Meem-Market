## Raw Concept
**Task:**
Implement Post and Page CRUD API with revisions, quick edit, and trash/restore functionality.

**Changes:**
- Created PostController to handle both "post" and "page" types.
- Implemented revision system storing snapshots as "revision" post types.
- Added trash/restore logic using post_meta to store original status.

**Files:**
- app/Http/Controllers/Api/V1/Admin/PostController.php
- app/Http/Resources/V1/Admin/PostResource.php
- app/Http/Requests/Admin/StorePostRequest.php

**Flow:**
PUT /posts/{id} -> Create Revision -> Update Post -> Return PostResource

**Timestamp:** 2026-02-18

## Narrative
### Structure
A single PostController manages both posts and pages via a type parameter. Revisions are linked to parent posts.

### Features
Auto-slug generation, featured image support via postmeta, and bulk actions for status changes.

### Rules
On trash: store old status in _wp_trash_meta_status. Permanent delete cascades to meta, relationships, and comments.
