<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkBranchRequest;
use App\Http\Requests\Admin\ReorderBranchesRequest;
use App\Http\Requests\Admin\StoreBranchRequest;
use App\Http\Requests\Admin\UpdateBranchRequest;
use App\Http\Resources\V1\Admin\BranchResource;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Branches", description: "Branch CRUD, bulk actions, and sorting")]
class BranchAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/branches",
        operationId: "adminListBranches",
        summary: "List branches",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "country_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "ahsa"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["id", "name_ar", "name_en", "sort_order", "created_at", "updated_at"]), example: "sort_order"),
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
        $query = Branch::query()
            ->with(['country'])
            ->withCount('offerCategories');

        if ($request->filled('country_id')) {
            $query->where('country_id', (int) $request->query('country_id'));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'name_ar', 'name_en', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');
        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), BranchResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/branches",
        operationId: "adminCreateBranch",
        summary: "Create branch",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["country_id", "name_ar"],
                properties: [
                    new OA\Property(property: "country_id", type: "integer", example: 1),
                    new OA\Property(property: "name_ar", type: "string", example: "الأحساء"),
                    new OA\Property(property: "name_en", type: "string", nullable: true, example: "Al Ahsa"),
                    new OA\Property(property: "slug", type: "string", example: "ahsa"),
                    new OA\Property(property: "address", type: "string", nullable: true, example: "Al Ahsa, Saudi Arabia"),
                    new OA\Property(property: "google_maps_url", type: "string", nullable: true, example: "https://maps.app.goo.gl/example"),
                    new OA\Property(property: "latitude", type: "number", format: "double", nullable: true, example: 25.4017469),
                    new OA\Property(property: "longitude", type: "number", format: "double", nullable: true, example: 49.5600663),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "0551297970"),
                    new OA\Property(property: "unified_phone", type: "string", nullable: true, example: "920010937"),
                    new OA\Property(property: "social_links", type: "object", nullable: true),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "sort_order", type: "integer", example: 1),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function store(StoreBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slug = $this->resolveSlugForStore($validated);

        if ($this->slugExists($slug)) {
            return $this->error(
                'The slug is already in use.',
                422,
                ['slug' => ['The slug has already been taken.']],
                'DUPLICATE_SLUG',
            );
        }

        $branch = Branch::create([
            'country_id' => (int) $validated['country_id'],
            'name_ar' => (string) $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'slug' => $slug,
            'address' => $validated['address'] ?? null,
            'google_maps_url' => $validated['google_maps_url'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'phone' => $validated['phone'] ?? null,
            'unified_phone' => $validated['unified_phone'] ?? null,
            'social_links' => $validated['social_links'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $branch->load(['country'])->loadCount('offerCategories');

        Log::info('admin.branches.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $branch->id,
            'country_id' => $branch->country_id,
        ]);

        return $this->success(new BranchResource($branch), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/branches/{id}",
        operationId: "adminShowBranch",
        summary: "Show branch",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 2),
        ],
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $branch = Branch::query()
            ->with(['country', 'offerCategories' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('offerCategories')
            ->find($id);

        if (!$branch) {
            return $this->error('Branch not found.', 404, null, 'BRANCH_NOT_FOUND');
        }

        return $this->success(new BranchResource($branch));
    }

    #[OA\Put(
        path: "/api/v1/admin/branches/{id}",
        operationId: "adminUpdateBranch",
        summary: "Update branch",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 2),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "country_id", type: "integer", example: 1),
                    new OA\Property(property: "name_ar", type: "string", example: "الأحساء"),
                    new OA\Property(property: "name_en", type: "string", nullable: true, example: "Al Ahsa"),
                    new OA\Property(property: "slug", type: "string", example: "ahsa"),
                    new OA\Property(property: "address", type: "string", nullable: true, example: "Al Ahsa, Saudi Arabia"),
                    new OA\Property(property: "google_maps_url", type: "string", nullable: true, example: "https://maps.app.goo.gl/example"),
                    new OA\Property(property: "latitude", type: "number", format: "double", nullable: true, example: 25.4017469),
                    new OA\Property(property: "longitude", type: "number", format: "double", nullable: true, example: 49.5600663),
                    new OA\Property(property: "phone", type: "string", nullable: true, example: "0551297970"),
                    new OA\Property(property: "unified_phone", type: "string", nullable: true, example: "920010937"),
                    new OA\Property(property: "social_links", type: "object", nullable: true),
                    new OA\Property(property: "is_active", type: "boolean", example: true),
                    new OA\Property(property: "sort_order", type: "integer", example: 2),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function update(UpdateBranchRequest $request, int $id): JsonResponse
    {
        $branch = Branch::query()->find($id);

        if (!$branch) {
            return $this->error('Branch not found.', 404, null, 'BRANCH_NOT_FOUND');
        }

        $validated = $request->validated();
        $updates = $validated;

        if (array_key_exists('slug', $validated)) {
            $slug = Str::slug((string) $validated['slug']) ?: 'branch';

            if ($this->slugExists($slug, $branch->id)) {
                return $this->error(
                    'The slug is already in use.',
                    422,
                    ['slug' => ['The slug has already been taken.']],
                    'DUPLICATE_SLUG',
                );
            }

            $updates['slug'] = $slug;
        }

        $branch->update($updates);
        $branch->refresh()->load(['country'])->loadCount('offerCategories');

        Log::info('admin.branches.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $branch->id,
            'country_id' => $branch->country_id,
        ]);

        return $this->success(new BranchResource($branch));
    }

    #[OA\Delete(
        path: "/api/v1/admin/branches/{id}",
        operationId: "adminDeleteBranch",
        summary: "Delete branch",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 2),
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $branch = Branch::query()->withCount('offerCategories')->find($id);

        if (!$branch) {
            return $this->error('Branch not found.', 404, null, 'BRANCH_NOT_FOUND');
        }

        $categoriesCount = (int) $branch->offer_categories_count;
        $branch->delete();

        Log::info('admin.branches.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
            'cascaded_offer_categories' => $categoriesCount,
        ]);

        return $this->success(['message' => 'Branch deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/branches/bulk",
        operationId: "adminBulkBranches",
        summary: "Bulk action for branches",
        tags: ["Admin Branches"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action", "ids"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["delete", "activate", "deactivate"], example: "deactivate"),
                    new OA\Property(property: "ids", type: "array", items: new OA\Items(type: "integer"), example: [1, 2]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function bulk(BulkBranchRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $branches = Branch::query()->whereIn('id', $ids)->get();

        if ($branches->isEmpty()) {
            return $this->error('No valid branches found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $branches, &$affected): void {
            if ($action === 'delete') {
                foreach ($branches as $branch) {
                    $branch->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Branch::query()
                ->whereIn('id', $branches->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.branches.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $branches->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} branch(es) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/branches/reorder",
        operationId: "adminReorderBranches",
        summary: "Reorder branches",
        tags: ["Admin Branches"],
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
                                new OA\Property(property: "id", type: "integer", example: 1),
                                new OA\Property(property: "sort_order", type: "integer", example: 2),
                            ],
                            type: "object",
                        ),
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: "Reordered"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ],
    )]
    public function reorder(ReorderBranchesRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Branch::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Branch::query()->with('country')->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.branches.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Branches reordered successfully.',
            'affected' => count($items),
            'branches' => BranchResource::collection($sorted),
        ]);
    }

    private function resolveSlugForStore(array $validated): string
    {
        $provided = trim((string) ($validated['slug'] ?? ''));
        if ($provided !== '') {
            return Str::slug($provided) ?: 'branch';
        }

        $source = trim((string) ($validated['name_en'] ?? $validated['name_ar']));
        return $this->generateUniqueSlug($source);
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Branch::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'branch';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
