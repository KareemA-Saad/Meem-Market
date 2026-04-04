<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkCompetitiveFeatureRequest;
use App\Http\Requests\Admin\ReorderCompetitiveFeaturesRequest;
use App\Http\Requests\Admin\StoreCompetitiveFeatureRequest;
use App\Http\Requests\Admin\UpdateCompetitiveFeatureRequest;
use App\Http\Resources\V1\Admin\CompetitiveFeatureResource;
use App\Models\CompetitiveFeature;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Homepage Features", description: "Homepage feature CRUD, bulk actions, and sorting")]
class CompetitiveFeatureAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/homepage/features",
        operationId: "adminHomepageFeaturesIndex",
        summary: "List homepage features",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = CompetitiveFeature::query();

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->query('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $allowedSorts = ['id', 'title', 'sort_order', 'created_at', 'updated_at'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts, true)
            ? (string) $request->query('sort_by')
            : 'sort_order';
        $sortDir = strtolower((string) $request->query('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';

        $query->orderBy($sortBy, $sortDir)->orderBy('id');

        $perPage = min(max((int) $request->query('per_page', 20), 1), 100);

        return $this->paginated($query->paginate($perPage), CompetitiveFeatureResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/features",
        operationId: "adminHomepageFeaturesStore",
        summary: "Create homepage feature",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 201, description: "Created")]
    )]
    public function store(StoreCompetitiveFeatureRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $feature = CompetitiveFeature::create([
            'title' => (string) $validated['title'],
            'description' => (string) $validated['description'],
            'icon' => $validated['icon'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        Log::info('admin.homepage.features.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $feature->id,
        ]);

        return $this->success(new CompetitiveFeatureResource($feature), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/homepage/features/{id}",
        operationId: "adminHomepageFeaturesShow",
        summary: "Show homepage feature",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function show(int $id): JsonResponse
    {
        $feature = CompetitiveFeature::query()->find($id);

        if (!$feature) {
            return $this->error('Feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        }

        return $this->success(new CompetitiveFeatureResource($feature));
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/features/{id}",
        operationId: "adminHomepageFeaturesUpdate",
        summary: "Update homepage feature",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Updated")]
    )]
    public function update(UpdateCompetitiveFeatureRequest $request, int $id): JsonResponse
    {
        $feature = CompetitiveFeature::query()->find($id);

        if (!$feature) {
            return $this->error('Feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        }

        $feature->update($request->validated());
        $feature->refresh();

        Log::info('admin.homepage.features.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $feature->id,
        ]);

        return $this->success(new CompetitiveFeatureResource($feature));
    }

    #[OA\Delete(
        path: "/api/v1/admin/homepage/features/{id}",
        operationId: "adminHomepageFeaturesDestroy",
        summary: "Delete homepage feature",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Deleted")]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $feature = CompetitiveFeature::query()->find($id);

        if (!$feature) {
            return $this->error('Feature not found.', 404, null, 'FEATURE_NOT_FOUND');
        }

        $feature->delete();

        Log::info('admin.homepage.features.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Feature deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/features/bulk",
        operationId: "adminHomepageFeaturesBulk",
        summary: "Bulk action for homepage features",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Bulk processed")]
    )]
    public function bulk(BulkCompetitiveFeatureRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $features = CompetitiveFeature::query()->whereIn('id', $ids)->get();

        if ($features->isEmpty()) {
            return $this->error('No valid features found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $features, &$affected): void {
            if ($action === 'delete') {
                foreach ($features as $feature) {
                    $feature->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = CompetitiveFeature::query()
                ->whereIn('id', $features->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.homepage.features.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $features->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} feature(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/features/reorder",
        operationId: "adminHomepageFeaturesReorder",
        summary: "Reorder homepage features",
        tags: ["Admin Homepage Features"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Reordered")]
    )]
    public function reorder(ReorderCompetitiveFeaturesRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                CompetitiveFeature::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = CompetitiveFeature::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.homepage.features.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Features reordered successfully.',
            'affected' => count($items),
            'features' => CompetitiveFeatureResource::collection($sorted),
        ]);
    }
}
