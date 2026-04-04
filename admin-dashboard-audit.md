# Admin Dashboard Audit - Meem-Market

## 1. High-level status

- ✅ Admin API layer is already implemented with a WP-like role/capability system.
- ✅ Authentication via Sanctum is configured for admin routes in `routes/api.php`.
- ✅ Capability guard middleware implemented in `app/Http/Middleware/CheckCapability.php` (`can_do` predicate).
- ✅ Dashboard endpoints exist in `app/Http/Controllers/Api/V1/Admin/DashboardController.php`:
  - `GET /api/v1/admin/dashboard/stats` (requires `read` capability)
  - `POST /api/v1/admin/dashboard/quick-draft` (requires `edit_posts` capability)

## 2. Admin route surface (`routes/api.php`)

- Public API prefix: `/api/v1/*`
- Admin API prefix: `/api/v1/admin/*`

### Auth (no auth required)
- `POST /api/v1/admin/auth/login`
- `POST /api/v1/admin/auth/register`
- `POST /api/v1/admin/auth/forgot-password`
- `POST /api/v1/admin/auth/reset-password`

### Authenticated admin routes (`auth:sanctum`)
- `POST /api/v1/admin/auth/logout`
- `GET/PUT /api/v1/admin/auth/me`

### Admin features currently implemented (with role/capability checks)
- Dashboard
- User management
- Post/page management
- Category/tag/custom taxonomy management
- Media library
- Comment moderation
- Settings management
- Content types & custom taxonomies
- Custom fields (field groups)
- Navigation menus
- Tools (export, site-health)

## 3. Role/capability system

- `app/Services/RoleService.php` loads roles from `options` table key `user_roles` and user role from `user_meta` key `wp_capabilities`.
- This service is used by `CheckCapability` middleware to authorize admin actions.

## 4. Related controller stack

- `app/Http/Controllers/Api/V1/Admin/ApiController.php` (shared response helpers)
- `AuthController`, `DashboardController`, `UserController`, `PostController`, `TaxonomyController`, `CommentController`, `MediaController`, `SettingsController`, `ContentTypeController`, `CustomFieldController`, `MenuController`, `ExportController`, `SiteHealthController`

## 5. Core models and tables

- Users: `users` + `user_meta` + `role` definitions via options mapping.
- Content: `posts`, `comments`, `terms`, `term_taxonomy`, `term_relationships`, `settings`, `options`.

## 6. Observed gaps / next steps

- [ ] No explicit admin frontend/UI in repository (currently API-only).
- [ ] No audit page directly; this file is the current audit snapshot.
- [ ] If you need UI components: wire these endpoints into a React/Vue admin panel.
- [ ] Add tests for capability coverage (route + controller).

## 7. Verification command

- `php artisan migrate:fresh --seed` now passes (exit code 0 as of the latest run).

---

_Last update: 2026-03-30_
