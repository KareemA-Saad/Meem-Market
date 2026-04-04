<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkSectionRequest;
use App\Http\Requests\Admin\ReorderSectionsRequest;
use App\Http\Requests\Admin\StoreSectionRequest;
use App\Http\Requests\Admin\UpdateSectionRequest;
use App\Http\Resources\V1\Admin\SectionResource;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Homepage Sections", description: "Homepage section CRUD, bulk actions, and sorting")]
class SectionAdminController extends ApiController
{
    #[OA\Get(
        path: "/api/v1/admin/homepage/sections",
        operationId: "adminHomepageSectionsIndex",
        summary: "List homepage sections",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Section::query();

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

        return $this->paginated($query->paginate($perPage), SectionResource::class);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/sections",
        operationId: "adminHomepageSectionsStore",
        summary: "Create homepage section",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 201, description: "Created")]
    )]
    public function store(StoreSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $section = Section::create([
            'title' => (string) $validated['title'],
            'icon' => $validated['icon'] ?? null,
            'image' => $validated['image'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        Log::info('admin.homepage.sections.created', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $section->id,
        ]);

        return $this->success(new SectionResource($section), 201);
    }

    #[OA\Get(
        path: "/api/v1/admin/homepage/sections/{id}",
        operationId: "adminHomepageSectionsShow",
        summary: "Show homepage section",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Success")]
    )]
    public function show(int $id): JsonResponse
    {
        $section = Section::query()->find($id);

        if (!$section) {
            return $this->error('Section not found.', 404, null, 'SECTION_NOT_FOUND');
        }

        return $this->success(new SectionResource($section));
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/sections/{id}",
        operationId: "adminHomepageSectionsUpdate",
        summary: "Update homepage section",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Updated")]
    )]
    public function update(UpdateSectionRequest $request, int $id): JsonResponse
    {
        $section = Section::query()->find($id);

        if (!$section) {
            return $this->error('Section not found.', 404, null, 'SECTION_NOT_FOUND');
        }

        $section->update($request->validated());
        $section->refresh();

        Log::info('admin.homepage.sections.updated', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $section->id,
        ]);

        return $this->success(new SectionResource($section));
    }

    #[OA\Delete(
        path: "/api/v1/admin/homepage/sections/{id}",
        operationId: "adminHomepageSectionsDestroy",
        summary: "Delete homepage section",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))],
        responses: [new OA\Response(response: 200, description: "Deleted")]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $section = Section::query()->find($id);

        if (!$section) {
            return $this->error('Section not found.', 404, null, 'SECTION_NOT_FOUND');
        }

        $section->delete();

        Log::info('admin.homepage.sections.deleted', [
            'actor_id' => $request->user()?->id,
            'resource_id' => $id,
        ]);

        return $this->success(['message' => 'Section deleted successfully.']);
    }

    #[OA\Post(
        path: "/api/v1/admin/homepage/sections/bulk",
        operationId: "adminHomepageSectionsBulk",
        summary: "Bulk action for homepage sections",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Bulk processed")]
    )]
    public function bulk(BulkSectionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $ids = $validated['ids'];

        $sections = Section::query()->whereIn('id', $ids)->get();

        if ($sections->isEmpty()) {
            return $this->error('No valid sections found.', 422, null, 'NO_VALID_RESOURCES');
        }

        $affected = 0;

        DB::transaction(function () use ($action, $sections, &$affected): void {
            if ($action === 'delete') {
                foreach ($sections as $section) {
                    $section->delete();
                    $affected++;
                }

                return;
            }

            $activeState = $action === 'activate';
            $affected = Section::query()
                ->whereIn('id', $sections->pluck('id'))
                ->update(['is_active' => $activeState]);
        });

        Log::info('admin.homepage.sections.bulk', [
            'actor_id' => $request->user()?->id,
            'action' => $action,
            'ids' => $sections->pluck('id')->values()->all(),
            'affected' => $affected,
        ]);

        return $this->success([
            'message' => "{$affected} section(s) processed successfully.",
            'affected' => $affected,
        ]);
    }

    #[OA\Put(
        path: "/api/v1/admin/homepage/sections/reorder",
        operationId: "adminHomepageSectionsReorder",
        summary: "Reorder homepage sections",
        tags: ["Admin Homepage Sections"],
        security: [["sanctum" => []]],
        responses: [new OA\Response(response: 200, description: "Reordered")]
    )]
    public function reorder(ReorderSectionsRequest $request): JsonResponse
    {
        $items = $request->validated()['items'];

        DB::transaction(function () use ($items): void {
            foreach ($items as $item) {
                Section::query()
                    ->where('id', (int) $item['id'])
                    ->update(['sort_order' => (int) $item['sort_order']]);
            }
        });

        $ids = collect($items)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $sorted = Section::query()->whereIn('id', $ids)->orderBy('sort_order')->get();

        Log::info('admin.homepage.sections.reordered', [
            'actor_id' => $request->user()?->id,
            'ids' => $ids,
            'affected' => count($items),
        ]);

        return $this->success([
            'message' => 'Sections reordered successfully.',
            'affected' => count($items),
            'sections' => SectionResource::collection($sorted),
        ]);
    }
}
