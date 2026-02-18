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
