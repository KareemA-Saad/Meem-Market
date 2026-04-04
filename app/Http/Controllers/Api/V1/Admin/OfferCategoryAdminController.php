<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkOfferCategoryRequest;
use App\Http\Requests\Admin\ReorderOfferCategoriesRequest;
use App\Http\Requests\Admin\StoreOfferCategoryRequest;
use App\Http\Requests\Admin\UpdateOfferCategoryRequest;
use App\Http\Resources\V1\Admin\OfferCategoryResource;
use App\Models\OfferCategory;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: "Admin Offer Categories", description: "Offer category CRUD, bulk actions, and sorting")]
class OfferCategoryAdminController extends ApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    #[OA\Get(
        path: "/api/v1/admin/offer-categories",
        operationId: "adminListOfferCategories",
        summary: "List offer categories",
        description: "Returns paginated offer categories with optional filters and sorting.",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "branch_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "winter"),
            new OA\Parameter(name: "start_date_from", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date"), example: "2026-01-01"),
            new OA\Parameter(name: "start_date_to", in: "query", required: false, schema: new OA\Schema(type: "string", format: "date"), example: "2026-12-31"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["id", "title", "sort_order", "start_date", "end_date", "created_at", "updated_at"]), example: "sort_order"),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"]), example: "asc"),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 20),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = OfferCategory::query()
            ->with(['branch'])
            ->withCount('offers');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->query('branch_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('start_date_from')) {
            $query->whereDate('start_date', '>=', (string) $request->query('start_date_from'));
        }

        if ($request->filled('start_date_to')) {
            $query->whereDate('start_date', '<=', (string) $request->query('start_date_to'));
        }

        $allowedSorts = ['id', 'title', 'sort_order', 'start_date', 'end_date', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), OfferCategoryResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/offer-categories",
        operationId: "adminCreateOfferCategory",
        summary: "Create an offer category",
        description: "Creates a new offer category. Use multipart form-data when uploading cover_image.",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["branch_id", "title"],
                    properties: [
                        new OA\Property(property: "branch_id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Winter 2026 Offers"),
                        new OA\Property(property: "slug", type: "string", example: "winter-2026-offers"),
                        new OA\Property(property: "cover_image", type: "string", format: "binary"),
                        new OA\Property(property: "start_date", type: "string", format: "date", example: "2026-01-15"),
                        new OA\Property(property: "end_date", type: "string", format: "date", example: "2026-02-15"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 1),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function store(StoreOfferCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $branchId = (int) $validated['branch_id'];
        $slug = $this->resolveSlugForStore($validated);

        if ($this->slugExistsForBranch($slug, $branchId)) {
            return $this->error(
                'The slug is already used for this branch.',
                422,
                ['slug' => ['The slug has already been taken for this branch.']],
                'DUPLICATE_SLUG',
            );
        }

        try {
            $category = DB::transaction(function () use ($request, $validated, $slug): OfferCategory {
                $coverImage = null;
                if ($request->hasFile('cover_image')) {
                    $uploaded = $this->mediaService->upload([$request->file('cover_image')], $request->user());
                    $coverImage = $uploaded[0]?->guid ?? null;
                }

                $category = OfferCategory::create([
                    'branch_id' => (int) $validated['branch_id'],
                    'title' => (string) $validated['title'],
                    'slug' => $slug,
                    'cover_image' => $coverImage,
                    'start_date' => $validated['start_date'] ?? null,
                    'end_date' => $validated['end_date'] ?? null,
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                    'sort_order' => (int) ($validated['sort_order'] ?? 0),
                ]);

                return $category->load(['branch'])->loadCount('offers');
            });
        } catch (Throwable $exception) {
            Log::error('admin.offer_categories.create_failed', [
                'actor_id' => $request->user()?->id,
                'branch_id' => $branchId,
                'slug' => $slug,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to create offer category.', 500, null, 'CREATE_FAILED');
        }

        Log::info('admin.offer_categories.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $category->id,
            'branch_id' => $category->branch_id,
        ]);

        return $this->success(new OfferCategoryResource($category), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/offer-categories/{id}",
        operationId: "adminShowOfferCategory",
        summary: "Show one offer category",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 5),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $category = OfferCategory::query()
            ->with([
                'branch',
                'offers' => fn ($q) => $q->orderBy('sort_order'),
            ])
            ->withCount('offers')
            ->find($id);

        if (!$category) {
            return $this->error('Offer category not found.', 404, null, 'OFFER_CATEGORY_NOT_FOUND');
        }

        return $this->success(new OfferCategoryResource($category));
    }

    #[OA\Put(
        path: "/api/v1/admin/offer-categories/{id}",
        operationId: "adminUpdateOfferCategory",
        summary: "Update an offer category",
        description: "Updates an offer category. Supports multipart form-data when replacing cover_image.",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 5),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "branch_id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Winter 2026 Offers - Updated"),
                        new OA\Property(property: "slug", type: "string", example: "winter-2026-updated"),
                        new OA\Property(property: "cover_image", type: "string", format: "binary"),
                        new OA\Property(property: "start_date", type: "string", format: "date", example: "2026-01-20"),
                        new OA\Property(property: "end_date", type: "string", format: "date", example: "2026-02-20"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 3),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function update(UpdateOfferCategoryRequest $request, int $id): JsonResponse
    {
        $category = OfferCategory::query()->find($id);

        if (!$category) {
            return $this->error('Offer category not found.', 404, null, 'OFFER_CATEGORY_NOT_FOUND');
        }

        $validated = $request->validated();
        $updates = collect($validated)->except(['cover_image', 'slug'])->all();
        $branchId = (int) ($validated['branch_id'] ?? $category->branch_id);

        if (!array_key_exists('slug', $validated) && $branchId !== (int) $category->branch_id) {
            if ($this->slugExistsForBranch($category->slug, $branchId, $category->id)) {
                return $this->error(
                    'The current slug is already used for the selected branch.',
                    422,
                    ['slug' => ['The slug has already been taken for this branch.']],
                    'DUPLICATE_SLUG',
                );
            }
        }

        if (array_key_exists('slug', $validated)) {
            $slug = Str::slug((string) $validated['slug']) ?: 'offer-category';
            if ($this->slugExistsForBranch($slug, $branchId, $category->id)) {
                return $this->error(
                    'The slug is already used for this branch.',
                    422,
                    ['slug' => ['The slug has already been taken for this branch.']],
                    'DUPLICATE_SLUG',
                );
            }
            $updates['slug'] = $slug;
        }

        try {
            DB::transaction(function () use ($request, $category, $updates): void {
                if ($request->hasFile('cover_image')) {
                    $uploaded = $this->mediaService->upload([$request->file('cover_image')], $request->user());
                    $updates['cover_image'] = $uploaded[0]?->guid ?? null;
                }

                $category->update($updates);
            });
        } catch (Throwable $exception) {
            Log::error('admin.offer_categories.update_failed', [
                'actor_id' => $request->user()?->id,
                'resource_id' => $category->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to update offer category.', 500, null, 'UPDATE_FAILED');
        }

        $category->refresh()->load(['branch'])->loadCount('offers');

        Log::info('admin.offer_categories.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $category->id,
            'branch_id' => $category->branch_id,
        ]);

        return $this->success(new OfferCategoryResource($category));
    }

    #[OA\Delete(
        path: "/api/v1/admin/offer-categories/{id}",
        operationId: "adminDeleteOfferCategory",
        summary: "Delete an offer category",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 5),
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $category = OfferCategory::query()->withCount('offers')->find($id);

        if (!$category) {
            return $this->error('Offer category not found.', 404, null, 'OFFER_CATEGORY_NOT_FOUND');
        }

        $offersCount = (int) $category->offers_count;
        $category->delete();

        Log::info('admin.offer_categories.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
            'cascaded_offers' => $offersCount,
        ]);

        return $this->success(['message' => 'Offer category deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/offer-categories/bulk",
        operationId: "adminBulkOfferCategories",
        summary: "Bulk action for offer categories",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action", "ids"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["delete", "activate", "deactivate"], example: "deactivate"),
                    new OA\Property(property: "ids", type: "array", items: new OA\Items(type: "integer"), example: [5, 6, 9]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
        ]
    )]
    public function bulk(BulkOfferCategoryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ids = $validated['ids'];
        $action = $validated['action'];

        $categories = OfferCategory::query()->whereIn('id', $ids)->get();

        if ($categories->isEmpty()) {
            return $this->error('No valid offer categories found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $categories, &$affected): void {
            if ($action === 'delete') {
                foreach ($categories as $category) {
                    $category->delete();
                    $affected++;
                }
                return;
            }

            $activeState = $action === 'activate';
            $affected = OfferCategory::query()
                ->whereIn('id', $categories->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.offer_categories.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $categories->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} offer category(ies) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/offer-categories/reorder",
        operationId: "adminReorderOfferCategories",
        summary: "Reorder offer categories",
        tags: ["Admin Offer Categories"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["items"],
                properties: [
                    new OA\Property(
                        property: "items",
                        type: "array",
                        items: new OA\Items(
                            required: ["id", "sort_order"],
                            properties: [
                                new OA\Property(property: "id", type: "integer", example: 5),
                                new OA\Property(property: "sort_order", type: "integer", example: 1),
                            ],
                            type: "object"
                        )
                    ),
                ],
                example: [
                    "items" => [
                        ["id" => 5, "sort_order" => 1],
                        ["id" => 6, "sort_order" => 2],
                    ],
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Reordered"),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
        ]
    )]
    public function reorder(ReorderOfferCategoriesRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                OfferCategory::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = OfferCategory::query()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get();

        Log::info('admin.offer_categories.reordered', [
            'actor_id' => $request->user()?->id,
            'affected' => count($items),
            'ids' => $ids,
        ]);

        return $this->success([
            'message' => 'Offer categories reordered successfully.',
            'affected' => count($items),
            'categories' => OfferCategoryResource::collection($sorted),
        ]);
    }

    private function resolveSlugForStore(array $validated): string
    {
        $provided = trim((string) ($validated['slug'] ?? ''));

        if ($provided !== '') {
            return Str::slug($provided) ?: 'offer-category';
        }

        return $this->generateUniqueSlug((string) $validated['title'], (int) $validated['branch_id']);
    }

    private function slugExistsForBranch(string $slug, int $branchId, ?int $ignoreId = null): bool
    {
        return OfferCategory::query()
            ->where('branch_id', $branchId)
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function generateUniqueSlug(string $source, int $branchId, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'offer-category';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExistsForBranch($slug, $branchId, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
