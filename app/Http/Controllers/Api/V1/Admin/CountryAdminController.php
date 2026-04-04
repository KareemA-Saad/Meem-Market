<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkCountryRequest;
use App\Http\Requests\Admin\ReorderCountriesRequest;
use App\Http\Requests\Admin\StoreCountryRequest;
use App\Http\Requests\Admin\UpdateCountryRequest;
use App\Http\Resources\V1\Admin\CountryResource;
use App\Models\Country;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Countries", description: "Country CRUD, bulk actions, and sorting")]
class CountryAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/countries",
        operationId: "adminListCountries",
        summary: "List countries",
        tags: ["Admin Countries"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "saudi"),
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
        $query = Country::query()->withCount('branches');

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

        return $this->paginated($query->paginate($perPage), CountryResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/countries",
        operationId: "adminCreateCountry",
        summary: "Create country",
        tags: ["Admin Countries"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name_ar"],
                properties: [
                    new OA\Property(property: "name_ar", type: "string", example: "السعودية"),
                    new OA\Property(property: "name_en", type: "string", nullable: true, example: "Saudi Arabia"),
                    new OA\Property(property: "slug", type: "string", example: "saudi-arabia"),
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
    public function store(StoreCountryRequest $request): JsonResponse
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

        $country = Country::create([
            'name_ar' => (string) $validated['name_ar'],
            'name_en' => $validated['name_en'] ?? null,
            'slug' => $slug,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $country->loadCount('branches');

        Log::info('admin.countries.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $country->id,
        ]);

        return $this->success(new CountryResource($country), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/countries/{id}",
        operationId: "adminShowCountry",
        summary: "Show country",
        tags: ["Admin Countries"],
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
        $country = Country::query()
            ->with(['branches' => fn ($q) => $q->orderBy('sort_order')])
            ->withCount('branches')
            ->find($id);

        if (!$country) {
            return $this->error('Country not found.', 404, null, 'COUNTRY_NOT_FOUND');
        }

        return $this->success(new CountryResource($country));
    }

    #[OA\Put(
        path: "/api/v1/admin/countries/{id}",
        operationId: "adminUpdateCountry",
        summary: "Update country",
        tags: ["Admin Countries"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "name_ar", type: "string", example: "المملكة العربية السعودية"),
                    new OA\Property(property: "name_en", type: "string", nullable: true, example: "Saudi Arabia"),
                    new OA\Property(property: "slug", type: "string", example: "saudi-arabia"),
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
    public function update(UpdateCountryRequest $request, int $id): JsonResponse
    {
        $country = Country::query()->find($id);

        if (!$country) {
            return $this->error('Country not found.', 404, null, 'COUNTRY_NOT_FOUND');
        }

        $validated = $request->validated();
        $updates = $validated;

        if (array_key_exists('slug', $validated)) {
            $slug = Str::slug((string) $validated['slug']) ?: 'country';

            if ($this->slugExists($slug, $country->id)) {
                return $this->error(
                    'The slug is already in use.',
                    422,
                    ['slug' => ['The slug has already been taken.']],
                    'DUPLICATE_SLUG',
                );
            }

            $updates['slug'] = $slug;
        }

        $country->update($updates);
        $country->refresh()->loadCount('branches');

        Log::info('admin.countries.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $country->id,
        ]);

        return $this->success(new CountryResource($country));
    }

    #[OA\Delete(
        path: "/api/v1/admin/countries/{id}",
        operationId: "adminDeleteCountry",
        summary: "Delete country",
        tags: ["Admin Countries"],
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
        $country = Country::query()->withCount('branches')->find($id);

        if (!$country) {
            return $this->error('Country not found.', 404, null, 'COUNTRY_NOT_FOUND');
        }

        $branchesCount = (int) $country->branches_count;
        $country->delete();

        Log::info('admin.countries.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
            'cascaded_branches' => $branchesCount,
        ]);

        return $this->success(['message' => 'Country deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/countries/bulk",
        operationId: "adminBulkCountries",
        summary: "Bulk action for countries",
        tags: ["Admin Countries"],
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
    public function bulk(BulkCountryRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $countries = Country::query()->whereIn('id', $ids)->get();

        if ($countries->isEmpty()) {
            return $this->error('No valid countries found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $countries, &$affected): void {
            if ($action === 'delete') {
                foreach ($countries as $country) {
                    $country->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Country::query()
                ->whereIn('id', $countries->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.countries.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $countries->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} country(ies) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/countries/reorder",
        operationId: "adminReorderCountries",
        summary: "Reorder countries",
        tags: ["Admin Countries"],
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
    public function reorder(ReorderCountriesRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Country::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Country::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.countries.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Countries reordered successfully.',
            'affected' => count($items),
            'countries' => CountryResource::collection($sorted),
        ]);
    }

    private function resolveSlugForStore(array $validated): string
    {
        $provided = trim((string) ($validated['slug'] ?? ''));
        if ($provided !== '') {
            return Str::slug($provided) ?: 'country';
        }

        $source = trim((string) ($validated['name_en'] ?? $validated['name_ar']));
        return $this->generateUniqueSlug($source);
    }

    private function slugExists(string $slug, ?int $ignoreId = null): bool
    {
        return Country::query()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    private function generateUniqueSlug(string $source, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($source) ?: 'country';
        $slug = $baseSlug;
        $counter = 2;

        while ($this->slugExists($slug, $ignoreId)) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
