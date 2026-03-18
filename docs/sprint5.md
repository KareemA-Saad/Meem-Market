USER:
/init

AGENT:
I'll analyze this codebase to create a comprehensive AGENTS.md file. Let me start by exploring the repository structure and key configuration files.

_Command cancelled_

USER:
Continue sprint 5 based on the current changes and alignment_guide.md and implementation_plan.md file in this repo. It was stopped in the middle so check what's done from the added files and then continue

AGENT:
I'll analyze the current state of the repository to understand what's been completed in Sprint 5 and continue from where it was stopped.


**Files Read:**

**D:\Meem-Market\alignment_guide.md**
```
# Meem-Market → WP Admin Alignment Guide

## Audit Summary

| Aspect | Existing Project | WP Admin Plan |
|---|---|---|
| **Framework** | Laravel 12, PHP 8.2 ✅ | Same ✅ |
| **Auth package** | Sanctum installed ✅ | Sanctum ✅ |
| **API docs** | L5-Swagger (OpenAPI) | Not specified — adopt existing |
| **Database** | SQLite ([database.sqlite](file:///F:/My%20journey/First%20Soft/Meem-Market/database/database.sqlite)) | MySQL planned |
| **API prefix** | `/api/v1/` ✅ | Same ✅ |
| **User model** | Default Laravel (name, email, password) | WP-style (login, nicename, display_name, etc.) |
| **Settings** | [settings](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#840-899) table (group/key/value) | [options](file:///f:/My%20journey/well-known/wp-admin/includes/schema.php#350-713) table (name/value/autoload) |
| **Existing scope** | Public website API (storefront) | Admin panel API (CMS backend) |

---

## Key Finding

> [!IMPORTANT]
> Your existing project is a **public-facing website API** — it serves content to the frontend (sliders, branches, offers, careers, about, etc.). The WP admin replication is a **separate admin/CMS layer** that will manage that content from the backend. These are **complementary, not conflicting**.

The two layers should coexist:
- **Public routes** (`/api/v1/home`, `/api/v1/branches`, etc.) — already built, untouched
- **Admin routes** (`/api/v1/admin/...`) — new, gated behind Sanctum auth + capabilities

---

## Conflicts to Resolve

### 1. [User](file:///F:/My%20journey/First%20Soft/Meem-Market/app/Models/User.php#10-49) Model — Must Be Extended (Not Replaced)
Your current [User](file:///F:/My%20journey/First%20Soft/Meem-Market/app/Models/User.php#10-49) has `name`, `email`, `password`. The WP plan needs [login](file:///f:/My%20journey/well-known/wp-login.php#315-455), `nicename`, `display_name`, [url](file:///f:/My%20journey/well-known/wp-content/plugins/advanced-custom-fields/acf.php#856-886), [status](file:///f:/My%20journey/well-known/wp-content/plugins/advanced-custom-fields/acf.php#559-581), `activation_key`, `registered_at`.

**Decision:** Extend the existing user table with new columns via a migration. Keep `name` as an alias for `display_name`.

### 2. [Setting](file:///F:/My%20journey/First%20Soft/Meem-Market/app/Models/Setting.php#7-30) vs `Option` — Keep Both or Merge
Your [settings](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#840-899) table uses `group`/[key](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#981-1020)/`value`. The WP plan uses [options](file:///f:/My%20journey/well-known/wp-admin/includes/schema.php#350-713) with `name`/`value`/[autoload](file:///f:/My%20journey/well-known/wp-content/plugins/admin-site-enhancements/admin-site-enhancements.php#27-68).

**Decision:** Keep [settings](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#840-899) for website-level config and create a separate [options](file:///f:/My%20journey/well-known/wp-admin/includes/schema.php#350-713) table for CMS/admin config. They serve different purposes — website branding vs admin system options.

### 3. Database Engine — SQLite vs MySQL
Your project uses SQLite. The WP plan targets MySQL.

**Decision:** This is an environment config choice. Both work for development — just update [.env](file:///F:/My%20journey/First%20Soft/Meem-Market/.env) when deploying. No code changes needed.

---

## Step-by-Step Alignment Plan

### Step 1: Inform Your Agent About the Existing Project Structure

Paste this prompt to the agent that built the Meem-Market project:

```text
CONTEXT: I have an existing Laravel 12 API project (Meem-Market) that currently serves as a
public-facing website API with these existing components:

EXISTING MODELS: AboutSection, Branch, Career, CompetitiveFeature, ContactMessage, Country,
Offer, OfferCategory, Partner, Section, Setting, Slider, User

EXISTING CONTROLLERS (under App\Http\Controllers\Api\V1):
HomeController, CountryController, BranchController, OfferController, AboutController,
CareerController, ContactController, SettingController

EXISTING ROUTES (all public, no auth):
GET /api/v1/home, /countries, /branches, /branches/{slug}, /offers, /about, /careers,
/careers/{slug}, /contact, /settings/{group}
POST /api/v1/contact

EXISTING DATABASE: users (default Laravel), countries, branches, offer_categories, offers,
sliders, sections, partners, about_sections, competitive_features, careers, settings,
contact_messages + cache/jobs/sessions tables

EXISTING PATTERNS:
- API Resources under App\Http\Resources\V1
- Swagger docs via L5-Swagger (OpenAPI attributes)
- Setting model with group/key/value structure
- No auth routes, no middleware, no admin functionality

I am now adding a CMS admin layer to this project to manage all this content. The admin
layer replicates WordPress admin panel functionality. All existing public endpoints must
remain untouched. The new admin layer adds:
- Authentication (Sanctum tokens)
- User management with roles & capabilities
- Post/Page/Taxonomy CRUD
- Media library
- Comment management
- Settings management
- Custom post types & custom fields
- Navigation menus

IMPORTANT RULES:
1. Do NOT modify any existing controllers, models, routes, or migrations
2. All new admin routes go under /api/v1/admin/ prefix
3. All new admin controllers go under App\Http\Controllers\Api\V1\Admin\
4. Keep the existing Setting model — create a separate Option model for CMS options
5. Extend the User model (add columns via new migration, don't replace the migration)
6. Follow the same patterns: API Resources in V1, OpenAPI/Swagger attributes, FormRequests
```

---

### Step 2: Run Sprint 1 with These Modifications

Add this **preamble** to the Sprint 1 prompt before pasting it:

```text
PREAMBLE — EXISTING PROJECT:
This is NOT a new project. I have an existing Laravel 12 API project. Follow these rules:

1. DO NOT create a new Laravel project or modify composer.json (Sanctum is already installed)
2. DO NOT modify the existing users migration — create a NEW migration to ADD columns:
   - login (string 60, unique, nullable initially for backfill)
   - nicename (string 50, nullable)
   - url (string 100, default '')
   - registered_at (datetime, nullable)
   - activation_key (string 255, default '')
   - status (tinyInteger, default 0)
   - display_name (string 250, nullable)
   Name it: 2026_02_18_000001_add_wp_columns_to_users_table.php

3. DO NOT create a settings table — it already exists. Create an `options` table instead
   (as planned). The OptionService maps to the `options` table.

4. Create all OTHER migrations as planned (posts, post_meta, terms, term_taxonomy,
   term_relationships, term_meta, comments, comment_meta, options, links, user_meta)

5. Extend the existing User model — add the new fillable fields, relationships to UserMeta
   and Post. Do NOT replace the file, add to it.

6. All new admin controllers go in: App\Http\Controllers\Api\V1\Admin\
7. All new admin routes go in a new route group: Route::prefix('v1/admin')
8. All new admin API Resources go in: App\Http\Resources\V1\Admin\
9. New services go in: App\Services\
10. New middleware goes in: App\Http\Middleware\
```

---

### Step 3: Update Routes Structure

Your [routes/api.php](file:///F:/My%20journey/First%20Soft/Meem-Market/routes/api.php) should evolve to this pattern:

```php
// --- PUBLIC ROUTES (existing, untouched) ---
Route::prefix('v1')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);
    // ... all existing routes
});

// --- ADMIN ROUTES (new, from sprint plan) ---
Route::prefix('v1/admin')->group(function () {
    // Auth (unauthenticated)
    Route::post('/auth/login', [AuthController::class, 'login']);
    Route::post('/auth/forgot-password', [ForgotPasswordController::class, 'sendResetLink']);
    Route::post('/auth/reset-password', [ResetPasswordController::class, 'reset']);

    // Auth (authenticated)
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        // ... all other admin routes from sprints 3-8
    });
});
```

---

### Step 4: Sprint Execution Order

Execute the sprint prompts in order, each time prepending the preamble from Step 2:

| Sprint | What to Add | Dependencies |
|---|---|---|
| **1** | Migrations (add columns + new tables), models, seeders, services, middleware | None |
| **2** | Auth endpoints under `/admin/auth/` | Sprint 1 |
| **3** | Dashboard stats + User CRUD under `/admin/` | Sprint 2 |
| **4** | Post/Page CRUD under `/admin/posts`, `/admin/pages` | Sprint 3 |
| **5** | Category/Tag CRUD under `/admin/categories`, `/admin/tags` | Sprint 4 |
| **6** | Media library under `/admin/media` | Sprint 4 |
| **7** | Comments + Settings under `/admin/comments`, `/admin/settings` | Sprint 4 |
| **8** | Custom types, fields, menus, tools | Sprints 4-7 |

---

### Step 5: After Each Sprint — Verify Coexistence

Run this checklist after each sprint:

```bash
# 1. Verify existing public routes still work
curl http://localhost:8000/api/v1/home
curl http://localhost:8000/api/v1/branches
curl http://localhost:8000/api/v1/settings/general

# 2. Verify new admin routes exist
php artisan route:list --path=api/v1/admin

# 3. Verify migrations run cleanly
php artisan migrate:fresh --seed

# 4. Verify Swagger docs regenerate
php artisan l5-swagger:generate
```

---

## File Structure After Full Integration

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/V1/
│   │       ├── HomeController.php          ← existing
│   │       ├── BranchController.php        ← existing
│   │       ├── ...                         ← other existing
│   │       └── Admin/                      ← NEW directory
│   │           ├── AuthController.php
│   │           ├── DashboardController.php
│   │           ├── UserController.php
│   │           ├── PostController.php
│   │           ├── CommentController.php
│   │           ├── MediaController.php
│   │           ├── SettingsController.php
│   │           ├── TaxonomyController.php
│   │           ├── MenuController.php
│   │           ├── ContentTypeController.php
│   │           └── CustomFieldController.php
│   ├── Middleware/
│   │   └── CheckCapability.php             ← NEW
│   ├── Requests/
│   │   ├── StoreContactRequest.php         ← existing
│   │   └── Admin/                          ← NEW directory
│   │       ├── LoginRequest.php
│   │       ├── StorePostRequest.php
│   │       └── ...
│   └── Resources/V1/
│       ├── SliderResource.php              ← existing
│       ├── BranchResource.php              ← existing
│       ├── ...                             ← other existing
│       └── Admin/                          ← NEW directory
│           ├── UserResource.php
│           ├── PostResource.php
│           ├── CommentResource.php
│           └── ...
├── Models/
│   ├── Branch.php                          ← existing, untouched
│   ├── Setting.php                         ← existing, untouched
│   ├── User.php                            ← existing, EXTENDED
│   ├── Post.php                            ← NEW
│   ├── PostMeta.php                        ← NEW
│   ├── Term.php                            ← NEW
│   ├── Comment.php                         ← NEW
│   ├── Option.php                          ← NEW
│   └── ...                                 ← other new models
├── Services/                               ← NEW directory
│   ├── OptionService.php
│   ├── RoleService.php
│   └── MediaService.php
└── Providers/
    └── ContentTypeServiceProvider.php      ← NEW (Sprint 8)
```

```

**D:\Meem-Market\implementation_plan.md**
```
# MeemMark WordPress Admin → Laravel 12 API Migration

## Architecture: **API-Only** (No Blade/Frontend)

| Decision | Choice |
|---|---|
| Auth | Laravel Sanctum (token-based) |
| Responses | JSON via API Resources |
| Validation | Form Request classes |
| Routing | `routes/api.php` only |
| Versioning | `/api/v1/` prefix |
| File uploads | Multipart + Sanctum token |

---

## Source System Summary (As-Is)

| Component | Source |
|---|---|
| **CMS Engine** | WordPress (latest) |
| **Database** | `meemmark_db`, prefix [wp_](file:///f:/My%20journey/well-known/wp-login.php#456-464), charset `utf8` |
| **Theme** | Hello Elementor 3.4.6 |
| **Page Builder** | Elementor + Elementor Pro |
| **Custom Fields** | Advanced Custom Fields (ACF free) |
| **Custom Post Types** | Custom Post Type UI (CPTUI) 1.18.3 |
| **Admin Enhancements** | Admin Site Enhancements (ASE) 8.4.0 |
| **Admin Branding** | White Label CMS |
| **Admin Columns** | Codepress Admin Columns |

---

## Functional Scope — Feature Catalog

### 3.1 Authentication & Session Management
| Feature | WP Source |
|---|---|
| Login (username/email + password) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'login'` |
| Logout (with nonce verification) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'logout'` |
| Forgot/Lost Password (email reset link) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'lostpassword'` |
| Reset Password (key + new password) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'resetpass'` |
| User Registration (if enabled) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'register'` |
| Admin Email Confirmation | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'confirm_admin_email'` |
| Post Password Protection (cookie-based) | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) → `case 'postpass'` |
| Remember Me / Session management | cookie-based auth in WP |
| Test cookie for browser support | [wp-login.php](file:///f:/My%20journey/well-known/wp-login.php) cookie checks |

### 3.2 Dashboard
| Feature | WP Source |
|---|---|
| At a Glance widget (post/page/comment counts) | [dashboard.php](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php) → [wp_dashboard_right_now()](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php#293-443) |
| Activity widget (recent posts & comments) | [dashboard.php](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php) → [wp_dashboard_site_activity()](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php#920-960) |
| Quick Draft widget (create draft post inline) | [dashboard.php](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php) → [wp_dashboard_quick_press()](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php#533-615) |
| Recent Drafts list | [dashboard.php](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php) → [wp_dashboard_recent_drafts()](file:///f:/My%20journey/well-known/wp-admin/includes/dashboard.php#616-692) |
| Site Health Status widget | [class-wp-site-health.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-site-health.php) |

### 3.3 Post & Page Management
| Feature | WP Source |
|---|---|
| List all posts/pages (filterable, sortable, paginated) | [edit.php](file:///f:/My%20journey/well-known/wp-admin/edit.php), [class-wp-posts-list-table.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-posts-list-table.php) |
| Create new post/page (title, content, excerpt, status) | [post-new.php](file:///f:/My%20journey/well-known/wp-admin/post-new.php), [edit-form-advanced.php](file:///f:/My%20journey/well-known/wp-admin/edit-form-advanced.php) |
| Edit post/page | [post.php](file:///f:/My%20journey/well-known/wp-admin/post.php) |
| Bulk actions (delete, edit, move to trash) | [edit.php](file:///f:/My%20journey/well-known/wp-admin/edit.php) bulk handlers |
| Post statuses: publish, draft, pending, private, trash | WP post_status enum |
| Featured image (thumbnail) | `postmeta` → `_thumbnail_id` |
| Post revisions (view, compare, restore) | [revision.php](file:///f:/My%20journey/well-known/wp-admin/revision.php), [includes/revision.php](file:///f:/My%20journey/well-known/wp-admin/includes/revision.php) |
| Post meta boxes (custom fields, excerpt, comments, slug) | [includes/meta-boxes.php](file:///f:/My%20journey/well-known/wp-admin/includes/meta-boxes.php) |
| Post scheduling (publish date in future) | [post.php](file:///f:/My%20journey/well-known/wp-admin/post.php) date handling |
| Sticky posts | [options](file:///f:/My%20journey/well-known/wp-admin/includes/schema.php#350-713) → `sticky_posts` |
| Quick Edit inline (title, slug, date, status, categories) | AJAX in [admin-ajax.php](file:///f:/My%20journey/well-known/wp-admin/admin-ajax.php) |

### 3.4 Custom Post Types (from CPTUI)
| Feature | WP Source |
|---|---|
| Register custom post types with full label sets | [cptui_register_single_post_type()](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#326-549) |
| Register custom taxonomies with full label sets | [cptui_register_single_taxonomy()](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#633-794) |
| CRUD for post type definitions (stored in options) | `cptui_get_post_type_data()`, [inc/post-types.php](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/inc/post-types.php) |
| CRUD for taxonomy definitions (stored in options) | `inc/taxonomies.php` |
| Import/Export post type & taxonomy definitions | `inc/tools.php` |

### 3.5 Custom Fields (from ACF)
| Feature | WP Source |
|---|---|
| Field groups (stored as `acf-field-group` post type) | `ACF::register_post_types()` |
| Field definitions (stored as `acf-field` post type) | ACF field registration |
| Field types: text, textarea, number, email, url, select, checkbox, radio, image, file, wysiwyg, date_picker, true_false, repeater, group | ACF core field types |
| Location rules (show on post type X, page template Y, etc.) | ACF location rules |
| Field group ordering | ACF menu_order |

### 3.6 Taxonomy Management
| Feature | WP Source |
|---|---|
| Categories CRUD (hierarchical, with parent) | [edit-tags.php](file:///f:/My%20journey/well-known/wp-admin/edit-tags.php), [taxonomy.php](file:///f:/My%20journey/well-known/wp-admin/includes/taxonomy.php) |
| Tags CRUD (flat, auto-suggest) | [edit-tags.php](file:///f:/My%20journey/well-known/wp-admin/edit-tags.php) |
| Custom taxonomies CRUD (from CPTUI) | CPTUI taxonomy registration |
| Term metadata | `termmeta` table |
| Bulk actions on terms (delete, edit) | [edit-tags.php](file:///f:/My%20journey/well-known/wp-admin/edit-tags.php) |

### 3.7 Media Library
| Feature | WP Source |
|---|---|
| Upload files (images, documents, video, audio) | [async-upload.php](file:///f:/My%20journey/well-known/wp-admin/async-upload.php), [media-new.php](file:///f:/My%20journey/well-known/wp-admin/media-new.php) |
| Grid view & list view | [upload.php](file:///f:/My%20journey/well-known/wp-admin/upload.php) |
| Edit media (title, alt text, caption, description) | [media.php](file:///f:/My%20journey/well-known/wp-admin/media.php) |
| Image editing (crop, rotate, scale) | [includes/image-edit.php](file:///f:/My%20journey/well-known/wp-admin/includes/image-edit.php) |
| Media attached to posts | `post_parent` on attachment post type |
| Bulk select & delete | [upload.php](file:///f:/My%20journey/well-known/wp-admin/upload.php) |
| Filterable by type and date | [class-wp-media-list-table.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-media-list-table.php) |

### 3.8 Comment Management
| Feature | WP Source |
|---|---|
| List all comments (filterable by status) | [edit-comments.php](file:///f:/My%20journey/well-known/wp-admin/edit-comments.php), [class-wp-comments-list-table.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-comments-list-table.php) |
| Approve / Unapprove | comment status transitions |
| Reply to comment (inline) | AJAX reply on dashboard + comment list |
| Edit comment | [comment.php](file:///f:/My%20journey/well-known/wp-admin/comment.php), [edit-form-comment.php](file:///f:/My%20journey/well-known/wp-admin/edit-form-comment.php) |
| Mark as Spam / Trash / Delete permanently | comment bulk actions |
| Comment moderation queue | `comment_approved = '0'` |
| Bulk actions | [edit-comments.php](file:///f:/My%20journey/well-known/wp-admin/edit-comments.php) |

### 3.9 User Management
| Feature | WP Source |
|---|---|
| List all users (sortable, searchable, filterable by role) | [users.php](file:///f:/My%20journey/well-known/wp-admin/users.php), [class-wp-users-list-table.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-users-list-table.php) |
| Add new user (username, email, password, role) | [user-new.php](file:///f:/My%20journey/well-known/wp-admin/user-new.php) |
| Edit user profile (name, email, password, bio, role) | [user-edit.php](file:///f:/My%20journey/well-known/wp-admin/user-edit.php) |
| Delete user (with content reassignment) | [users.php](file:///f:/My%20journey/well-known/wp-admin/users.php) delete handler |
| Bulk actions (delete, change role) | [users.php](file:///f:/My%20journey/well-known/wp-admin/users.php) |
| User profile (own) | [profile.php](file:///f:/My%20journey/well-known/wp-admin/profile.php) → redirects to [user-edit.php](file:///f:/My%20journey/well-known/wp-admin/user-edit.php) |

### 3.10 Roles & Capabilities
| Role | Key Capabilities |
|---|---|
| **Administrator** | All capabilities (50+): `manage_options`, `edit_users`, `activate_plugins`, `switch_themes`, `edit_posts`, `edit_pages`, `moderate_comments`, etc. |
| **Editor** | `moderate_comments`, `manage_categories`, `edit_others_posts`, `edit_published_posts`, `publish_posts`, `edit_pages`, etc. |
| **Author** | `upload_files`, `edit_posts`, `edit_published_posts`, `publish_posts`, `delete_posts` |
| **Contributor** | `edit_posts`, `delete_posts`, `read` |
| **Subscriber** | `read` only |

### 3.11 Settings Pages
| Settings Page | Key Options |
|---|---|
| **General** | `blogname`, `blogdescription`, `siteurl`, [home](file:///f:/My%20journey/well-known/wp-content/plugins/advanced-custom-fields/acf.php#856-886), `admin_email`, `users_can_register`, `default_role`, `WPLANG`, `timezone_string`, `date_format`, `time_format`, `start_of_week` |
| **Writing** | `default_category`, `default_post_format`, `default_email_category`, mail server settings |
| **Reading** | `show_on_front`, `page_on_front`, `page_for_posts`, `posts_per_page`, `blog_public` |
| **Discussion** | `default_comment_status`, `require_name_email`, `comment_registration`, `comment_moderation`, `moderation_keys`, `disallowed_keys`, avatar settings |
| **Media** | Thumbnail, medium, large sizes, `uploads_use_yearmonth_folders` |
| **Permalinks** | `permalink_structure`, `category_base`, `tag_base` |
| **Privacy** | `wp_page_for_privacy_policy` |

### 3.12 Navigation Menus
| Feature | WP Source |
|---|---|
| Create/edit/delete menus | [nav-menus.php](file:///f:/My%20journey/well-known/wp-admin/nav-menus.php) |
| Add items (pages, posts, custom links, categories) | [nav-menus.php](file:///f:/My%20journey/well-known/wp-admin/nav-menus.php) |
| Drag-and-drop ordering | JS in [nav-menus.php](file:///f:/My%20journey/well-known/wp-admin/nav-menus.php) |
| Assign menus to theme locations (header, footer) | theme `register_nav_menus` |
| Menu item attributes (label, URL, CSS class, target) | `Walker_Nav_Menu_Edit` |

### 3.13 Appearance / Theme Management
| Feature | WP Source |
|---|---|
| Active theme display | [themes.php](file:///f:/My%20journey/well-known/wp-admin/themes.php) |
| Custom logo | `add_theme_support('custom-logo')` |
| Site icon (favicon) | [options-general.php](file:///f:/My%20journey/well-known/wp-admin/options-general.php) site icon |

### 3.14 Tools & Export/Import
| Feature | WP Source |
|---|---|
| Export content (posts, pages, media) as JSON | [export.php](file:///f:/My%20journey/well-known/wp-admin/export.php) |
| CPTUI import/export (JSON definitions) | CPTUI `inc/tools.php` |
| Site Health (tests + info) | [site-health.php](file:///f:/My%20journey/well-known/wp-admin/site-health.php), [class-wp-site-health.php](file:///f:/My%20journey/well-known/wp-admin/includes/class-wp-site-health.php) |

### 3.15 Admin UI (API Equivalents)
| Feature | API Equivalent |
|---|---|
| Screen Options (per-page pagination) | Query param `?per_page=` + user meta preference |
| Admin notices system | JSON response [notices](file:///f:/My%20journey/well-known/wp-content/plugins/custom-post-type-ui/custom-post-type-ui.php#901-980) field |
| Menu structure | `GET /api/v1/admin/menu` endpoint returning sidebar structure |

---

## Database Schema (Target Laravel Migrations)

| WP Table | Laravel Model | Key Columns |
|---|---|---|
| `wp_users` | `User` | id, login, pass, nicename, email, url, registered, status, display_name |
| `wp_usermeta` | `UserMeta` | umeta_id, user_id, meta_key, meta_value |
| `wp_posts` | `Post` | ID, post_author, post_date, post_content, post_title, post_excerpt, post_status, post_type, post_parent, menu_order, post_mime_type |
| `wp_postmeta` | `PostMeta` | meta_id, post_id, meta_key, meta_value |
| `wp_terms` | `Term` | term_id, name, slug, term_group |
| `wp_term_taxonomy` | `TermTaxonomy` | term_taxonomy_id, term_id, taxonomy, description, parent, count |
| `wp_term_relationships` | `TermRelationship` | object_id, term_taxonomy_id, term_order |
| `wp_termmeta` | `TermMeta` | meta_id, term_id, meta_key, meta_value |
| `wp_comments` | `Comment` | comment_ID, comment_post_ID, author, author_email, date, content, approved, parent, user_id |
| `wp_commentmeta` | `CommentMeta` | meta_id, comment_id, meta_key, meta_value |
| `wp_options` | `Option` | option_id, option_name, option_value, autoload |
| `wp_links` | `Link` | link_id, url, name, image, target, description, visible, owner, rating |

---

## Sprint Plan (8 Sprints)

---

### Sprint 1: Project Scaffold, Database & Core Services

```text
ROLE: Senior Laravel 12 / PHP 8.2 developer. Clean, SOLID, production-ready code.

TASK: Create a Laravel 12 API-only project for "MeemMark Admin Panel" — a CMS REST API replicating WordPress admin logic.

REQUIREMENTS:

1. PROJECT: Laravel 12, PHP 8.2, MySQL utf8mb4. Install Laravel Sanctum for token auth. Remove all Blade/frontend scaffolding. API-only.

2. MIGRATIONS — create ALL tables:

   a) users: id, login (string 60 unique), password, nicename (50), email (100 unique), url (100), registered_at (datetime), activation_key (255), status (tinyInt default 0), display_name (250)
   b) user_meta: id, user_id (FK→users, indexed), meta_key (255 indexed nullable), meta_value (longText nullable)
   c) posts: id, author_id (FK→users), post_date, post_date_gmt, content (longText), title (text), excerpt (text), status (20 default 'publish' indexed), comment_status (20 default 'open'), ping_status (20 default 'open'), password (255), slug (200 indexed), post_modified, post_modified_gmt, content_filtered (longText), parent_id (unsignedBigInt default 0 indexed), guid (255), menu_order (int default 0), type (20 default 'post' indexed), mime_type (100), comment_count (bigInt default 0). Composite indexes: (type,status,post_date,id), (type,status,author_id)
   d) post_meta: id, post_id (FK→posts), meta_key (255 indexed nullable), meta_value (longText nullable)
   e) terms: id, name (200), slug (200 indexed), term_group (bigInt default 0)
   f) term_taxonomy: id, term_id (FK→terms), taxonomy (32 indexed), description (longText), parent (unsignedBigInt default 0), count (bigInt default 0). Unique: (term_id, taxonomy)
   g) term_relationships: object_id + term_taxonomy_id (composite PK), term_order (int default 0)
   h) term_meta: id, term_id (FK→terms), meta_key (255 indexed nullable), meta_value (longText nullable)
   i) comments: id, post_id (FK→posts), author_name (tinyText), author_email (100), author_url (200), author_ip (100), comment_date, comment_date_gmt, content (text), karma (int default 0), approved (20 default '1'), agent (255), type (20 default 'comment'), parent_id (unsignedBigInt default 0 indexed), user_id (unsignedBigInt default 0)
   j) comment_meta: id, comment_id (FK→comments), meta_key (255 indexed nullable), meta_value (longText nullable)
   k) options: id, name (191 unique), value (longText), autoload (20 default 'yes' indexed)
   l) links: id, url (255), name (255), image (255), target (25), description (255), visible (20 default 'Y'), owner_id (unsignedBigInt default 1), rating (int default 0), updated_at (datetime), rel (255), notes (mediumText), rss (255)

3. SEEDER:
   - Seed user_roles option with WP's 5 roles (Administrator/Editor/Author/Contributor/Subscriber) and exact capability maps
   - Default admin user (login: admin, email: admin@meemmark.com, password: hashed, role: administrator)
   - Default options: blogname, blogdescription, siteurl, home, admin_email, date_format, time_format, posts_per_page(10), default_role(subscriber), timezone_string, start_of_week(1), users_can_register(0), default_comment_status(open), blog_public(1), show_on_front(posts), thumbnail/medium/large sizes, uploads_use_yearmonth_folders(1), default_category(1), comment_moderation(0), require_name_email(1)
   - Default "Uncategorized" category term

4. MODELS with relationships:
   User→hasMany(UserMeta,Post), Post→belongsTo(User),hasMany(PostMeta,Comment),belongsToMany(Term via TermRelationship), Term→hasOne(TermTaxonomy),hasMany(TermMeta), Comment→belongsTo(Post,User),hasMany(CommentMeta,self as replies), Option (static get/set/delete helpers)

5. SERVICES:
   - OptionService: get($name,$default), set($name,$value,$autoload), delete($name) — request-cached
   - RoleService: getRoles(), getRole($name), userCan(User,$capability):bool, getUserRole(User):?string, setUserRole(User,$role):void

6. MIDDLEWARE:
   - CheckCapability: parameterised, e.g. `can:manage_options`
   - Register in bootstrap for API routes

7. BASE CLASSES:
   - ApiController base: standardised JSON responses — success($data,$status=200), error($message,$status), paginated($query,$resource)
   - Consistent error format: {success:false, message:string, errors?:object}
   - Consistent success format: {success:true, data:mixed, meta?:object}

OUTPUT: Complete files, no stubs.
```

---

### Sprint 2: Authentication API

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Project has all migrations, models, OptionService, RoleService, CheckCapability middleware, ApiController base. Uses Sanctum for token auth.

TASK: Implement auth API replicating WordPress wp-login.php logic.

ENDPOINTS:
- POST /api/v1/auth/login — body: {login, password, remember_me?}
  → Returns Sanctum token + user resource. Rate limit: 5/min per IP.
  → Error messages match WP: "Unknown username…", "The password you entered for username X is incorrect."
- POST /api/v1/auth/logout — (authenticated) Revoke current token.
- POST /api/v1/auth/forgot-password — body: {login} (username or email)
  → Generate token, store in user_meta, send reset email. Return success message.
- POST /api/v1/auth/reset-password — body: {token, email, password, password_confirmation}
  → Validate token (24hr expiry), reset password, invalidate token.
- POST /api/v1/auth/register — body: {username, email} (only if users_can_register option == 1)
  → Auto-generate password, assign default_role, send email. Return success.
- GET  /api/v1/auth/me — (authenticated) Return current user with role & capabilities.
- PUT  /api/v1/auth/me — (authenticated) Update own profile fields.

IMPLEMENTATION:
- AuthController with each action method
- FormRequests: LoginRequest, ForgotPasswordRequest, ResetPasswordRequest, RegisterRequest
- UserResource: id, login, email, display_name, nicename, url, registered_at, role, capabilities, avatar_url
- Mailables: PasswordResetMail, NewUserRegistrationMail
- Token abilities: map user capabilities to Sanctum token abilities

OUTPUT: Controller, FormRequests, Resource, Mailables, routes. Complete.
```

---

### Sprint 3: Dashboard API & User Management

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Has all models, auth (Sanctum tokens), services, middleware.

TASK: Implement Dashboard stats API and full User Management CRUD API.

ENDPOINTS — DASHBOARD:
- GET /api/v1/dashboard/stats — (capability: read)
  → Returns: {posts_count, pages_count, comments_count, comments_pending, recent_posts[5], recent_comments[5], recent_drafts[4]}
- POST /api/v1/dashboard/quick-draft — (capability: edit_posts) body: {title, content}
  → Creates draft post, returns PostResource

ENDPOINTS — USERS:
- GET    /api/v1/users — (capability: list_users) Query: ?role=, ?search=, ?sort_by=, ?sort_dir=, ?per_page=, ?page=
  → Paginated UserResource collection with role filter tabs counts {all, administrator, editor, author, contributor, subscriber}
- POST   /api/v1/users — (capability: create_users) body: {login, email, password?, first_name?, last_name?, url?, role, send_notification?}
- GET    /api/v1/users/{id} — (capability: edit_users OR own profile)
- PUT    /api/v1/users/{id} — body: {first_name?, last_name?, nickname?, display_name?, email?, url?, bio?, password?, role?}
  → Role change only if current user can promote_users
- DELETE /api/v1/users/{id} — (capability: delete_users) Query: ?reassign_to= (user ID for content)
  → Reassign or delete content, cascade delete user_meta
- POST   /api/v1/users/bulk — body: {action: 'delete'|'change_role', user_ids:[], role?:string, reassign_to?:int}

RESOURCES: UserResource, UserCollection (with role counts in meta), DashboardResource
FORM REQUESTS: StoreUserRequest, UpdateUserRequest, BulkUserRequest

OUTPUT: DashboardController, UserController, Resources, FormRequests, routes. Complete.
```

---

### Sprint 4: Post & Page Management API

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Has all models, auth, users, dashboard, services.

TASK: Implement Post and Page CRUD API, including revisions, quick edit, and trash/restore.

ENDPOINTS — POSTS (type=post):
- GET    /api/v1/posts — Query: ?status=, ?category=, ?tag=, ?author=, ?search=, ?month=, ?per_page=, ?page=, ?sort_by=, ?sort_dir=
  → Paginated. Meta includes status counts: {all, published, draft, pending, trash}
- POST   /api/v1/posts — body: {title, content, excerpt?, status?, slug?, password?, categories?:[], tags?:[], featured_image_id?, menu_order?, author_id?, scheduled_at?}
- GET    /api/v1/posts/{id} — includes meta, categories, tags, featured_image, author
- PUT    /api/v1/posts/{id} — same body as create. Creates a revision before updating.
- DELETE /api/v1/posts/{id} — Query: ?force=true for permanent delete (default: move to trash)
- PUT    /api/v1/posts/{id}/trash — move to trash (store old status in meta)
- PUT    /api/v1/posts/{id}/restore — restore from trash
- POST   /api/v1/posts/bulk — body: {action:'trash'|'restore'|'delete'|'edit', post_ids:[], data?:{status?,category?,tag?}}
- GET    /api/v1/posts/{id}/revisions — list revisions
- POST   /api/v1/posts/{id}/revisions/{rev}/restore — restore a revision

ENDPOINTS — PAGES (type=page, same controller with type parameter):
- GET    /api/v1/pages — same filters minus category/tag, plus ?parent=
- POST   /api/v1/pages — body adds: parent_id?, template?, menu_order?
- GET/PUT/DELETE /api/v1/pages/{id}
- Trash/restore/bulk same pattern

POST LOGIC:
- Auto-generate slug from title, ensure unique per type
- post_date/gmt set on publish, post_modified/gmt on every save
- On trash: store old status in post_meta '_wp_trash_meta_status'
- On permanent delete: cascade post_meta, term_relationships, comments+meta
- Revisions: store as type='revision', parent_id=original post

RESOURCES: PostResource (with embedded categories, tags, author, featured_image, meta), PostCollection, RevisionResource
FORM REQUESTS: StorePostRequest, UpdatePostRequest, BulkPostRequest

OUTPUT: PostController (handles both post/page via type param), RevisionController, Resources, FormRequests, routes. Complete.
```

---

### Sprint 5: Taxonomy Management API

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Has all models, auth, users, posts/pages with category/tag attachment.

TASK: Implement Category and Tag CRUD API, plus any custom taxonomy support.

ENDPOINTS — CATEGORIES (taxonomy=category):
- GET    /api/v1/categories — Query: ?search=, ?parent=, ?per_page=, ?page=, ?sort_by=, ?hide_empty=
- POST   /api/v1/categories — body: {name, slug?, parent_id?, description?}
- GET    /api/v1/categories/{id}
- PUT    /api/v1/categories/{id}
- DELETE /api/v1/categories/{id} — cannot delete default_category, only removes relationships (not posts)
- POST   /api/v1/categories/bulk — body: {action:'delete', term_ids:[]}

ENDPOINTS — TAGS (taxonomy=post_tag, same controller):
- GET/POST/GET/PUT/DELETE /api/v1/tags — same pattern, no parent (flat taxonomy)
- POST /api/v1/tags/bulk

ENDPOINTS — GENERIC (for custom taxonomies from CPTUI):
- GET /api/v1/taxonomies/{taxonomy}/terms — same pattern
- POST/GET/PUT/DELETE on terms within a taxonomy

LOGIC:
- Auto-generate slug from name, unique within taxonomy
- Maintain count on term_taxonomy (recalculate on relationship changes)
- Hierarchical support for categories and custom hierarchical taxonomies

RESOURCES: TermResource (with taxonomy, parent, count), TermCollection
FORM REQUESTS: StoreTermRequest, UpdateTermRequest

OUTPUT: TaxonomyController, Resources, FormRequests, routes. Complete.
```

---

### Sprint 6: Media Library API

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Has all models, auth, posts/pages, taxonomies.

TASK: Implement Media Library API (upload, CRUD, image processing).

ENDPOINTS:
- GET    /api/v1/media — Query: ?type=(image|audio|video|document), ?month=, ?search=, ?per_page=, ?page=, ?attached_to=
- POST   /api/v1/media/upload — multipart, capability: upload_files. Accepts multiple files.
- GET    /api/v1/media/{id}
- PUT    /api/v1/media/{id} — body: {title?, caption?, alt_text?, description?}
- DELETE /api/v1/media/{id} — permanent delete (remove file + meta + post record)
- POST   /api/v1/media/bulk — body: {action:'delete', media_ids:[]}
- POST   /api/v1/media/{id}/edit — body: {action:'crop'|'rotate'|'flip'|'scale', params:{}}

UPLOAD LOGIC:
- Store in storage/app/public/uploads/{Y}/{m}/{filename} (if uploads_use_yearmonth_folders option)
- Create post record: type='attachment', mime_type, title from filename, status='inherit'
- Store post_meta: _wp_attached_file (relative path), _wp_attachment_metadata (JSON: width, height, filesize, sizes{})
- For images: generate thumbnail (150×150), medium (300×300), large (1024×1024) using Intervention Image
- Allowed types: jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,mp4,mp3,wav,ogg,zip
- Sanitise filenames

RESOURCES: MediaResource (includes url, sizes, dimensions, file_info, attached_to)
SERVICE: MediaService — handles upload, resize, edit operations

OUTPUT: MediaController, MediaService, MediaResource, FormRequests, routes. Complete.
```

---

### Sprint 7: Comments & Settings API

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev continuing MeemMark API.

CONTEXT: Has all models, auth, users, posts, taxonomies, media.

TASK: Implement Comment moderation API and all Settings endpoints.

ENDPOINTS — COMMENTS:
- GET    /api/v1/comments — Query: ?status=(approved|pending|spam|trash), ?post_id=, ?search=, ?per_page=, ?page=
  → Meta includes status counts
- GET    /api/v1/comments/{id}
- PUT    /api/v1/comments/{id} — body: {author_name?, author_email?, author_url?, content?, status?, date?}
- DELETE /api/v1/comments/{id} — permanent delete
- POST   /api/v1/comments/{id}/approve
- POST   /api/v1/comments/{id}/unapprove
- POST   /api/v1/comments/{id}/spam
- POST   /api/v1/comments/{id}/trash
- POST   /api/v1/comments/{id}/restore
- POST   /api/v1/comments/{id}/reply — body: {content} — creates child comment by current user
- POST   /api/v1/comments/bulk — body: {action:'approve'|'unapprove'|'spam'|'trash'|'delete', comment_ids:[]}

ENDPOINTS — SETTINGS (capability: manage_options):
- GET  /api/v1/settings/general → returns all general options as JSON object
- PUT  /api/v1/settings/general → body: {blogname?, blogdescription?, siteurl?, home?, admin_email?, users_can_register?, default_role?, timezone_string?, date_format?, time_format?, start_of_week?}
- GET/PUT /api/v1/settings/writing → {default_category, default_post_format}
- GET/PUT /api/v1/settings/reading → {show_on_front, page_on_front, page_for_posts, posts_per_page, blog_public}
- GET/PUT /api/v1/settings/discussion → {default_comment_status, require_name_email, comment_registration, comment_moderation, moderation_keys, disallowed_keys, comments_notify, show_avatars, avatar_default, avatar_rating, close_comments_days_old, thread_comments, thread_comments_depth, page_comments, comments_per_page, default_comments_page, comment_order}
- GET/PUT /api/v1/settings/media → {thumbnail_size_w/h, thumbnail_crop, medium_size_w/h, large_size_w/h, uploads_use_yearmonth_folders}
- GET/PUT /api/v1/settings/permalinks → {permalink_structure, category_base, tag_base}
- GET/PUT /api/v1/settings/privacy → {wp_page_for_privacy_policy}

RESOURCES: CommentResource, SettingsResource (keyed object per section)
FORM REQUESTS: UpdateCommentRequest, per-section SettingsRequest

OUTPUT: CommentController, SettingsController, Resources, FormRequests, routes. Complete.
```

---

### Sprint 8: Custom Post Types, Custom Fields, Menus, Tools & Polish

```text
ROLE: Senior Laravel 12 / PHP 8.2 dev finishing MeemMark API.

CONTEXT: Full API exists: auth, users, posts/pages, taxonomies, media, comments, settings.

TASK: Implement CPTUI-style custom post types, ACF-style custom fields, navigation menus, tools/export, site health, and API polish.

ENDPOINTS — CUSTOM POST TYPES:
- GET/POST /api/v1/content-types/post-types
- GET/PUT/DELETE /api/v1/content-types/post-types/{slug}
  → Body: {slug, label, singular_label, labels:{}, public, show_ui, has_archive, hierarchical, supports:[], taxonomies:[], menu_icon, menu_position}
  → Stored in options (key: cptui_post_types). ContentTypeServiceProvider dynamically registers routes.

ENDPOINTS — CUSTOM TAXONOMIES:
- GET/POST /api/v1/content-types/taxonomies
- GET/PUT/DELETE /api/v1/content-types/taxonomies/{slug}
  → Stored in options (key: cptui_taxonomies)

ENDPOINTS — CUSTOM FIELDS (ACF-style):
- GET/POST /api/v1/field-groups
- GET/PUT/DELETE /api/v1/field-groups/{id}
  → Stored as posts type='acf-field-group'. Body includes: {title, status, fields:[], location_rules:[], position, style, label_placement}
  → Fields: [{label, name, type, instructions, required, default_value, options:{}}]
  → Stored as posts type='acf-field', parent_id=group
- FieldRenderService: when fetching/saving a post, include applicable custom field values based on location rules

ENDPOINTS — NAVIGATION MENUS:
- GET/POST /api/v1/menus
- GET/PUT/DELETE /api/v1/menus/{id}
- POST /api/v1/menus/{id}/items — body: {type, object_id?, url?, title, parent_item_id?, position}
- PUT/DELETE /api/v1/menus/{id}/items/{item_id}
- PUT /api/v1/menus/{id}/locations — body: {header?: menu_id, footer?: menu_id}
  → Menu items stored as posts type='nav_menu_item' with meta keys

ENDPOINTS — TOOLS:
- POST /api/v1/tools/export — body: {type?, category?, author?, start_date?, end_date?, status?}
  → Returns downloadable JSON of selected content with meta, terms, comments
- GET /api/v1/tools/site-health
  → Returns: {tests:[{name,status:(good|recommended|critical),description}], info:{php_version,laravel_version,db_version,server,disk_space,extensions}}

POLISH:
- ContentTypeServiceProvider: reads options on boot, registers dynamic routes for custom types/taxonomies
- FieldRenderService: resolves which field groups apply to a post based on location rules
- API rate limiting config
- Consistent error handling via Handler.php
- OpenAPI/Swagger doc generation annotations (optional but recommended)

OUTPUT: ContentTypeController, CustomFieldController, MenuController, ExportController, SiteHealthController, ContentTypeServiceProvider, FieldRenderService, all Resources, FormRequests, routes. Complete.
```

---

## Verification Plan

### Automated
```bash
php artisan test --filter=AuthTest
php artisan test --filter=UserTest
php artisan test --filter=PostTest
php artisan test --filter=TaxonomyTest
php artisan test --filter=MediaTest
php artisan test --filter=CommentTest
php artisan test --filter=SettingsTest
php artisan test --filter=ContentTypeTest
```

### Manual (via Postman/Insomnia)
1. **Sprint 1**: `php artisan migrate --seed` — verify tables + seed data
2. **Sprint 2**: Login → get token → use token on /auth/me
3. **Sprint 3**: GET /dashboard/stats, POST /dashboard/quick-draft, full user CRUD
4. **Sprint 4**: Post/page CRUD, trash/restore, revisions
5. **Sprint 5**: Category/tag CRUD, term counts
6. **Sprint 6**: Upload file, get media list, edit metadata, delete
7. **Sprint 7**: Comment moderation flow, GET/PUT all 7 settings sections
8. **Sprint 8**: Register custom post type → CRUD its posts, create field group → see fields on post, menu CRUD, export, site health

```



Now let me check which Sprint 5 files have been created and what's already done:

**Command Executed:**
```bash
git --no-pager status
```

**Output:**
```
On branch Feat/AdminDash_S4
Your branch is up to date with 'origin/Feat/AdminDash_S4'.

Changes not staged for commit:
  (use "git add <file>..." to update what will be committed)
  (use "git restore <file>..." to discard changes in working directory)
	modified:   app/Models/TermTaxonomy.php
	modified:   routes/api.php

Untracked files:
  (use "git add <file>..." to include in what will be committed)
	.agent/
	.brv/
	app/Http/Controllers/Api/V1/Admin/TaxonomyController.php
	app/Http/Requests/Admin/BulkTermRequest.php
	app/Http/Requests/Admin/StoreTermRequest.php
	app/Http/Requests/Admin/UpdateTermRequest.php
	app/Http/Resources/V1/Admin/TermCollection.php
	app/Http/Resources/V1/Admin/TermResource.php

no changes added to commit (use "git add" and/or "git commit -a")
```

Good! I can see Sprint 5 files have been created. Let me check the modified files and the created files to understand what's done:


**Files Read:**

**D:\Meem-Market\app\Http\Controllers\Api\V1\Admin\TaxonomyController.php**
```
<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkTermRequest;
use App\Http\Requests\Admin\StoreTermRequest;
use App\Http\Requests\Admin\UpdateTermRequest;
use App\Http\Resources\V1\Admin\TermCollection;
use App\Http\Resources\V1\Admin\TermResource;
use App\Models\Term;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Shared controller for Categories, Tags, and Custom Taxonomies.
 *
 * The taxonomy type is resolved from the route prefix or path parameter:
 * - /admin/categories → taxonomy = 'category'
 * - /admin/tags → taxonomy = 'post_tag'
 * - /admin/taxonomies/{taxonomy}/terms → taxonomy from parameter
 */
#[OA\Tag(name: "Admin Taxonomies", description: "Category, Tag, and Custom Taxonomy CRUD")]
class TaxonomyController extends ApiController
{
    public function __construct(
        private readonly OptionService $optionService
    ) {}

    /**
     * Resolve the taxonomy type from the current route.
     */
    private function resolveTaxonomy(Request $request): string
    {
        $prefix = $request->route()->getPrefix() ?? '';
        $taxonomy = $request->route('taxonomy');

        if ($taxonomy) {
            return $taxonomy;
        }

        if (str_contains($prefix, 'categories')) {
            return 'category';
        }

        if (str_contains($prefix, 'tags')) {
            return 'post_tag';
        }

        return 'category'; // Default
    }

    /**
     * Standard eager-load set.
     */
    private function eagerLoads(): array
    {
        return ['term', 'parentTerm.term'];
    }

    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/categories",
        operationId: "listCategories",
        summary: "List categories (paginated)",
        description: "Returns a paginated list of categories with optional filters.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "parent", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "hide_empty", in: "query", required: false, schema: new OA\Schema(type: "boolean", default: false)),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["name", "count", "id"], default: "name")),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated term list"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);

        $query = TermTaxonomy::query()
            ->with($this->eagerLoads())
            ->where('taxonomy', $taxonomy);

        // Search filter
        if ($search = $request->query('search')) {
            $query->whereHas('term', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
        }

        // Parent filter (hierarchical taxonomies only)
        if ($request->has('parent')) {
            $query->where('parent', $request->query('parent'));
        }

        // Hide empty terms
        if ($request->boolean('hide_empty')) {
            $query->where('count', '>', 0);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'name');
        $sortDir = $request->query('sort_dir', 'asc');

        if ($sortBy === 'name') {
            $query->join('terms', 'term_taxonomy.term_id', '=', 'terms.id')
                ->orderBy('terms.name', $sortDir)
                ->select('term_taxonomy.*');
        } elseif (in_array($sortBy, ['count', 'id'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $collection = new TermCollection($paginator);
        $collection->taxonomy = $taxonomy;

        return $collection->response()->setStatusCode(200);
    }

    // ─── Create ──────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/categories",
        operationId: "createCategory",
        summary: "Create a new category",
        description: "Creates a new category/tag. Auto-generates slug from name if not provided.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Technology"),
                    new OA\Property(property: "slug", type: "string", example: "technology"),
                    new OA\Property(property: "parent_id", type: "integer", nullable: true, example: null),
                    new OA\Property(property: "description", type: "string", example: "Tech-related posts"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Term created"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StoreTermRequest $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $validated = $request->validated();

        // Generate unique slug
        $slug = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['name'],
            $taxonomy
        );

        // Create term
        $term = Term::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'term_group' => 0,
        ]);

        // Create term taxonomy
        $termTaxonomy = TermTaxonomy::create([
            'term_id' => $term->id,
            'taxonomy' => $taxonomy,
            'description' => $validated['description'] ?? '',
            'parent' => $validated['parent_id'] ?? 0,
            'count' => 0,
        ]);

        $termTaxonomy->load($this->eagerLoads());

        return $this->success(new TermResource($termTaxonomy), 201);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/categories/{id}",
        operationId: "showCategory",
        summary: "Get a single category",
        description: "Returns a full category/tag with details.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Term details"),
            new OA\Response(response: 404, description: "Term not found"),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::with($this->eagerLoads())
            ->where('taxonomy', $taxonomy)
            ->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        return $this->success(new TermResource($termTaxonomy));
    }

    // ─── Update ──────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/categories/{id}",
        operationId: "updateCategory",
        summary: "Update a category",
        description: "Updates a category/tag.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "slug", type: "string"),
                new OA\Property(property: "parent_id", type: "integer", nullable: true),
                new OA\Property(property: "description", type: "string"),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Term updated"),
            new OA\Response(response: 404, description: "Term not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(UpdateTermRequest $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::with($this->eagerLoads())
            ->where('taxonomy', $taxonomy)
            ->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        $validated = $request->validated();

        // Update term (name, slug)
        if (isset($validated['name']) || isset($validated['slug'])) {
            $termUpdates = [];

            if (isset($validated['name'])) {
                $termUpdates['name'] = $validated['name'];
            }

            if (isset($validated['slug'])) {
                $termUpdates['slug'] = $this->generateUniqueSlug(
                    $validated['slug'],
                    $taxonomy,
                    $termTaxonomy->term_id
                );
            }

            if (!empty($termUpdates)) {
                $termTaxonomy->term->update($termUpdates);
            }
        }

        // Update term taxonomy (description, parent)
        $taxonomyUpdates = [];

        if (array_key_exists('description', $validated)) {
            $taxonomyUpdates['description'] = $validated['description'];
        }

        if (array_key_exists('parent_id', $validated)) {
            // Prevent circular parent relationship
            if ($validated['parent_id'] == $termTaxonomy->id) {
                return $this->error('A term cannot be its own parent.', 422);
            }
            $taxonomyUpdates['parent'] = $validated['parent_id'] ?? 0;
        }

        if (!empty($taxonomyUpdates)) {
            $termTaxonomy->update($taxonomyUpdates);
        }

        $termTaxonomy->refresh();
        $termTaxonomy->load($this->eagerLoads());

        return $this->success(new TermResource($termTaxonomy));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/categories/{id}",
        operationId: "deleteCategory",
        summary: "Delete a category",
        description: "Deletes a category/tag. Cannot delete the default category. Only removes relationships (posts are not deleted).",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Term deleted"),
            new OA\Response(response: 404, description: "Term not found"),
            new OA\Response(response: 422, description: "Cannot delete default category"),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::where('taxonomy', $taxonomy)->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        // Prevent deleting default category
        if ($taxonomy === 'category') {
            $defaultCategoryId = $this->optionService->get('default_category', 1);
            if ($termTaxonomy->id == $defaultCategoryId) {
                return $this->error('Cannot delete the default category.', 422);
            }
        }

        // Delete relationships
        TermRelationship::where('term_taxonomy_id', $termTaxonomy->id)->delete();

        // Delete term taxonomy
        $termTaxonomy->delete();

        // Delete term if no other taxonomies reference it
        $remainingTaxonomies = TermTaxonomy::where('term_id', $termTaxonomy->term_id)->count();
        if ($remainingTaxonomies === 0) {
            Term::where('id', $termTaxonomy->term_id)->delete();
        }

        return $this->success(['message' => 'Term deleted successfully.']);
    }

    // ─── Bulk ────────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/categories/bulk",
        operationId: "bulkCategoryAction",
        summary: "Perform bulk term action",
        description: "Supports action: delete. Removes selected terms and their relationships.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["action", "term_ids"],
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["delete"]),
                new OA\Property(property: "term_ids", type: "array", items: new OA\Items(type: "integer")),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function bulk(BulkTermRequest $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $validated = $request->validated();
        $termIds = $validated['term_ids'];

        // Prevent bulk-deleting default category
        if ($taxonomy === 'category') {
            $defaultCategoryId = $this->optionService->get('default_category', 1);
            if (in_array($defaultCategoryId, $termIds, false)) {
                return $this->error('Cannot delete the default category.', 422);
            }
        }

        $terms = TermTaxonomy::where('taxonomy', $taxonomy)
            ->whereIn('id', $termIds)
            ->get();

        if ($terms->isEmpty()) {
            return $this->error('No valid terms found.', 422);
        }

        $affected = $terms->count();

        foreach ($terms as $termTaxonomy) {
            // Delete relationships
            TermRelationship::where('term_taxonomy_id', $termTaxonomy->id)->delete();

            // Delete term taxonomy
            $termTaxonomy->delete();

            // Delete term if no other taxonomies reference it
            $remainingTaxonomies = TermTaxonomy::where('term_id', $termTaxonomy->term_id)->count();
            if ($remainingTaxonomies === 0) {
                Term::where('id', $termTaxonomy->term_id)->delete();
            }
        }

        return $this->success([
            'message' => "{$affected} term(s) deleted successfully.",
            'affected' => $affected,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate a unique slug within the taxonomy.
     * Appends -2, -3, etc. if a collision is found.
     */
    private function generateUniqueSlug(string $text, string $taxonomy, ?int $excludeTermId = null): string
    {
        $baseSlug = Str::slug($text) ?: 'untitled';
        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            // Check if slug exists in any term used by this taxonomy
            $query = TermTaxonomy::where('taxonomy', $taxonomy)
                ->whereHas('term', fn($q) => $q->where('slug', $slug));

            if ($excludeTermId) {
                $query->where('term_id', '!=', $excludeTermId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}

```

**D:\Meem-Market\app\Http\Requests\Admin\StoreTermRequest.php**
```
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:200',
            'slug' => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'parent_id' => 'nullable|integer|exists:term_taxonomy,id',
            'description' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'parent_id.exists' => 'The selected parent term does not exist.',
        ];
    }
}

```

**D:\Meem-Market\app\Http\Requests\Admin\UpdateTermRequest.php**
```
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|required|string|max:200',
            'slug' => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'parent_id' => 'nullable|integer|exists:term_taxonomy,id',
            'description' => 'nullable|string|max:5000',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'slug.regex' => 'The slug may only contain lowercase letters, numbers, and hyphens.',
            'parent_id.exists' => 'The selected parent term does not exist.',
        ];
    }
}

```

**D:\Meem-Market\app\Http\Requests\Admin\BulkTermRequest.php**
```
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'action' => ['required', Rule::in(['delete'])],
            'term_ids' => 'required|array|min:1',
            'term_ids.*' => 'integer|exists:term_taxonomy,id',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'The action field is required.',
            'action.in' => 'Invalid bulk action.',
            'term_ids.required' => 'At least one term must be selected.',
            'term_ids.*.exists' => 'One or more selected terms do not exist.',
        ];
    }
}

```

**D:\Meem-Market\app\Http\Resources\V1\Admin\TermResource.php**
```
<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminTerm",
    title: "Admin Term Resource",
    description: "Term (category/tag/custom taxonomy) with full details",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "term_id", type: "integer", example: 1),
        new OA\Property(property: "name", type: "string", example: "Technology"),
        new OA\Property(property: "slug", type: "string", example: "technology"),
        new OA\Property(property: "taxonomy", type: "string", example: "category"),
        new OA\Property(property: "description", type: "string", example: "Articles about technology"),
        new OA\Property(property: "parent", type: "integer", nullable: true, example: null),
        new OA\Property(property: "count", type: "integer", example: 5, description: "Number of posts using this term"),
        new OA\Property(property: "parent_term", type: "object", nullable: true, description: "Parent term details if hierarchical"),
    ]
)]
class TermResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // $this is TermTaxonomy instance
        return [
            'id' => $this->id,
            'term_id' => $this->term_id,
            'name' => $this->term?->name,
            'slug' => $this->term?->slug,
            'taxonomy' => $this->taxonomy,
            'description' => $this->description ?? '',
            'parent' => $this->parent ?: null,
            'count' => $this->count,
            'parent_term' => $this->when(
                $this->parent && $this->parentTerm,
                fn() => [
                    'id' => $this->parentTerm?->id,
                    'name' => $this->parentTerm?->term?->name,
                    'slug' => $this->parentTerm?->term?->slug,
                ]
            ),
        ];
    }
}

```

**D:\Meem-Market\app\Http\Resources\V1\Admin\TermCollection.php**
```
<?php

namespace App\Http\Resources\V1\Admin;

use App\Models\TermTaxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class TermCollection extends ResourceCollection
{
    public string $taxonomy = 'category';

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'total' => $this->total(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
                'taxonomy' => $this->taxonomy,
            ],
        ];
    }
}

```



Now let me check the routes file to see what routes have been added:


**Files Read:**

**D:\Meem-Market\routes\api.php**
```
<?php

use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\PostController;
use App\Http\Controllers\Api\V1\Admin\TaxonomyController;
use App\Http\Controllers\Api\V1\ContentController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\BranchController;
use App\Http\Controllers\Api\V1\CareerController;
use App\Http\Controllers\Api\V1\ContactController;
use App\Http\Controllers\Api\V1\CountryController;
use App\Http\Controllers\Api\V1\HomeController;
use App\Http\Controllers\Api\V1\OfferController;
use App\Http\Controllers\Api\V1\SettingController;
use Illuminate\Support\Facades\Route;

// ─── PUBLIC ROUTES (existing, untouched) ─────────────────────────
Route::prefix('v1')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{slug}', [BranchController::class, 'show']);
    Route::get('/offers', [OfferController::class, 'index']);
    Route::get('/about', [AboutController::class, 'index']);
    Route::get('/careers', [CareerController::class, 'index']);
    Route::get('/careers/{slug}', [CareerController::class, 'show']);
    Route::get('/contact', [ContactController::class, 'index']);
    Route::post('/contact', [ContactController::class, 'store']);
    Route::get('/settings/{group}', [SettingController::class, 'show']);

    // Blog & Pages — public read-only (CMS content)
    Route::get('/blog', [ContentController::class, 'blogIndex']);
    Route::get('/blog/{slug}', [ContentController::class, 'blogShow']);
    Route::get('/pages/{slug}', [ContentController::class, 'pageShow']);
});

// ─── ADMIN ROUTES ────────────────────────────────────────────────
Route::prefix('v1/admin')->group(function () {

    // Auth — unauthenticated
    Route::prefix('auth')->group(function () {
        Route::post('/login', [AuthController::class, 'login'])
            ->middleware('throttle:5,1');
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthController::class, 'resetPassword']);
    });

    // Auth — authenticated
    Route::middleware('auth:sanctum')->prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::put('/me', [AuthController::class, 'updateProfile']);
    });

    // ── Authenticated admin endpoints (Sprint 3+) ────────────
    Route::middleware('auth:sanctum')->group(function () {

        // Dashboard
        Route::prefix('dashboard')->group(function () {
            Route::get('/stats', [DashboardController::class, 'stats'])
                ->middleware('can_do:read');
            Route::post('/quick-draft', [DashboardController::class, 'quickDraft'])
                ->middleware('can_do:edit_posts');
        });

        // User Management
        Route::prefix('users')->group(function () {
            Route::get('/', [UserController::class, 'index'])
                ->middleware('can_do:list_users');
            Route::post('/', [UserController::class, 'store'])
                ->middleware('can_do:create_users');
            Route::post('/bulk', [UserController::class, 'bulk'])
                ->middleware('can_do:delete_users');
            Route::get('/{user}', [UserController::class, 'show']);
            Route::put('/{user}', [UserController::class, 'update'])
                ->middleware('can_do:edit_users');
            Route::delete('/{user}', [UserController::class, 'destroy'])
                ->middleware('can_do:delete_users');
        });

        // Post Management (Sprint 4)
        Route::prefix('posts')->group(function () {
            Route::get('/', [PostController::class, 'index'])
                ->middleware('can_do:edit_posts');
            Route::post('/', [PostController::class, 'store'])
                ->middleware('can_do:edit_posts');
            Route::post('/bulk', [PostController::class, 'bulk'])
                ->middleware('can_do:edit_posts');
            Route::get('/{post}', [PostController::class, 'show'])
                ->middleware('can_do:edit_posts');
            Route::put('/{post}', [PostController::class, 'update'])
                ->middleware('can_do:edit_posts');
            Route::delete('/{post}', [PostController::class, 'destroy'])
                ->middleware('can_do:delete_posts');
            Route::put('/{post}/trash', [PostController::class, 'trash'])
                ->middleware('can_do:delete_posts');
            Route::put('/{post}/restore', [PostController::class, 'restore'])
                ->middleware('can_do:edit_posts');
            Route::get('/{post}/revisions', [PostController::class, 'listRevisions'])
                ->middleware('can_do:edit_posts');
            Route::post('/{post}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])
                ->middleware('can_do:edit_posts');
        });

        // Page Management (Sprint 4) — same controller, different type
        Route::prefix('pages')->group(function () {
            Route::get('/', [PostController::class, 'index'])
                ->middleware('can_do:edit_pages');
            Route::post('/', [PostController::class, 'store'])
                ->middleware('can_do:edit_pages');
            Route::post('/bulk', [PostController::class, 'bulk'])
                ->middleware('can_do:edit_pages');
            Route::get('/{page}', [PostController::class, 'show'])
                ->middleware('can_do:edit_pages');
            Route::put('/{page}', [PostController::class, 'update'])
                ->middleware('can_do:edit_pages');
            Route::delete('/{page}', [PostController::class, 'destroy'])
                ->middleware('can_do:delete_pages');
            Route::put('/{page}/trash', [PostController::class, 'trash'])
                ->middleware('can_do:delete_pages');
            Route::put('/{page}/restore', [PostController::class, 'restore'])
                ->middleware('can_do:edit_pages');
            Route::get('/{page}/revisions', [PostController::class, 'listRevisions'])
                ->middleware('can_do:edit_pages');
            Route::post('/{page}/revisions/{revision}/restore', [PostController::class, 'restoreRevision'])
                ->middleware('can_do:edit_pages');
        });

        // Category Management (Sprint 5)
        Route::prefix('categories')->group(function () {
            Route::get('/', [TaxonomyController::class, 'index'])
                ->middleware('can_do:manage_categories');
            Route::post('/', [TaxonomyController::class, 'store'])
                ->middleware('can_do:manage_categories');
            Route::post('/bulk', [TaxonomyController::class, 'bulk'])
                ->middleware('can_do:manage_categories');
            Route::get('/{id}', [TaxonomyController::class, 'show'])
                ->middleware('can_do:manage_categories');
            Route::put('/{id}', [TaxonomyController::class, 'update'])
                ->middleware('can_do:manage_categories');
            Route::delete('/{id}', [TaxonomyController::class, 'destroy'])
                ->middleware('can_do:manage_categories');
        });

        // Tag Management (Sprint 5)
        Route::prefix('tags')->group(function () {
            Route::get('/', [TaxonomyController::class, 'index'])
                ->middleware('can_do:manage_categories');
            Route::post('/', [TaxonomyController::class, 'store'])
                ->middleware('can_do:manage_categories');
            Route::post('/bulk', [TaxonomyController::class, 'bulk'])
                ->middleware('can_do:manage_categories');
            Route::get('/{id}', [TaxonomyController::class, 'show'])
                ->middleware('can_do:manage_categories');
            Route::put('/{id}', [TaxonomyController::class, 'update'])
                ->middleware('can_do:manage_categories');
            Route::delete('/{id}', [TaxonomyController::class, 'destroy'])
                ->middleware('can_do:manage_categories');
        });

        // Custom Taxonomy Management (Sprint 5) — generic routes
        Route::prefix('taxonomies/{taxonomy}/terms')->group(function () {
            Route::get('/', [TaxonomyController::class, 'index'])
                ->middleware('can_do:manage_categories');
            Route::post('/', [TaxonomyController::class, 'store'])
                ->middleware('can_do:manage_categories');
            Route::post('/bulk', [TaxonomyController::class, 'bulk'])
                ->middleware('can_do:manage_categories');
            Route::get('/{id}', [TaxonomyController::class, 'show'])
                ->middleware('can_do:manage_categories');
            Route::put('/{id}', [TaxonomyController::class, 'update'])
                ->middleware('can_do:manage_categories');
            Route::delete('/{id}', [TaxonomyController::class, 'destroy'])
                ->middleware('can_do:manage_categories');
        });
    });
});

```



Let me also check the TermTaxonomy model to see what modifications were made:

**Command Executed:**
```bash
git --no-pager diff app/Models/TermTaxonomy.php
```

**Output:**
```
diff --git a/app/Models/TermTaxonomy.php b/app/Models/TermTaxonomy.php
index 491d3d2..ec15f65 100644
--- a/app/Models/TermTaxonomy.php
+++ b/app/Models/TermTaxonomy.php
@@ -47,4 +47,20 @@ public function posts(): BelongsToMany
             'object_id',
         )->withPivot('term_order');
     }
+
+    /**
+     * Parent term taxonomy (for hierarchical taxonomies).
+     */
+    public function parentTerm(): BelongsTo
+    {
+        return $this->belongsTo(TermTaxonomy::class, 'parent', 'id');
+    }
+
+    /**
+     * Child term taxonomies (for hierarchical taxonomies).
+     */
+    public function children()
+    {
+        return $this->hasMany(TermTaxonomy::class, 'parent', 'id');
+    }
 }
```

Perfect! Now let me check if there are any tests that need to be created for Sprint 5. Let me look at the test directory structure:

**Command Executed:**
```bash
Test-Path "D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php"
```

**Output:**
```
False
```

Let me check what test files exist:


**File Glob Results:**

- **D:\Meem-Market\tests\Feature\ExampleTest.php**
- **D:\Meem-Market\tests\Unit\ExampleTest.php**


Now let me check if there's an ApiController base class to understand the pattern:


**Files Read:**

**D:\Meem-Market\app\Http\Controllers\Api\V1\Admin\ApiController.php**
```
<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Base controller for all admin API controllers.
 *
 * Provides standardised JSON response helpers so every admin
 * endpoint returns a consistent envelope format:
 *   Success: { success: true, data: mixed, meta?: object }
 *   Error:   { success: false, message: string, errors?: object }
 */
abstract class ApiController extends Controller
{
    /**
     * Return a success response.
     */
    protected function success(mixed $data = null, int $status = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $data,
        ], $status);
    }

    /**
     * Return an error response.
     */
    protected function error(string $message, int $status = 400, ?array $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    /**
     * Return a paginated response using an API Resource.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $resourceClass::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }
}

```



Perfect! Now I have a complete understanding of Sprint 5's current state. All the core files have been created:

✅ **Completed:**
1. TaxonomyController with full CRUD operations
2. Form Requests (StoreTermRequest, UpdateTermRequest, BulkTermRequest)
3. Resources (TermResource, TermCollection)
4. Routes for categories, tags, and custom taxonomies
5. TermTaxonomy model relationships (parentTerm, children)

❌ **Missing:**
1. Tests for the taxonomy endpoints
2. Verification that the implementation works correctly

Let me create comprehensive tests for Sprint 5 and verify the implementation:


**Diff:**
```diff
--- D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php
+++ D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php
@@ -0,0 +1,571 @@
+<?php
+
+namespace Tests\Feature\Admin;
+
+use App\Models\Option;
+use App\Models\Post;
+use App\Models\Term;
+use App\Models\TermRelationship;
+use App\Models\TermTaxonomy;
+use App\Models\User;
+use Illuminate\Foundation\Testing\RefreshDatabase;
+use Laravel\Sanctum\Sanctum;
+use Tests\TestCase;
+
+class TaxonomyTest extends TestCase
+{
+    use RefreshDatabase;
+
+    private User $admin;
+    private User $editor;
+
+    protected function setUp(): void
+    {
+        parent::setUp();
+
+        // Create roles in options table
+        Option::create([
+            'name' => 'user_roles',
+            'value' => json_encode([
+                'administrator' => [
+                    'name' => 'Administrator',
+                    'capabilities' => [
+                        'manage_categories' => true,
+                        'edit_posts' => true,
+                        'delete_posts' => true,
+                    ],
+                ],
+                'editor' => [
+                    'name' => 'Editor',
+                    'capabilities' => [
+                        'manage_categories' => true,
+                        'edit_posts' => true,
+                    ],
+                ],
+            ]),
+            'autoload' => 'yes',
+        ]);
+
+        // Create admin user
+        $this->admin = User::factory()->create([
+            'login' => 'admin',
+            'email' => 'admin@test.com',
+        ]);
+        $this->admin->meta()->create(['meta_key' => 'role', 'meta_value' => 'administrator']);
+
+        // Create editor user
+        $this->editor = User::factory()->create([
+            'login' => 'editor',
+            'email' => 'editor@test.com',
+        ]);
+        $this->editor->meta()->create(['meta_key' => 'role', 'meta_value' => 'editor']);
+
+        // Create default category option
+        Option::create([
+            'name' => 'default_category',
+            'value' => '1',
+            'autoload' => 'yes',
+        ]);
+    }
+
+    // ─── Categories ──────────────────────────────────────────────
+
+    /** @test */
+    public function it_lists_categories_with_pagination()
+    {
+        Sanctum::actingAs($this->admin);
+
+        // Create categories
+        $term1 = Term::create(['name' => 'Technology', 'slug' => 'technology']);
+        TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category', 'count' => 5]);
+
+        $term2 = Term::create(['name' => 'Science', 'slug' => 'science']);
+        TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'category', 'count' => 3]);
+
+        $response = $this->getJson('/api/v1/admin/categories');
+
+        $response->assertOk()
+            ->assertJsonStructure([
+                'data' => [
+                    '*' => ['id', 'term_id', 'name', 'slug', 'taxonomy', 'description', 'parent', 'count'],
+                ],
+                'meta' => ['total', 'per_page', 'current_page', 'last_page', 'taxonomy'],
+            ])
+            ->assertJsonPath('meta.taxonomy', 'category')
+            ->assertJsonCount(2, 'data');
+    }
+
+    /** @test */
+    public function it_searches_categories_by_name()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term1 = Term::create(['name' => 'Technology', 'slug' => 'technology']);
+        TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category']);
+
+        $term2 = Term::create(['name' => 'Science', 'slug' => 'science']);
+        TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'category']);
+
+        $response = $this->getJson('/api/v1/admin/categories?search=Tech');
+
+        $response->assertOk()
+            ->assertJsonCount(1, 'data')
+            ->assertJsonPath('data.0.name', 'Technology');
+    }
+
+    /** @test */
+    public function it_filters_categories_by_parent()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $parentTerm = Term::create(['name' => 'Parent Category', 'slug' => 'parent']);
+        $parentTaxonomy = TermTaxonomy::create(['term_id' => $parentTerm->id, 'taxonomy' => 'category']);
+
+        $childTerm = Term::create(['name' => 'Child Category', 'slug' => 'child']);
+        TermTaxonomy::create(['term_id' => $childTerm->id, 'taxonomy' => 'category', 'parent' => $parentTaxonomy->id]);
+
+        $response = $this->getJson('/api/v1/admin/categories?parent=' . $parentTaxonomy->id);
+
+        $response->assertOk()
+            ->assertJsonCount(1, 'data')
+            ->assertJsonPath('data.0.name', 'Child Category');
+    }
+
+    /** @test */
+    public function it_hides_empty_categories()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term1 = Term::create(['name' => 'Has Posts', 'slug' => 'has-posts']);
+        TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category', 'count' => 5]);
+
+        $term2 = Term::create(['name' => 'Empty', 'slug' => 'empty']);
+        TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'category', 'count' => 0]);
+
+        $response = $this->getJson('/api/v1/admin/categories?hide_empty=1');
+
+        $response->assertOk()
+            ->assertJsonCount(1, 'data')
+            ->assertJsonPath('data.0.name', 'Has Posts');
+    }
+
+    /** @test */
+    public function it_creates_a_category_with_auto_generated_slug()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'New Technology',
+            'description' => 'Tech articles',
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.name', 'New Technology')
+            ->assertJsonPath('data.slug', 'new-technology')
+            ->assertJsonPath('data.taxonomy', 'category');
+
+        $this->assertDatabaseHas('terms', [
+            'name' => 'New Technology',
+            'slug' => 'new-technology',
+        ]);
+
+        $this->assertDatabaseHas('term_taxonomy', [
+            'taxonomy' => 'category',
+            'description' => 'Tech articles',
+        ]);
+    }
+
+    /** @test */
+    public function it_creates_a_category_with_custom_slug()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'Technology',
+            'slug' => 'tech',
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.slug', 'tech');
+    }
+
+    /** @test */
+    public function it_creates_a_category_with_parent()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $parentTerm = Term::create(['name' => 'Parent', 'slug' => 'parent']);
+        $parentTaxonomy = TermTaxonomy::create(['term_id' => $parentTerm->id, 'taxonomy' => 'category']);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'Child',
+            'parent_id' => $parentTaxonomy->id,
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.parent', $parentTaxonomy->id)
+            ->assertJsonPath('data.parent_term.name', 'Parent');
+    }
+
+    /** @test */
+    public function it_generates_unique_slug_on_collision()
+    {
+        Sanctum::actingAs($this->admin);
+
+        // Create first category
+        $term1 = Term::create(['name' => 'Technology', 'slug' => 'technology']);
+        TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category']);
+
+        // Create second with same name
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'Technology',
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.slug', 'technology-2');
+    }
+
+    /** @test */
+    public function it_shows_a_single_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Technology', 'slug' => 'technology']);
+        $taxonomy = TermTaxonomy::create([
+            'term_id' => $term->id,
+            'taxonomy' => 'category',
+            'description' => 'Tech posts',
+            'count' => 10,
+        ]);
+
+        $response = $this->getJson('/api/v1/admin/categories/' . $taxonomy->id);
+
+        $response->assertOk()
+            ->assertJsonPath('data.name', 'Technology')
+            ->assertJsonPath('data.description', 'Tech posts')
+            ->assertJsonPath('data.count', 10);
+    }
+
+    /** @test */
+    public function it_returns_404_for_nonexistent_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->getJson('/api/v1/admin/categories/999');
+
+        $response->assertNotFound()
+            ->assertJsonPath('message', 'Term not found.');
+    }
+
+    /** @test */
+    public function it_updates_a_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Old Name', 'slug' => 'old-name']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'category']);
+
+        $response = $this->putJson('/api/v1/admin/categories/' . $taxonomy->id, [
+            'name' => 'New Name',
+            'slug' => 'new-name',
+            'description' => 'Updated description',
+        ]);
+
+        $response->assertOk()
+            ->assertJsonPath('data.name', 'New Name')
+            ->assertJsonPath('data.slug', 'new-name')
+            ->assertJsonPath('data.description', 'Updated description');
+
+        $this->assertDatabaseHas('terms', ['slug' => 'new-name']);
+        $this->assertDatabaseHas('term_taxonomy', ['description' => 'Updated description']);
+    }
+
+    /** @test */
+    public function it_prevents_circular_parent_relationship()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Category', 'slug' => 'category']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'category']);
+
+        $response = $this->putJson('/api/v1/admin/categories/' . $taxonomy->id, [
+            'parent_id' => $taxonomy->id,
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonPath('message', 'A term cannot be its own parent.');
+    }
+
+    /** @test */
+    public function it_deletes_a_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'To Delete', 'slug' => 'to-delete']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'category']);
+
+        // Create a post relationship
+        $post = Post::factory()->create();
+        TermRelationship::create([
+            'object_id' => $post->id,
+            'term_taxonomy_id' => $taxonomy->id,
+        ]);
+
+        $response = $this->deleteJson('/api/v1/admin/categories/' . $taxonomy->id);
+
+        $response->assertOk()
+            ->assertJsonPath('data.message', 'Term deleted successfully.');
+
+        $this->assertDatabaseMissing('term_taxonomy', ['id' => $taxonomy->id]);
+        $this->assertDatabaseMissing('terms', ['id' => $term->id]);
+        $this->assertDatabaseMissing('term_relationships', ['term_taxonomy_id' => $taxonomy->id]);
+    }
+
+    /** @test */
+    public function it_prevents_deleting_default_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Default', 'slug' => 'default']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'category']);
+
+        // Set as default category
+        Option::where('name', 'default_category')->update(['value' => (string)$taxonomy->id]);
+
+        $response = $this->deleteJson('/api/v1/admin/categories/' . $taxonomy->id);
+
+        $response->assertStatus(422)
+            ->assertJsonPath('message', 'Cannot delete the default category.');
+    }
+
+    /** @test */
+    public function it_performs_bulk_delete_on_categories()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term1 = Term::create(['name' => 'Cat 1', 'slug' => 'cat-1']);
+        $taxonomy1 = TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category']);
+
+        $term2 = Term::create(['name' => 'Cat 2', 'slug' => 'cat-2']);
+        $taxonomy2 = TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'category']);
+
+        $response = $this->postJson('/api/v1/admin/categories/bulk', [
+            'action' => 'delete',
+            'term_ids' => [$taxonomy1->id, $taxonomy2->id],
+        ]);
+
+        $response->assertOk()
+            ->assertJsonPath('data.affected', 2);
+
+        $this->assertDatabaseMissing('term_taxonomy', ['id' => $taxonomy1->id]);
+        $this->assertDatabaseMissing('term_taxonomy', ['id' => $taxonomy2->id]);
+    }
+
+    /** @test */
+    public function it_prevents_bulk_delete_of_default_category()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term1 = Term::create(['name' => 'Default', 'slug' => 'default']);
+        $taxonomy1 = TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'category']);
+
+        Option::where('name', 'default_category')->update(['value' => (string)$taxonomy1->id]);
+
+        $term2 = Term::create(['name' => 'Other', 'slug' => 'other']);
+        $taxonomy2 = TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'category']);
+
+        $response = $this->postJson('/api/v1/admin/categories/bulk', [
+            'action' => 'delete',
+            'term_ids' => [$taxonomy1->id, $taxonomy2->id],
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonPath('message', 'Cannot delete the default category.');
+    }
+
+    // ─── Tags ────────────────────────────────────────────────────
+
+    /** @test */
+    public function it_lists_tags()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term1 = Term::create(['name' => 'PHP', 'slug' => 'php']);
+        TermTaxonomy::create(['term_id' => $term1->id, 'taxonomy' => 'post_tag']);
+
+        $term2 = Term::create(['name' => 'Laravel', 'slug' => 'laravel']);
+        TermTaxonomy::create(['term_id' => $term2->id, 'taxonomy' => 'post_tag']);
+
+        $response = $this->getJson('/api/v1/admin/tags');
+
+        $response->assertOk()
+            ->assertJsonPath('meta.taxonomy', 'post_tag')
+            ->assertJsonCount(2, 'data');
+    }
+
+    /** @test */
+    public function it_creates_a_tag()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/tags', [
+            'name' => 'PHP',
+            'description' => 'PHP programming',
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.name', 'PHP')
+            ->assertJsonPath('data.taxonomy', 'post_tag');
+    }
+
+    /** @test */
+    public function it_updates_a_tag()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Old Tag', 'slug' => 'old-tag']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'post_tag']);
+
+        $response = $this->putJson('/api/v1/admin/tags/' . $taxonomy->id, [
+            'name' => 'New Tag',
+        ]);
+
+        $response->assertOk()
+            ->assertJsonPath('data.name', 'New Tag');
+    }
+
+    /** @test */
+    public function it_deletes_a_tag()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Tag', 'slug' => 'tag']);
+        $taxonomy = TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'post_tag']);
+
+        $response = $this->deleteJson('/api/v1/admin/tags/' . $taxonomy->id);
+
+        $response->assertOk();
+        $this->assertDatabaseMissing('term_taxonomy', ['id' => $taxonomy->id]);
+    }
+
+    // ─── Custom Taxonomies ───────────────────────────────────────
+
+    /** @test */
+    public function it_handles_custom_taxonomy_via_generic_routes()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $term = Term::create(['name' => 'Action', 'slug' => 'action']);
+        TermTaxonomy::create(['term_id' => $term->id, 'taxonomy' => 'movie_genre']);
+
+        $response = $this->getJson('/api/v1/admin/taxonomies/movie_genre/terms');
+
+        $response->assertOk()
+            ->assertJsonPath('meta.taxonomy', 'movie_genre')
+            ->assertJsonCount(1, 'data');
+    }
+
+    /** @test */
+    public function it_creates_custom_taxonomy_term()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/taxonomies/book_genre/terms', [
+            'name' => 'Fiction',
+        ]);
+
+        $response->assertCreated()
+            ->assertJsonPath('data.taxonomy', 'book_genre');
+    }
+
+    // ─── Authorization ───────────────────────────────────────────
+
+    /** @test */
+    public function it_requires_authentication()
+    {
+        $response = $this->getJson('/api/v1/admin/categories');
+        $response->assertUnauthorized();
+    }
+
+    /** @test */
+    public function it_requires_manage_categories_capability()
+    {
+        $user = User::factory()->create();
+        $user->meta()->create(['meta_key' => 'role', 'meta_value' => 'subscriber']);
+
+        Sanctum::actingAs($user);
+
+        $response = $this->getJson('/api/v1/admin/categories');
+        $response->assertForbidden();
+    }
+
+    // ─── Validation ──────────────────────────────────────────────
+
+    /** @test */
+    public function it_validates_category_name_is_required()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'description' => 'No name',
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonValidationErrors(['name']);
+    }
+
+    /** @test */
+    public function it_validates_slug_format()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'Test',
+            'slug' => 'Invalid Slug!',
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonValidationErrors(['slug']);
+    }
+
+    /** @test */
+    public function it_validates_parent_exists()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories', [
+            'name' => 'Test',
+            'parent_id' => 99999,
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonValidationErrors(['parent_id']);
+    }
+
+    /** @test */
+    public function it_validates_bulk_action()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories/bulk', [
+            'action' => 'invalid_action',
+            'term_ids' => [1, 2],
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonValidationErrors(['action']);
+    }
+
+    /** @test */
+    public function it_validates_bulk_term_ids_required()
+    {
+        Sanctum::actingAs($this->admin);
+
+        $response = $this->postJson('/api/v1/admin/categories/bulk', [
+            'action' => 'delete',
+        ]);
+
+        $response->assertStatus(422)
+            ->assertJsonValidationErrors(['term_ids']);
+    }
+}

```



Excellent! Now let me run the tests to verify Sprint 5 is working correctly:

```bash
php artisan test --filter=TaxonomyTest
```

**Current Output:**
```

<|cursor|>
```

Subagent: 2414fe1b-4671-4f35-9d40-5c6188f8f37f

The tests reveal authorization issues. Let me check the middleware configuration to understand the issue:


**Files Read:**

**D:\Meem-Market\app\Http\Middleware\CheckCapability.php**
```
<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameterised middleware that checks if the authenticated user
 * has a specific WordPress-style capability.
 *
 * Usage in routes: ->middleware('can_do:manage_options')
 */
class CheckCapability
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$this->roleService->userCan($user, $capability)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}

```



Now let me check the RoleService to understand how it checks capabilities:


**Files Read:**

**D:\Meem-Market\app\Services\RoleService.php**
```
<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserMeta;

/**
 * WP-style role and capability management.
 *
 * Roles and capabilities are stored in the `options` table under the key
 * `user_roles` as a JSON map: { "administrator": { "name": "...", "capabilities": {...} }, ... }
 *
 * Each user's assigned role is stored in `user_meta` under the key
 * `wp_capabilities` as a JSON object: { "administrator": true }
 */
class RoleService
{
    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    /**
     * Get all defined roles from the options table.
     *
     * @return array<string, array{name: string, capabilities: array<string, bool>}>
     */
    public function getRoles(): array
    {
        $rolesJson = $this->optionService->get('user_roles', '{}');

        return json_decode($rolesJson, true) ?: [];
    }

    /**
     * Get a single role definition by slug.
     *
     * @return array{name: string, capabilities: array<string, bool>}|null
     */
    public function getRole(string $name): ?array
    {
        return $this->getRoles()[$name] ?? null;
    }

    /**
     * Check if a user has a specific capability through their assigned role.
     */
    public function userCan(User $user, string $capability): bool
    {
        $roleName = $this->getUserRole($user);

        if (!$roleName) {
            return false;
        }

        $role = $this->getRole($roleName);

        if (!$role) {
            return false;
        }

        return !empty($role['capabilities'][$capability]);
    }

    /**
     * Get the user's current role slug from user_meta.
     */
    public function getUserRole(User $user): ?string
    {
        $meta = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'wp_capabilities')
            ->first();

        if (!$meta?->meta_value) {
            return null;
        }

        $capabilities = json_decode($meta->meta_value, true);

        if (!is_array($capabilities)) {
            return null;
        }

        // WP stores roles as { "administrator": true } — we return the first key
        return array_key_first($capabilities);
    }

    /**
     * Get all capabilities for a user based on their role.
     *
     * @return array<string, bool>
     */
    public function getUserCapabilities(User $user): array
    {
        $roleName = $this->getUserRole($user);

        if (!$roleName) {
            return [];
        }

        $role = $this->getRole($roleName);

        return $role['capabilities'] ?? [];
    }

    /**
     * Set the user's role by writing to user_meta.
     */
    public function setUserRole(User $user, string $role): void
    {
        // Validate the role exists
        if (!$this->getRole($role)) {
            throw new \InvalidArgumentException("Role '{$role}' does not exist.");
        }

        UserMeta::updateOrCreate(
            ['user_id' => $user->id, 'meta_key' => 'wp_capabilities'],
            ['meta_value' => json_encode([$role => true])],
        );
    }
}

```



I see the issue! The test is storing the role with meta_key `'role'` but the RoleService expects `'wp_capabilities'` as a JSON object. Let me fix the tests:


**Diff:**
```diff
--- \\?\D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php
+++ \\?\D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php
@@ -51,14 +51,20 @@
             'login' => 'admin',
             'email' => 'admin@test.com',
         ]);
-        $this->admin->meta()->create(['meta_key' => 'role', 'meta_value' => 'administrator']);
+        $this->admin->meta()->create([
+            'meta_key' => 'wp_capabilities',
+            'meta_value' => json_encode(['administrator' => true]),
+        ]);
 
         // Create editor user
         $this->editor = User::factory()->create([
             'login' => 'editor',
             'email' => 'editor@test.com',
         ]);
-        $this->editor->meta()->create(['meta_key' => 'role', 'meta_value' => 'editor']);
+        $this->editor->meta()->create([
+            'meta_key' => 'wp_capabilities',
+            'meta_value' => json_encode(['editor' => true]),
+        ]);
 
         // Create default category option
         Option::create([
@@ -491,7 +497,10 @@
     public function it_requires_manage_categories_capability()
     {
         $user = User::factory()->create();
-        $user->meta()->create(['meta_key' => 'role', 'meta_value' => 'subscriber']);
+        $user->meta()->create([
+            'meta_key' => 'wp_capabilities',
+            'meta_value' => json_encode(['subscriber' => true]),
+        ]);
 
         Sanctum::actingAs($user);
 

```



Now let me also check if the Post factory exists and create it if it doesn't:

**Command Executed:**
```bash
Test-Path "D:\Meem-Market\database\factories\PostFactory.php"
```

**Output:**
```
False
```

Let me check the Post model to understand its structure:


**Files Read:**

**D:\Meem-Market\app\Models\Post.php**
```
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'author_id',
        'post_date',
        'post_date_gmt',
        'content',
        'title',
        'excerpt',
        'status',
        'comment_status',
        'ping_status',
        'password',
        'slug',
        'post_modified',
        'post_modified_gmt',
        'content_filtered',
        'parent_id',
        'guid',
        'menu_order',
        'type',
        'mime_type',
        'comment_count',
    ];

    protected function casts(): array
    {
        return [
            'post_date' => 'datetime',
            'post_date_gmt' => 'datetime',
            'post_modified' => 'datetime',
            'post_modified_gmt' => 'datetime',
            'parent_id' => 'integer',
            'menu_order' => 'integer',
            'comment_count' => 'integer',
        ];
    }

    // ─── Relationships ───────────────────────────────────────────

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function meta(): HasMany
    {
        return $this->hasMany(PostMeta::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Many-to-many through term_relationships → term_taxonomy.
     * Enables: $post->taxonomies (returns TermTaxonomy pivots).
     */
    public function taxonomies(): BelongsToMany
    {
        return $this->belongsToMany(
            TermTaxonomy::class,
            'term_relationships',
            'object_id',
            'term_taxonomy_id',
        )->withPivot('term_order');
    }

    // ─── Scopes ──────────────────────────────────────────────────

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    public function scopeOfStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'publish');
    }

    public function scopeNotRevision(Builder $query): Builder
    {
        return $query->where('type', '!=', 'revision');
    }

    public function scopeTrash(Builder $query): Builder
    {
        return $query->where('status', 'trash');
    }

    // ─── Additional Relationships ───────────────────────────────

    /**
     * Featured image (attachment post) stored as _thumbnail_id in post_meta.
     */
    public function featuredImage(): BelongsTo
    {
        $thumbnailMeta = $this->meta()
            ->where('meta_key', '_thumbnail_id')
            ->first();

        $imageId = $thumbnailMeta?->meta_value;

        return $this->belongsTo(self::class, 'parent_id')
            ->where('id', $imageId ?? 0);
    }

    /**
     * Revisions — child posts of type 'revision'.
     */
    public function revisions(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('type', 'revision')
            ->orderByDesc('post_modified');
    }
}

```



Now let me create a PostFactory:


**Diff:**
```diff
--- D:\Meem-Market\database\factories\PostFactory.php
+++ D:\Meem-Market\database\factories\PostFactory.php
@@ -0,0 +1,85 @@
+<?php
+
+namespace Database\Factories;
+
+use App\Models\Post;
+use App\Models\User;
+use Illuminate\Database\Eloquent\Factories\Factory;
+use Illuminate\Support\Str;
+
+/**
+ * @extends Factory<Post>
+ */
+class PostFactory extends Factory
+{
+    protected $model = Post::class;
+
+    public function definition(): array
+    {
+        $title = fake()->sentence();
+        $now = now();
+
+        return [
+            'author_id' => User::factory(),
+            'post_date' => $now,
+            'post_date_gmt' => $now,
+            'content' => fake()->paragraphs(3, true),
+            'title' => $title,
+            'excerpt' => fake()->sentence(),
+            'status' => 'publish',
+            'comment_status' => 'open',
+            'ping_status' => 'open',
+            'password' => '',
+            'slug' => Str::slug($title),
+            'post_modified' => $now,
+            'post_modified_gmt' => $now,
+            'content_filtered' => '',
+            'parent_id' => 0,
+            'guid' => '',
+            'menu_order' => 0,
+            'type' => 'post',
+            'mime_type' => '',
+            'comment_count' => 0,
+        ];
+    }
+
+    /**
+     * Create a page instead of a post.
+     */
+    public function page(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'type' => 'page',
+        ]);
+    }
+
+    /**
+     * Create a draft post.
+     */
+    public function draft(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'status' => 'draft',
+        ]);
+    }
+
+    /**
+     * Create a trashed post.
+     */
+    public function trashed(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'status' => 'trash',
+        ]);
+    }
+
+    /**
+     * Create a revision.
+     */
+    public function revision(): static
+    {
+        return $this->state(fn (array $attributes) => [
+            'type' => 'revision',
+        ]);
+    }
+}

```



Now let me also add HasFactory trait to the Post model:


**Diff:**
```diff
--- \\?\D:\Meem-Market\app\Models\Post.php
+++ \\?\D:\Meem-Market\app\Models\Post.php
@@ -3,6 +3,7 @@
 namespace App\Models;
 
 use Illuminate\Database\Eloquent\Builder;
+use Illuminate\Database\Eloquent\Factories\HasFactory;
 use Illuminate\Database\Eloquent\Model;
 use Illuminate\Database\Eloquent\Relations\BelongsTo;
 use Illuminate\Database\Eloquent\Relations\BelongsToMany;
@@ -10,6 +11,7 @@
 
 class Post extends Model
 {
+    use HasFactory;
     public $timestamps = false;
 
     protected $fillable = [

```



Now let's run the tests again:

```bash
php artisan test --filter=TaxonomyTest
```

**Current Output:**
```

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_lists_categories_with_pagination(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_searches_categories_by_name(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_filters_categories_by_parent(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_hides_empty_categories(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_creates_a_category_with_auto_generated_slug(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_creates_a_category_with_custom_slug(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_creates_a_category_with_parent(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_generates_unique_slug_on_collision(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_shows_a_single_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_returns_404_for_nonexistent_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_updates_a_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_prevents_circular_parent_relationship(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_deletes_a_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_prevents_deleting_default_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_performs_bulk_delete_on_categories(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_prevents_bulk_delete_of_default_category(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_lists_tags(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_creates_a_tag(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_updates_a_tag(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_deletes_a_tag(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_handles_custom_taxonomy_via_generic_routes(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_creates_custom_taxonomy_term(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_requires_authentication(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_requires_manage_categories_capability(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_validates_category_name_is_required(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_validates_slug_format(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_validates_parent_exists(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_validates_bulk_action(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.

   WARN  Metadata found in doc-comment for method Tests\Feature\Admin\TaxonomyTest::it_validates_bulk_term_ids_required(). Metadata in doc-comments is deprecated and will no longer be supported in PHPUnit 12. Update your test code to use attributes instead.
<|cursor|>
```

Subagent: fb738ced-7e8b-424b-9f53-409eefa80325

USER:
skip the tests and remove the test file. analyze the public endpoints, do they need change based on this sprint ?

AGENT:
Let me remove the test file and analyze the public endpoints:

**Command Executed:**
```bash
Remove-Item "D:\Meem-Market\tests\Feature\Admin\TaxonomyTest.php" -Force
```

**Output:**
```

```

Now let me analyze the public endpoints to see if they need changes based on Sprint 5. Let me check the ContentController that handles public blog/page routes:


**Files Read:**

**D:\Meem-Market\app\Http\Controllers\Api\V1\ContentController.php**
```
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Public read-only endpoints for blog posts and pages.
 * Only serves published content — no auth required.
 */
#[OA\Tag(name: "Blog", description: "Public blog posts")]
#[OA\Tag(name: "Pages", description: "Public pages")]
class ContentController extends Controller
{
    private const EAGER_LOADS = ['author', 'meta', 'taxonomies.term'];

    // ─── Blog List ───────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/blog",
        operationId: "getPublicBlogPosts",
        summary: "List published blog posts",
        description: "Returns a paginated list of published blog posts. Supports filtering by category slug, tag slug, author, search, and month.",
        tags: ["Blog"],
        parameters: [
            new OA\Parameter(name: "category", in: "query", required: false, description: "Filter by category slug", schema: new OA\Schema(type: "string", example: "news")),
            new OA\Parameter(name: "tag", in: "query", required: false, description: "Filter by tag slug", schema: new OA\Schema(type: "string", example: "featured")),
            new OA\Parameter(name: "author", in: "query", required: false, description: "Filter by author ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "month", in: "query", required: false, description: "Filter by month (YYYY-MM)", schema: new OA\Schema(type: "string", example: "2026-02")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated blog posts", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/PublicPost")),
            ])),
        ]
    )]
    public function blogIndex(Request $request): JsonResponse
    {
        $query = Post::query()
            ->with(self::EAGER_LOADS)
            ->ofType('post')
            ->published()
            ->notRevision()
            ->orderByDesc('post_date');

        // Category filter (by slug)
        if ($categorySlug = $request->query('category')) {
            $query->whereHas('taxonomies', function ($q) use ($categorySlug) {
                $q->where('taxonomy', 'category')
                    ->whereHas('term', fn($t) => $t->where('slug', $categorySlug));
            });
        }

        // Tag filter (by slug)
        if ($tagSlug = $request->query('tag')) {
            $query->whereHas('taxonomies', function ($q) use ($tagSlug) {
                $q->where('taxonomy', 'post_tag')
                    ->whereHas('term', fn($t) => $t->where('slug', $tagSlug));
            });
        }

        // Author filter
        if ($authorId = $request->query('author')) {
            $query->where('author_id', $authorId);
        }

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        // Month filter (YYYY-MM)
        if ($month = $request->query('month')) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $query->whereYear('post_date', $parts[0])
                    ->whereMonth('post_date', $parts[1]);
            }
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

        return response()->json(
            PublicPostResource::collection($query->paginate($perPage))
        );
    }

    // ─── Blog Single ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/blog/{slug}",
        operationId: "getPublicBlogPost",
        summary: "Get a published blog post by slug",
        description: "Returns a single published blog post with full content, categories, tags, and author.",
        tags: ["Blog"],
        parameters: [
            new OA\Parameter(name: "slug", in: "path", required: true, description: "Post slug", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Blog post details", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", ref: "#/components/schemas/PublicPost"),
            ])),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function blogShow(string $slug): JsonResponse
    {
        $post = Post::with(self::EAGER_LOADS)
            ->ofType('post')
            ->published()
            ->notRevision()
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        return response()->json([
            'data' => new PublicPostResource($post),
        ]);
    }

    // ─── Page Single ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/pages/{slug}",
        operationId: "getPublicPage",
        summary: "Get a published page by slug",
        description: "Returns a single published page with full content and author. Pages don't have categories or tags.",
        tags: ["Pages"],
        parameters: [
            new OA\Parameter(name: "slug", in: "path", required: true, description: "Page slug", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Page details", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", ref: "#/components/schemas/PublicPost"),
            ])),
            new OA\Response(response: 404, description: "Page not found"),
        ]
    )]
    public function pageShow(string $slug): JsonResponse
    {
        $page = Post::with(self::EAGER_LOADS)
            ->ofType('page')
            ->published()
            ->notRevision()
            ->where('slug', $slug)
            ->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return response()->json([
            'data' => new PublicPostResource($page),
        ]);
    }
}

```



Now let me check the PublicPostResource to see how it handles taxonomies:


**Files Read:**

**D:\Meem-Market\app\Http\Resources\V1\PublicPostResource.php**
```
<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Public-facing post representation — stripped down for visitors.
 * No admin-only fields (password, comment_status, menu_order etc.).
 */
#[OA\Schema(
    schema: "PublicPost",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 42),
        new OA\Property(property: "title", type: "string", example: "Hello World"),
        new OA\Property(property: "slug", type: "string", example: "hello-world"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "excerpt", type: "string"),
        new OA\Property(property: "author", type: "object", properties: [
            new OA\Property(property: "id", type: "integer"),
            new OA\Property(property: "display_name", type: "string"),
        ]),
        new OA\Property(property: "categories", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "name", type: "string"),
            new OA\Property(property: "slug", type: "string"),
        ])),
        new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "object", properties: [
            new OA\Property(property: "name", type: "string"),
            new OA\Property(property: "slug", type: "string"),
        ])),
        new OA\Property(property: "featured_image_id", type: "integer", nullable: true),
        new OA\Property(property: "published_at", type: "string", format: "date-time"),
        new OA\Property(property: "comment_count", type: "integer"),
    ]
)]
class PublicPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        $categories = $this->whenLoaded('taxonomies', function () {
            return $this->taxonomies
                ->where('taxonomy', 'category')
                ->map(fn($tt) => [
                    'name' => $tt->term->name ?? '',
                    'slug' => $tt->term->slug ?? '',
                ]);
        }, []);

        $tags = $this->whenLoaded('taxonomies', function () {
            return $this->taxonomies
                ->where('taxonomy', 'post_tag')
                ->map(fn($tt) => [
                    'name' => $tt->term->name ?? '',
                    'slug' => $tt->term->slug ?? '',
                ]);
        }, []);

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $this->whenLoaded('author', fn() => [
                'id' => $this->author->id,
                'display_name' => $this->author->display_name ?? $this->author->name,
            ]),
            'categories' => collect($categories)->values(),
            'tags' => collect($tags)->values(),
            'featured_image_id' => $metaMap['_thumbnail_id'] ?? null,
            'published_at' => $this->post_date?->toIso8601String(),
            'comment_count' => $this->comment_count ?? 0,
        ];
    }
}

```



Perfect! Now let me analyze the situation. The public endpoints are **already integrated with the taxonomy system** from Sprint 5. Let me verify the routes are properly set up and summarize what's done:

**Command Executed:**
```bash
php artisan route:list --path=api/v1 --columns=method,uri,name,action
```

**Output:**
```

                                          
  The "--columns" option does not exist.  
                                          

```


**Command Executed:**
```bash
php artisan route:list --path=api/v1
```

**Output:**
```

  GET|HEAD  api/v1/about ....................................................................................................... Api\V1\AboutController@index
  POST      api/v1/admin/auth/forgot-password .................................................................... Api\V1\Admin\AuthController@forgotPassword
  POST      api/v1/admin/auth/login ....................................................................................... Api\V1\Admin\AuthController@login
  POST      api/v1/admin/auth/logout ..................................................................................... Api\V1\Admin\AuthController@logout
  GET|HEAD  api/v1/admin/auth/me ............................................................................................. Api\V1\Admin\AuthController@me
  PUT       api/v1/admin/auth/me .................................................................................. Api\V1\Admin\AuthController@updateProfile
  POST      api/v1/admin/auth/register ................................................................................. Api\V1\Admin\AuthController@register
  POST      api/v1/admin/auth/reset-password ...................................................................... Api\V1\Admin\AuthController@resetPassword
  GET|HEAD  api/v1/admin/categories ................................................................................... Api\V1\Admin\TaxonomyController@index
  POST      api/v1/admin/categories ................................................................................... Api\V1\Admin\TaxonomyController@store
  POST      api/v1/admin/categories/bulk ............................................................................... Api\V1\Admin\TaxonomyController@bulk
  GET|HEAD  api/v1/admin/categories/{id} ............................................................................... Api\V1\Admin\TaxonomyController@show
  PUT       api/v1/admin/categories/{id} ............................................................................. Api\V1\Admin\TaxonomyController@update
  DELETE    api/v1/admin/categories/{id} ............................................................................ Api\V1\Admin\TaxonomyController@destroy
  POST      api/v1/admin/dashboard/quick-draft .................................................................. Api\V1\Admin\DashboardController@quickDraft
  GET|HEAD  api/v1/admin/dashboard/stats ............................................................................. Api\V1\Admin\DashboardController@stats
  GET|HEAD  api/v1/admin/pages ............................................................................................ Api\V1\Admin\PostController@index
  POST      api/v1/admin/pages ............................................................................................ Api\V1\Admin\PostController@store
  POST      api/v1/admin/pages/bulk ........................................................................................ Api\V1\Admin\PostController@bulk
  GET|HEAD  api/v1/admin/pages/{page} ...................................................................................... Api\V1\Admin\PostController@show
  PUT       api/v1/admin/pages/{page} .................................................................................... Api\V1\Admin\PostController@update
  DELETE    api/v1/admin/pages/{page} ................................................................................... Api\V1\Admin\PostController@destroy
  PUT       api/v1/admin/pages/{page}/restore ........................................................................... Api\V1\Admin\PostController@restore
  GET|HEAD  api/v1/admin/pages/{page}/revisions ................................................................... Api\V1\Admin\PostController@listRevisions
  POST      api/v1/admin/pages/{page}/revisions/{revision}/restore .............................................. Api\V1\Admin\PostController@restoreRevision
  PUT       api/v1/admin/pages/{page}/trash ............................................................................... Api\V1\Admin\PostController@trash
  GET|HEAD  api/v1/admin/posts ............................................................................................ Api\V1\Admin\PostController@index
  POST      api/v1/admin/posts ............................................................................................ Api\V1\Admin\PostController@store
  POST      api/v1/admin/posts/bulk ........................................................................................ Api\V1\Admin\PostController@bulk
  GET|HEAD  api/v1/admin/posts/{post} ...................................................................................... Api\V1\Admin\PostController@show
  PUT       api/v1/admin/posts/{post} .................................................................................... Api\V1\Admin\PostController@update
  DELETE    api/v1/admin/posts/{post} ................................................................................... Api\V1\Admin\PostController@destroy
  PUT       api/v1/admin/posts/{post}/restore ........................................................................... Api\V1\Admin\PostController@restore
  GET|HEAD  api/v1/admin/posts/{post}/revisions ................................................................... Api\V1\Admin\PostController@listRevisions
  POST      api/v1/admin/posts/{post}/revisions/{revision}/restore .............................................. Api\V1\Admin\PostController@restoreRevision
  PUT       api/v1/admin/posts/{post}/trash ............................................................................... Api\V1\Admin\PostController@trash
  GET|HEAD  api/v1/admin/tags ......................................................................................... Api\V1\Admin\TaxonomyController@index
  POST      api/v1/admin/tags ......................................................................................... Api\V1\Admin\TaxonomyController@store
  POST      api/v1/admin/tags/bulk ..................................................................................... Api\V1\Admin\TaxonomyController@bulk
  GET|HEAD  api/v1/admin/tags/{id} ..................................................................................... Api\V1\Admin\TaxonomyController@show
  PUT       api/v1/admin/tags/{id} ................................................................................... Api\V1\Admin\TaxonomyController@update
  DELETE    api/v1/admin/tags/{id} .................................................................................. Api\V1\Admin\TaxonomyController@destroy
  GET|HEAD  api/v1/admin/taxonomies/{taxonomy}/terms .................................................................. Api\V1\Admin\TaxonomyController@index
  POST      api/v1/admin/taxonomies/{taxonomy}/terms .................................................................. Api\V1\Admin\TaxonomyController@store
  POST      api/v1/admin/taxonomies/{taxonomy}/terms/bulk .............................................................. Api\V1\Admin\TaxonomyController@bulk
  GET|HEAD  api/v1/admin/taxonomies/{taxonomy}/terms/{id} .............................................................. Api\V1\Admin\TaxonomyController@show
  PUT       api/v1/admin/taxonomies/{taxonomy}/terms/{id} ............................................................ Api\V1\Admin\TaxonomyController@update
  DELETE    api/v1/admin/taxonomies/{taxonomy}/terms/{id} ........................................................... Api\V1\Admin\TaxonomyController@destroy
  GET|HEAD  api/v1/admin/users ............................................................................................ Api\V1\Admin\UserController@index
  POST      api/v1/admin/users ............................................................................................ Api\V1\Admin\UserController@store
  POST      api/v1/admin/users/bulk ........................................................................................ Api\V1\Admin\UserController@bulk
  GET|HEAD  api/v1/admin/users/{user} ...................................................................................... Api\V1\Admin\UserController@show
  PUT       api/v1/admin/users/{user} .................................................................................... Api\V1\Admin\UserController@update
  DELETE    api/v1/admin/users/{user} ................................................................................... Api\V1\Admin\UserController@destroy
  GET|HEAD  api/v1/blog .................................................................................................. Api\V1\ContentController@blogIndex
  GET|HEAD  api/v1/blog/{slug} ............................................................................................ Api\V1\ContentController@blogShow
  GET|HEAD  api/v1/branches ................................................................................................... Api\V1\BranchController@index
  GET|HEAD  api/v1/branches/{slug} ............................................................................................. Api\V1\BranchController@show
  GET|HEAD  api/v1/careers .................................................................................................... Api\V1\CareerController@index
  GET|HEAD  api/v1/careers/{slug} .............................................................................................. Api\V1\CareerController@show
  GET|HEAD  api/v1/contact ................................................................................................... Api\V1\ContactController@index
  POST      api/v1/contact ................................................................................................... Api\V1\ContactController@store
  GET|HEAD  api/v1/countries ................................................................................................. Api\V1\CountryController@index
  GET|HEAD  api/v1/home ......................................................................................................... Api\V1\HomeController@index
  GET|HEAD  api/v1/offers ...................................................................................................... Api\V1\OfferController@index
  GET|HEAD  api/v1/pages/{slug} ........................................................................................... Api\V1\ContentController@pageShow
  GET|HEAD  api/v1/settings/{group} ........................................................................................... Api\V1\SettingController@show

                                                                                                                                          Showing [67] routes

```

Perfect! Let me now create a summary document of what's been completed in Sprint 5. Let me also check if there are any remaining Sprint 5 requirements by reviewing the implementation plan again:

Based on my analysis, **Sprint 5 is complete** and the public endpoints are already properly integrated. Here's what was accomplished:

## Sprint 5 Completion Summary

### ✅ **Completed Components:**

1. **TaxonomyController** - Full CRUD API for categories, tags, and custom taxonomies
   - List with pagination, search, filtering (by parent, hide_empty)
   - Create with auto-generated unique slugs
   - Read single term
   - Update (prevents circular parent relationships)
   - Delete (prevents deleting default category, cascades relationships)
   - Bulk delete operations
   - Generic routes for custom taxonomies

2. **Form Requests:**
   - `StoreTermRequest` - Validates term creation
   - `UpdateTermRequest` - Validates term updates
   - `BulkTermRequest` - Validates bulk operations

3. **Resources:**
   - `TermResource` - Full term details with parent info
   - `TermCollection` - Paginated collection with taxonomy metadata

4. **Model Enhancements:**
   - Added `parentTerm()` and `children()` relationships to `TermTaxonomy`

5. **Routes:**
   - `/api/v1/admin/categories/*` - Category management
   - `/api/v1/admin/tags/*` - Tag management
   - `/api/v1/admin/taxonomies/{taxonomy}/terms/*` - Custom taxonomy management

### ✅ **Public Endpoints Integration:**

The public endpoints **already properly integrate with Sprint 5 taxonomies**:

- **`GET /api/v1/blog`** - Filters by category/tag slug, returns posts with categories and tags
- **`GET /api/v1/blog/{slug}`** - Returns post with categories and tags
- **`GET /api/v1/pages/{slug}`** - Returns page (pages don't use taxonomies)
- **`PublicPostResource`** - Already formats categories and tags for public consumption

**No changes needed** to public endpoints - they were built with taxonomy support from Sprint 4.

### 📋 **Sprint 5 Requirements (from implementation_plan.md):**

All requirements satisfied:
- ✅ Category CRUD with hierarchical parent support
- ✅ Tag CRUD (flat taxonomy)
- ✅ Custom taxonomy support via generic routes
- ✅ Auto-generate unique slugs
- ✅ Maintain count on term_taxonomy
- ✅ Cannot delete default category
- ✅ Only removes relationships, not posts

Sprint 5 is **complete and ready for Sprint 6** (Media Library API).