<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\StoreFieldGroupRequest;
use App\Http\Requests\Admin\UpdateFieldGroupRequest;
use App\Http\Resources\V1\Admin\FieldGroupResource;
use App\Models\Post;
use App\Models\PostMeta;
use App\Services\FieldRenderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * ACF-style Custom Field Group CRUD.
 *
 * Field groups are stored as Post records with type='acf-field-group'.
 * Individual fields are stored as child Posts with type='acf-field'
 * (parent_id pointing to the group). Field properties are kept in post_meta.
 */
#[OA\Tag(name: "Admin Custom Fields", description: "ACF-style field group and field CRUD")]
class CustomFieldController extends ApiController
{
    public function __construct(
        private readonly FieldRenderService $fieldRenderService,
    ) {}

    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(path: "/api/v1/admin/field-groups", operationId: "listFieldGroups", summary: "List all field groups", tags: ["Admin Custom Fields"], security: [["sanctum" => []]], responses: [new OA\Response(response: 200, description: "Field group list")])]
    public function index(): JsonResponse
    {
        $groups = Post::where('type', 'acf-field-group')
            ->with(['meta', 'children.meta'])
            ->orderBy('menu_order')
            ->orderByDesc('post_date')
            ->get();

        return $this->success(FieldGroupResource::collection($groups));
    }

    // ─── Create ──────────────────────────────────────────────────

    #[OA\Post(path: "/api/v1/admin/field-groups", operationId: "createFieldGroup", summary: "Create a field group with fields", tags: ["Admin Custom Fields"], security: [["sanctum" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminFieldGroup")), responses: [new OA\Response(response: 201, description: "Created"), new OA\Response(response: 422, description: "Validation error")])]
    public function store(StoreFieldGroupRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $group = Post::create([
            'author_id' => $request->user()->id,
            'post_date' => now(),
            'post_date_gmt' => now()->utc(),
            'content' => '',
            'title' => $validated['title'],
            'excerpt' => '',
            'status' => $validated['status'] ?? 'publish',
            'comment_status' => 'closed',
            'ping_status' => 'closed',
            'password' => '',
            'slug' => Str::slug($validated['title']),
            'post_modified' => now(),
            'post_modified_gmt' => now()->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => $validated['menu_order'] ?? 0,
            'type' => 'acf-field-group',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        // Store group metadata
        $this->saveGroupMeta($group, $validated);

        // Create child fields
        if (!empty($validated['fields'])) {
            $this->syncFields($group, $validated['fields']);
        }

        $group->load(['meta', 'children.meta']);

        return $this->success(new FieldGroupResource($group), 201);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(path: "/api/v1/admin/field-groups/{id}", operationId: "showFieldGroup", summary: "Get a field group with its fields", tags: ["Admin Custom Fields"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Field group details"), new OA\Response(response: 404, description: "Not found")])]
    public function show(int $id): JsonResponse
    {
        $group = Post::where('type', 'acf-field-group')
            ->with(['meta', 'children.meta'])
            ->find($id);

        if (!$group) {
            return $this->error('Field group not found.', 404);
        }

        return $this->success(new FieldGroupResource($group));
    }

    // ─── Update ──────────────────────────────────────────────────

    #[OA\Put(path: "/api/v1/admin/field-groups/{id}", operationId: "updateFieldGroup", summary: "Update a field group and its fields", tags: ["Admin Custom Fields"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminFieldGroup")), responses: [new OA\Response(response: 200, description: "Updated"), new OA\Response(response: 404, description: "Not found")])]
    public function update(UpdateFieldGroupRequest $request, int $id): JsonResponse
    {
        $group = Post::where('type', 'acf-field-group')
            ->with(['meta', 'children.meta'])
            ->find($id);

        if (!$group) {
            return $this->error('Field group not found.', 404);
        }

        $validated = $request->validated();

        // Update post-level fields
        $postUpdates = ['post_modified' => now(), 'post_modified_gmt' => now()->utc()];

        if (isset($validated['title'])) {
            $postUpdates['title'] = $validated['title'];
        }
        if (isset($validated['status'])) {
            $postUpdates['status'] = $validated['status'];
        }
        if (isset($validated['menu_order'])) {
            $postUpdates['menu_order'] = $validated['menu_order'];
        }

        $group->update($postUpdates);

        // Update group metadata
        $this->saveGroupMeta($group, $validated);

        // Re-sync fields if provided
        if (isset($validated['fields'])) {
            $this->syncFields($group, $validated['fields']);
        }

        $group->refresh();
        $group->load(['meta', 'children.meta']);

        return $this->success(new FieldGroupResource($group));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(path: "/api/v1/admin/field-groups/{id}", operationId: "deleteFieldGroup", summary: "Delete a field group and its fields", tags: ["Admin Custom Fields"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Deleted"), new OA\Response(response: 404, description: "Not found")])]
    public function destroy(int $id): JsonResponse
    {
        $group = Post::where('type', 'acf-field-group')
            ->with('children')
            ->find($id);

        if (!$group) {
            return $this->error('Field group not found.', 404);
        }

        // Delete child fields and their meta
        foreach ($group->children as $field) {
            $field->meta()->delete();
            $field->delete();
        }

        // Delete group meta and record
        $group->meta()->delete();
        $group->delete();

        return $this->success(['message' => 'Field group deleted.']);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Save group-level metadata (position, style, label_placement, location_rules).
     */
    private function saveGroupMeta(Post $group, array $data): void
    {
        $metaKeys = ['position', 'style', 'label_placement'];

        foreach ($metaKeys as $key) {
            if (isset($data[$key])) {
                $group->meta()->updateOrCreate(
                    ['meta_key' => $key],
                    ['meta_value' => $data[$key]],
                );
            }
        }

        if (isset($data['location_rules'])) {
            $group->meta()->updateOrCreate(
                ['meta_key' => 'location_rules'],
                ['meta_value' => json_encode($data['location_rules'])],
            );
        }
    }

    /**
     * Replace all child fields with the provided set.
     *
     * Why full replacement? ACF's approach is to overwrite the complete
     * field list on save. This avoids complex diff logic and ensures
     * the group's fields are always in sync with the submitted payload.
     */
    private function syncFields(Post $group, array $fields): void
    {
        // Delete existing child fields
        foreach ($group->children()->where('type', 'acf-field')->get() as $existing) {
            $existing->meta()->delete();
            $existing->delete();
        }

        // Create new fields
        foreach ($fields as $order => $fieldData) {
            $field = Post::create([
                'author_id' => $group->author_id,
                'post_date' => now(),
                'post_date_gmt' => now()->utc(),
                'content' => '',
                'title' => $fieldData['label'],
                'excerpt' => '',
                'status' => 'publish',
                'comment_status' => 'closed',
                'ping_status' => 'closed',
                'password' => '',
                'slug' => $fieldData['name'],
                'post_modified' => now(),
                'post_modified_gmt' => now()->utc(),
                'content_filtered' => '',
                'parent_id' => $group->id,
                'guid' => '',
                'menu_order' => $order,
                'type' => 'acf-field',
                'mime_type' => '',
                'comment_count' => 0,
            ]);

            // Store field properties as meta
            $field->meta()->createMany([
                ['meta_key' => 'field_type', 'meta_value' => $fieldData['type']],
                ['meta_key' => 'instructions', 'meta_value' => $fieldData['instructions'] ?? ''],
                ['meta_key' => 'required', 'meta_value' => (string) ($fieldData['required'] ?? 0)],
                ['meta_key' => 'default_value', 'meta_value' => $fieldData['default_value'] ?? ''],
                ['meta_key' => 'options', 'meta_value' => json_encode($fieldData['options'] ?? [])],
            ]);
        }
    }
}
