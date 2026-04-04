<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkOfferRequest;
use App\Http\Requests\Admin\ReorderOffersRequest;
use App\Http\Requests\Admin\StoreOfferRequest;
use App\Http\Requests\Admin\UpdateOfferRequest;
use App\Http\Resources\V1\Admin\OfferResource;
use App\Models\Offer;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;
use Throwable;

#[OA\Tag(name: "Admin Offers", description: "Offer CRUD, bulk actions, and sorting")]
class OfferAdminController extends ApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    #[OA\Get(
        path: "/api/v1/admin/offers",
        operationId: "adminListOffers",
        summary: "List offers",
        description: "Returns paginated offers with optional filters and sorting.",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "offer_category_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 1),
            new OA\Parameter(name: "branch_id", in: "query", required: false, schema: new OA\Schema(type: "integer"), example: 2),
            new OA\Parameter(name: "is_active", in: "query", required: false, schema: new OA\Schema(type: "boolean"), example: true),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), example: "winter"),
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
        $query = Offer::query()->with(['offerCategory.branch']);

        if ($request->filled('offer_category_id')) {
            $query->where('offer_category_id', (int) $request->query('offer_category_id'));
        }

        if ($request->filled('branch_id')) {
            $branchId = (int) $request->query('branch_id');
            $query->whereHas('offerCategory', fn ($q) => $q->where('branch_id', $branchId));
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where('title', 'like', "%{$search}%");
        }

        $allowedSorts = ['id', 'title', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), OfferResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/offers",
        operationId: "adminCreateOffer",
        summary: "Create an offer",
        description: "Creates a new offer. Use multipart form-data and attach an image file.",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    required: ["offer_category_id", "image"],
                    properties: [
                        new OA\Property(property: "offer_category_id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Winter Offer 2026"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
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
    public function store(StoreOfferRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $offer = DB::transaction(function () use ($request, $validated): Offer {
                $uploaded = $this->mediaService->upload([$request->file('image')], $request->user());
                $imageUrl = $uploaded[0]?->guid ?? null;

                $offer = Offer::create([
                    'offer_category_id' => (int) $validated['offer_category_id'],
                    'title' => $validated['title'] ?? null,
                    'image' => $imageUrl,
                    'is_active' => (bool) ($validated['is_active'] ?? true),
                    'sort_order' => (int) ($validated['sort_order'] ?? 0),
                ]);

                return $offer->load(['offerCategory.branch']);
            });
        } catch (Throwable $exception) {
            Log::error('admin.offers.create_failed', [
                'actor_id' => $request->user()?->id,
                'offer_category_id' => $validated['offer_category_id'] ?? null,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to create offer.', 500, null, 'CREATE_FAILED');
        }

        Log::info('admin.offers.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $offer->id,
            'offer_category_id' => $offer->offer_category_id,
        ]);

        return $this->success(new OfferResource($offer), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/offers/{id}",
        operationId: "adminShowOffer",
        summary: "Show one offer",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 12),
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
        $offer = Offer::query()->with(['offerCategory.branch'])->find($id);

        if (!$offer) {
            return $this->error('Offer not found.', 404, null, 'OFFER_NOT_FOUND');
        }

        return $this->success(new OfferResource($offer));
    }

    #[OA\Put(
        path: "/api/v1/admin/offers/{id}",
        operationId: "adminUpdateOffer",
        summary: "Update an offer",
        description: "Updates an offer. Supports JSON payloads. For multipart form-data uploads in Swagger UI/PHP environments, use POST /api/v1/admin/offers/{id}.",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 12),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "offer_category_id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Winter Offer 2026 - Updated"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 5),
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
    #[OA\Post(
        path: "/api/v1/admin/offers/{id}",
        operationId: "adminUpdateOfferMultipart",
        summary: "Update an offer (multipart-safe)",
        description: "Updates an offer using multipart form-data in environments where PUT multipart parsing is unreliable.",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 12),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "offer_category_id", type: "integer", example: 1),
                        new OA\Property(property: "title", type: "string", example: "Winter Offer 2026 - Updated"),
                        new OA\Property(property: "image", type: "string", format: "binary"),
                        new OA\Property(property: "is_active", type: "boolean", example: true),
                        new OA\Property(property: "sort_order", type: "integer", example: 5),
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
    public function update(UpdateOfferRequest $request, int $id): JsonResponse
    {
        $offer = Offer::query()->find($id);

        if (!$offer) {
            return $this->error('Offer not found.', 404, null, 'OFFER_NOT_FOUND');
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
            DB::transaction(function () use ($request, $offer, $updates): void {
                if ($request->hasFile('image')) {
                    $uploaded = $this->mediaService->upload([$request->file('image')], $request->user());
                    $updates['image'] = $uploaded[0]?->guid ?? $offer->image;
                }

                $offer->update($updates);
            });
        } catch (Throwable $exception) {
            Log::error('admin.offers.update_failed', [
                'actor_id' => $request->user()?->id,
                'resource_id' => $offer->id,
                'error' => $exception->getMessage(),
            ]);

            return $this->error('Failed to update offer.', 500, null, 'UPDATE_FAILED');
        }

        $offer->refresh()->load(['offerCategory.branch']);

        Log::info('admin.offers.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $offer->id,
            'offer_category_id' => $offer->offer_category_id,
        ]);

        return $this->success(new OfferResource($offer));
    }

    #[OA\Delete(
        path: "/api/v1/admin/offers/{id}",
        operationId: "adminDeleteOffer",
        summary: "Delete an offer",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"), example: 12),
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
        $offer = Offer::query()->find($id);

        if (!$offer) {
            return $this->error('Offer not found.', 404, null, 'OFFER_NOT_FOUND');
        }

        $offer->delete();

        Log::info('admin.offers.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Offer deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/offers/bulk",
        operationId: "adminBulkOffers",
        summary: "Bulk action for offers",
        tags: ["Admin Offers"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action", "ids"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["delete", "activate", "deactivate"], example: "deactivate"),
                    new OA\Property(property: "ids", type: "array", items: new OA\Items(type: "integer"), example: [3, 4, 7]),
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
    public function bulk(BulkOfferRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ids = $validated['ids'];
        $action = $validated['action'];

        $offers = Offer::query()->whereIn('id', $ids)->get();

        if ($offers->isEmpty()) {
            return $this->error('No valid offers found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $offers, &$affected): void {
            if ($action === 'delete') {
                foreach ($offers as $offer) {
                    $offer->delete();
                    $affected++;
                }
                return;
            }

            $activeState = $action === 'activate';
            $affected = Offer::query()
                ->whereIn('id', $offers->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.offers.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $offers->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} offer(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/offers/reorder",
        operationId: "adminReorderOffers",
        summary: "Reorder offers",
        tags: ["Admin Offers"],
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
                                new OA\Property(property: "id", type: "integer", example: 3),
                                new OA\Property(property: "sort_order", type: "integer", example: 1),
                            ],
                            type: "object"
                        )
                    ),
                ],
                example: [
                    "items" => [
                        ["id" => 3, "sort_order" => 1],
                        ["id" => 4, "sort_order" => 2],
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
    public function reorder(ReorderOffersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Offer::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Offer::query()
            ->with(['offerCategory.branch'])
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->get();

        Log::info('admin.offers.reordered', [
            'actor_id' => $request->user()?->id,
            'affected' => count($items),
            'ids' => $ids,
        ]);

        return $this->success([
            'message' => 'Offers reordered successfully.',
            'affected' => count($items),
            'offers' => OfferResource::collection($sorted),
        ]);
    }
}
