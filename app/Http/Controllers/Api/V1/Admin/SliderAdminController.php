<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkSliderRequest;
use App\Http\Requests\Admin\ReorderSlidersRequest;
use App\Http\Requests\Admin\StoreSliderRequest;
use App\Http\Requests\Admin\UpdateSliderRequest;
use App\Http\Resources\V1\Admin\SliderResource;
use App\Models\Slider;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: "Admin Sliders", description: "Slider CRUD, bulk actions, and sorting")]
class SliderAdminController extends ApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    #[OA\Get(
        path: "/api/v1/admin/sliders",
        operationId: "adminListSliders",
        summary: "List sliders",
        tags: ["Admin Sliders"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "media_type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["image", "video"]), example: "image"),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "summer"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["id", "title", "sort_order", "created_at", "updated_at"]), example: "sort_order"),
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
        $query = Slider::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('media_type')) {
            $query->where('media_type', (string) $request->query('media_type'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('link', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'title', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), SliderResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/sliders",
        operationId: "adminCreateSlider",
        summary: "Create slider",
        description: "Creates a new slider. Use multipart form-data with image file.",
        tags: ["Admin Sliders"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["image"],
                    properties: [
                        new OA\Property(property: "title", type: "string", nullable: true, example: "Summer Banner"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                        new OA\Property(property: "media_type", type: "string", enum: ["image", "video"], example: "image"),
                        new OA\Property(property: "link", type: "string", nullable: true, example: "https://meem.market/offers"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 1),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Created"),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function store(StoreSliderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $slider = DB::transaction(function () use ($request, $validated): Slider {
                $uploaded = $this->mediaService->upload([$request->file('image')], $request->user());
                $imageUrl = $uploaded[0]?->guid ?? null;

                return Slider::create([
                    'title' => $validated['title'] ?? null,
                    'image' => $imageUrl,
                    'media_type' => (string) ($validated['media_type'] ?? 'image'),
                    'link' => $validated['link'] ?? null,
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                    'sort_order' => (int) ($validated['sort_order'] ?? 0),
                ]);
            });
        } catch (Throwable $exception) {
            Log::error('admin.sliders.create_failed', [
                'actor_id' => $request->user()?->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to create slider.', 500, null, 'CREATE_FAILED');
        }

        Log::info('admin.sliders.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $slider->id,
        ]);

        return $this->success(new SliderResource($slider), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/sliders/{id}",
        operationId: "adminShowSlider",
        summary: "Show slider",
        tags: ["Admin Sliders"],
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
        $slider = Slider::query()->find($id);

        if (!$slider) {
            return $this->error('Slider not found.', 404, null, 'SLIDER_NOT_FOUND');
        }

        return $this->success(new SliderResource($slider));
    }

    #[OA\Put(
        path: "/api/v1/admin/sliders/{id}",
        operationId: "adminUpdateSlider",
        summary: "Update slider",
        description: "Updates slider. For multipart uploads in Swagger/PHP, use POST /api/v1/admin/sliders/{id}.",
        tags: ["Admin Sliders"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "title", type: "string", nullable: true, example: "Summer Banner Updated"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                        new OA\Property(property: "media_type", type: "string", enum: ["image", "video"], example: "image"),
                        new OA\Property(property: "link", type: "string", nullable: true, example: "https://meem.market/offers"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 3),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    #[OA\Post(
        path: "/api/v1/admin/sliders/{id}",
        operationId: "adminUpdateSliderMultipart",
        summary: "Update slider (multipart-safe)",
        description: "Updates slider using multipart form-data in environments where PUT multipart parsing is unreliable.",
        tags: ["Admin Sliders"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 1),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "title", type: "string", nullable: true, example: "Summer Banner Updated"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                        new OA\Property(property: "media_type", type: "string", enum: ["image", "video"], example: "image"),
                        new OA\Property(property: "link", type: "string", nullable: true, example: "https://meem.market/offers"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 3),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Success"),
            new OA\Response(response: 404, description: "Not found", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 422, description: "Validation error", content: new OA\JsonContent(ref: "#/components/schemas/AdminValidationErrorResponse")),
            new OA\Response(response: 401, description: "Unauthenticated", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 403, description: "Forbidden", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
            new OA\Response(response: 500, description: "Server error", content: new OA\JsonContent(ref: "#/components/schemas/AdminErrorResponse")),
        ]
    )]
    public function update(UpdateSliderRequest $request, int $id): JsonResponse
    {
        $slider = Slider::query()->find($id);

        if (!$slider) {
            return $this->error('Slider not found.', 404, null, 'SLIDER_NOT_FOUND');
        }

        $validated = $request->validated();
        $updates = collect($validated)->except(['image'])->all();

        if (empty($updates) && !$request->hasFile('image')) {
            return $this->error(
                'No update fields were provided. For multipart requests, submit as POST with _method=PUT.',
                422,
                null,
                'NO_UPDATE_FIELDS'
            );
        }

        try {
            DB::transaction(function () use ($request, $slider, $updates): void {
                if ($request->hasFile('image')) {
                    $uploaded = $this->mediaService->upload([$request->file('image')], $request->user());
                    $updates['image'] = $uploaded[0]?->guid ?? $slider->image;
                }

                $slider->update($updates);
            });
        } catch (Throwable $exception) {
            Log::error('admin.sliders.update_failed', [
                'actor_id' => $request->user()?->id,
                'resource_id' => $slider->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to update slider.', 500, null, 'UPDATE_FAILED');
        }

        $slider->refresh();

        Log::info('admin.sliders.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $slider->id,
        ]);

        return $this->success(new SliderResource($slider));
    }

    #[OA\Delete(
        path: "/api/v1/admin/sliders/{id}",
        operationId: "adminDeleteSlider",
        summary: "Delete slider",
        tags: ["Admin Sliders"],
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
        $slider = Slider::query()->find($id);

        if (!$slider) {
            return $this->error('Slider not found.', 404, null, 'SLIDER_NOT_FOUND');
        }

        $slider->delete();

        Log::info('admin.sliders.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Slider deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/sliders/bulk",
        operationId: "adminBulkSliders",
        summary: "Bulk action for sliders",
        tags: ["Admin Sliders"],
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
    public function bulk(BulkSliderRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $sliders = Slider::query()->whereIn('id', $ids)->get();

        if ($sliders->isEmpty()) {
            return $this->error('No valid sliders found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $sliders, &$affected): void {
            if ($action === 'delete') {
                foreach ($sliders as $slider) {
                    $slider->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Slider::query()
                ->whereIn('id', $sliders->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.sliders.bulk', [
            'actor_id' => request()->user()?->id,
            'action' => $action,
            'ids' => $sliders->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} slider(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/sliders/reorder",
        operationId: "adminReorderSliders",
        summary: "Reorder sliders",
        tags: ["Admin Sliders"],
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
                        )
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
    public function reorder(ReorderSlidersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Slider::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Slider::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.sliders.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Sliders reordered successfully.',
            'affected' => count($items),
            'sliders' => SliderResource::collection($sorted),
        ]);
    }
}
