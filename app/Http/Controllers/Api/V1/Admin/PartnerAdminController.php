<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkPartnerRequest;
use App\Http\Requests\Admin\ReorderPartnersRequest;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Http\Resources\V1\Admin\PartnerResource;
use App\Models\Partner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Homepage Partners", description: "Homepage partner CRUD, bulk actions, and sorting")]
class PartnerAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/homepage/partners",
        operationId: "adminHomepagePartnersIndex",
        summary: "List homepage partners",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Partner::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'name', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), PartnerResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/partners",
        operationId: "adminHomepagePartnersStore",
        summary: "Create homepage partner",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 201, description: "Created")]
    )]
    public function store(StorePartnerRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $partner = Partner::create([
            'name' => (string) $validated['name'],
            'logo' => (string) $validated['logo'],
            'url' => $validated['url'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        Log::info('admin.homepage.partners.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $partner->id,
        ]);

        return $this->success(new PartnerResource($partner), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/homepage/partners/{id}",
        operationId: "adminHomepagePartnersShow",
        summary: "Show homepage partner",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function show(int $id): JsonResponse
    {
        $partner = Partner::query()->find($id);

        if (!$partner) {
            return $this->error('Partner not found.', 404, null, 'PARTNER_NOT_FOUND');
        }

        return $this->success(new PartnerResource($partner));
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/partners/{id}",
        operationId: "adminHomepagePartnersUpdate",
        summary: "Update homepage partner",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Updated")]
    )]
    public function update(UpdatePartnerRequest $request, int $id): JsonResponse
    {
        $partner = Partner::query()->find($id);

        if (!$partner) {
            return $this->error('Partner not found.', 404, null, 'PARTNER_NOT_FOUND');
        }

        $partner->update($request->validated());
        $partner->refresh();

        Log::info('admin.homepage.partners.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $partner->id,
        ]);

        return $this->success(new PartnerResource($partner));
    }

    #[OA\Delete(
        path: "/api/v1/admin/homepage/partners/{id}",
        operationId: "adminHomepagePartnersDestroy",
        summary: "Delete homepage partner",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Deleted")]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $partner = Partner::query()->find($id);

        if (!$partner) {
            return $this->error('Partner not found.', 404, null, 'PARTNER_NOT_FOUND');
        }

        $partner->delete();

        Log::info('admin.homepage.partners.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Partner deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/partners/bulk",
        operationId: "adminHomepagePartnersBulk",
        summary: "Bulk action for homepage partners",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Bulk processed")]
    )]
    public function bulk(BulkPartnerRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $partners = Partner::query()->whereIn('id', $ids)->get();

        if ($partners->isEmpty()) {
            return $this->error('No valid partners found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $partners, &$affected): void {
            if ($action === 'delete') {
                foreach ($partners as $partner) {
                    $partner->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Partner::query()
                ->whereIn('id', $partners->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.homepage.partners.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $partners->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} partner(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/partners/reorder",
        operationId: "adminHomepagePartnersReorder",
        summary: "Reorder homepage partners",
        tags: ["Admin Homepage Partners"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Reordered")]
    )]
    public function reorder(ReorderPartnersRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Partner::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Partner::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.homepage.partners.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Partners reordered successfully.',
            'affected' => count($items),
            'partners' => PartnerResource::collection($sorted),
        ]);
    }
}
