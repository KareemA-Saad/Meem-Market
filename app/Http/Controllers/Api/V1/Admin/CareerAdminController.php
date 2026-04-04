<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkCareerRequest;
use App\Http\Requests\Admin\ReorderCareersRequest;
use App\Http\Requests\Admin\StoreCareerRequest;
use App\Http\Requests\Admin\UpdateCareerRequest;
use App\Http\Resources\V1\Admin\CareerResource;
use App\Models\Career;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Careers", description: "Career CRUD, bulk actions, and sorting")]
class CareerAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/careers",
        operationId: "adminListCareers",
        summary: "List careers",
        tags: ["Admin Careers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "Full Time"),
            new OA\Parameter(name: "location", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "Riyadh"),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "cashier"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["id", "title", "location", "type", "sort_order", "created_at", "updated_at"]), example: "sort_order"),
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
        $query = Career::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . (string) $request->query('type') . '%');
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . (string) $request->query('location') . '%');
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'title', 'location', 'type', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), CareerResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/careers",
        operationId: "adminCreateCareer",
        summary: "Create career",
        tags: ["Admin Careers"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "description"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Store Cashier"),
                    new OA\Property(property: "slug", type: "string", example: "store-cashier"),
                    new OA\Property(property: "location", type: "string", nullable: true, example: "Riyadh"),
                    new OA\Property(property: "type", type: "string", nullable: true, example: "Full Time"),
                    new OA\Property(property: "description", type: "string", example: "Career description"),
                    new OA\Property(property: "requirements", type: "string", nullable: true, example: "Requirements list"),
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
    public function store(StoreCareerRequest $request): JsonResponse
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

        $career = Career::create([
            'title' => (string) $validated['title'],
            'slug' => $slug,
            'location' => $validated['location'] ?? null,
            'type' => $validated['type'] ?? null,
            'description' => (string) $validated['description'],
            'requirements' => $validated['requirements'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        Log::info('admin.careers.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $career->id,
        ]);

        return $this->success(new CareerResource($career), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/careers/{id}",
        operationId: "adminShowCareer",
        summary: "Show career",
        tags: ["Admin Careers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
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
        $career = Career::query()->find($id);

        if (!$career) {
            return $this->error('Career not found.', 404, null, 'CAREER_NOT_FOUND');
        }

        return $this->success(new CareerResource($career));
    }

    #[OA\Put(
        path: "/api/v1/admin/careers/{id}",
        operationId: "adminUpdateCareer",
        summary: "Update career",
        tags: ["Admin Careers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string", example: "Store Cashier - Updated"),
                    new OA\Property(property: "slug", type: "string", example: "store-cashier-updated"),
                    new OA\Property(property: "location", type: "string", nullable: true, example: "Jeddah"),
                    new OA\Property(property: "type", type: "string", nullable: true, example: "Part Time"),
                    new OA\Property(property: "description", type: "string", example: "Updated description"),
                    new OA\Property(property: "requirements", type: "string", nullable: true, example: "Updated requirements"),
                    new OA\Property(property: "is_active", type: "boolean", example: false),
                    new OA\Property(property: "sort_order", type: "integer", example: 3),
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
    public function update(UpdateCareerRequest $request, int $id): JsonResponse
    {
        $career = Career::query()->find($id);

        if (!$career) {
            return $this->error('Career not found.', 404, null, 'CAREER_NOT_FOUND');
        }

        $validated = $request->validated();
        $updates = $validated;

        if (array_key_exists('slug', $validated)) {
            $slug = Str::slug((string) $validated['slug']) ?: 'career';

            if ($this->slugExists($slug, $career->id)) {
                return $this->error(
                    'The slug is already in use.',
                    422,
                    ['slug' => ['The slug has already been taken.']],
                    'DUPLICATE_SLUG',
                );
            }

            $updates['slug'] = $slug;
        }

        $career->update($updates);
        $career->refresh();

        Log::info('admin.careers.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $career->id,
        ]);

        return $this->success(new CareerResource($career));
    }

    #[OA\Delete(
        path: "/api/v1/admin/careers/{id}",
        operationId: "adminDeleteCareer",
        summary: "Delete career",
        tags: ["Admin Careers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
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
        $career = Career::query()->find($id);

        if (!$career) {
            return $this->error('Career not found.', 404, null, 'CAREER_NOT_FOUND');
        }

        $career->delete();

        Log::info('admin.careers.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Career deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/careers/bulk",
        operationId: "adminBulkCareers",
        summary: "Bulk action for careers",
        tags: ["Admin Careers"],
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
    public function bulk(BulkCareerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $careers = Career::query()->whereIn('id', $ids)->get();

        if ($careers->isEmpty()) {
            return $this->error('No valid careers found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $careers, &$affected): void {
            if ($action === 'delete') {
                foreach ($careers as $career) {
                    $career->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Career::query()
                ->whereIn('id', $careers->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.careers.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $careers->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} career(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/careers/reorder",
        operationId: "adminReorderCareers",
        summary: "Reorder careers",
        tags: ["Admin Careers"],
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
                            type: "object"
                        ),
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Reordered"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function reorder(ReorderCareersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Career::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Career::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.careers.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Careers reordered successfully.',
            'affected' => count($items),
            'careers' => CareerResource::collection($sorted),
        ]);
    }

    private function resolveSlugForStore(array $validated): string
    {
        $provided = trim((string) ($validated['slug'] ?? ''));
        if ($provided !== '') {
            return Str::slug($provided) ?: 'career';
        }

        return $this->generateUniqueSlug((string) $validated['title']);
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Career::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'career';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
