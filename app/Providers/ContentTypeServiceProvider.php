<?php

namespace App\Providers;

use App\Http\Controllers\Api\V1\Admin\PostController;
use App\Http\Controllers\Api\V1\Admin\TaxonomyController;
use App\Services\OptionService;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Dynamically registers CRUD routes for custom post types and taxonomies
 * that have been defined via the Content Type admin API.
 *
 * On boot, this provider reads the `cptui_post_types` and `cptui_taxonomies`
 * option keys and registers route groups mirroring the built-in post/page
 * and category/tag patterns.
 *
 * Why a ServiceProvider? Routes must be registered before any request is
 * dispatched. A provider's boot() method runs at the right lifecycle
 * moment — after all services are bound but before routing begins.
 */
class ContentTypeServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Guard: skip during artisan commands that don't need routing (e.g. migrate)
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            return;
        }

        try {
            $this->registerDynamicRoutes();
        } catch (\Throwable) {
            // Silently fail if DB is not yet migrated or options table doesn't exist.
            // This prevents the provider from crashing during initial setup.
        }
    }

    private function registerDynamicRoutes(): void
    {
        /** @var OptionService $optionService */
        $optionService = $this->app->make(OptionService::class);

        $this->registerCustomPostTypeRoutes($optionService);
        $this->registerCustomTaxonomyRoutes($optionService);
    }

    /**
     * Register CRUD routes for each custom post type.
     * Reuses the existing PostController (which resolves type from the route prefix).
     */
    private function registerCustomPostTypeRoutes(OptionService $optionService): void
    {
        $postTypesJson = $optionService->get('cptui_post_types', '{}');
        $postTypes = json_decode($postTypesJson, true) ?: [];

        foreach ($postTypes as $slug => $definition) {
            Route::prefix("api/v1/admin/{$slug}")
                ->middleware(['api', 'auth:sanctum', 'can_do:edit_posts'])
                ->group(function () use ($slug) {
                    Route::get('/', [PostController::class, 'index'])
                        ->defaults('post_type', $slug);
                    Route::post('/', [PostController::class, 'store'])
                        ->defaults('post_type', $slug);
                    Route::get('/{post}', [PostController::class, 'show'])
                        ->defaults('post_type', $slug);
                    Route::put('/{post}', [PostController::class, 'update'])
                        ->defaults('post_type', $slug);
                    Route::delete('/{post}', [PostController::class, 'destroy'])
                        ->defaults('post_type', $slug);
                    Route::put('/{post}/trash', [PostController::class, 'trash'])
                        ->defaults('post_type', $slug);
                    Route::put('/{post}/restore', [PostController::class, 'restore'])
                        ->defaults('post_type', $slug);
                });
        }
    }

    /**
     * Register CRUD routes for each custom taxonomy.
     * Reuses the existing TaxonomyController.
     */
    private function registerCustomTaxonomyRoutes(OptionService $optionService): void
    {
        $taxonomiesJson = $optionService->get('cptui_taxonomies', '{}');
        $taxonomies = json_decode($taxonomiesJson, true) ?: [];

        foreach ($taxonomies as $slug => $definition) {
            Route::prefix("api/v1/admin/taxonomies/{$slug}/terms")
                ->middleware(['api', 'auth:sanctum', 'can_do:manage_categories'])
                ->group(function () {
                    Route::get('/', [TaxonomyController::class, 'index']);
                    Route::post('/', [TaxonomyController::class, 'store']);
                    Route::post('/bulk', [TaxonomyController::class, 'bulk']);
                    Route::get('/{id}', [TaxonomyController::class, 'show']);
                    Route::put('/{id}', [TaxonomyController::class, 'update']);
                    Route::delete('/{id}', [TaxonomyController::class, 'destroy']);
                });
        }
    }
}
