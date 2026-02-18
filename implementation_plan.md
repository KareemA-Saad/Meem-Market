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
