<?php

use App\Http\Controllers\Api\V1\AboutController;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
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
    });
});
