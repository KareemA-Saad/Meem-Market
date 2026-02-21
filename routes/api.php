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
use App\Http\Controllers\Api\V1\OfferCategoryController;
use App\Http\Controllers\Api\V1\SettingController;
use Illuminate\Support\Facades\Route;

// ─── PUBLIC ROUTES (existing, untouched) ─────────────────────────
Route::prefix('v1')->group(function () {
    Route::get('/home', [HomeController::class, 'index']);
    Route::get('/countries', [CountryController::class, 'index']);
    Route::get('/branches', [BranchController::class, 'index']);
    Route::get('/branches/{slug}', [BranchController::class, 'show']);
    Route::get('/offers', [OfferController::class, 'index']);
    Route::get('/offers/{id}', [OfferController::class, 'show']);
    Route::get('/offer-categories', [OfferCategoryController::class, 'index']);
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
