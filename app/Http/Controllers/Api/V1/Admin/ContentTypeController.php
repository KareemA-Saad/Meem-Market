<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\StoreContentTypeRequest;
use App\Http\Requests\Admin\UpdateContentTypeRequest;
use App\Http\Resources\V1\Admin\ContentTypeResource;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * CPTUI-style Custom Post Type and Taxonomy definition management.
 *
 * Definitions are stored as JSON arrays in the `options` table under:
 * - `cptui_post_types` for custom post types
 * - `cptui_taxonomies` for custom taxonomies
 *
 * The ContentTypeServiceProvider reads these at boot to register dynamic routes.
 */
#[OA\Tag(name: "Admin Content Types", description: "Custom Post Type and Taxonomy definition CRUD")]
class ContentTypeController extends ApiController
{
    /** Reserved slugs that must not be overridden. */
    private const RESERVED_POST_TYPE_SLUGS = [
        'post',
        'page',
        'attachment',
        'revision',
        'nav_menu_item',
        'acf-field-group',
        'acf-field',
    ];

    private const RESERVED_TAXONOMY_SLUGS = [
        'category',
        'post_tag',
        'nav_menu',
    ];

    public function __construct(
        private readonly OptionService $optionService,
    ) {}

    // ═══════════════════════════════════════════════════════════
    //  Custom Post Types
    // ═══════════════════════════════════════════════════════════

    #[OA\Get(path: "/api/v1/admin/content-types/post-types", operationId: "listPostTypes", summary: "List all custom post types", tags: ["Admin Content Types"], security: [["sanctum" => []]], responses: [new OA\Response(response: 200, description: "List of custom post types")])]
    public function indexPostTypes(): JsonResponse
    {
        $types = $this->getStoredPostTypes();

        return $this->success(ContentTypeResource::collection(array_values($types)));
    }

    #[OA\Post(path: "/api/v1/admin/content-types/post-types", operationId: "createPostType", summary: "Register a custom post type", tags: ["Admin Content Types"], security: [["sanctum" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminContentType")), responses: [new OA\Response(response: 201, description: "Created"), new OA\Response(response: 422, description: "Validation error")])]
    public function storePostType(StoreContentTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (in_array($validated['slug'], self::RESERVED_POST_TYPE_SLUGS, true)) {
            return $this->error("The slug '{$validated['slug']}' is reserved.", 422);
        }

        $types = $this->getStoredPostTypes();

        if (isset($types[$validated['slug']])) {
            return $this->error("A post type with slug '{$validated['slug']}' already exists.", 422);
        }

        $definition = $this->buildDefinition($validated);
        $types[$validated['slug']] = $definition;
        $this->savePostTypes($types);

        return $this->success(new ContentTypeResource($definition), 201);
    }

    #[OA\Get(path: "/api/v1/admin/content-types/post-types/{slug}", operationId: "showPostType", summary: "Get a custom post type", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], responses: [new OA\Response(response: 200, description: "Post type details"), new OA\Response(response: 404, description: "Not found")])]
    public function showPostType(string $slug): JsonResponse
    {
        $types = $this->getStoredPostTypes();

        if (!isset($types[$slug])) {
            return $this->error('Post type not found.', 404);
        }

        return $this->success(new ContentTypeResource($types[$slug]));
    }

    #[OA\Put(path: "/api/v1/admin/content-types/post-types/{slug}", operationId: "updatePostType", summary: "Update a custom post type", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminContentType")), responses: [new OA\Response(response: 200, description: "Updated"), new OA\Response(response: 404, description: "Not found")])]
    public function updatePostType(UpdateContentTypeRequest $request, string $slug): JsonResponse
    {
        $types = $this->getStoredPostTypes();

        if (!isset($types[$slug])) {
            return $this->error('Post type not found.', 404);
        }

        $validated = $request->validated();
        $types[$slug] = array_merge($types[$slug], $validated);
        $this->savePostTypes($types);

        return $this->success(new ContentTypeResource($types[$slug]));
    }

    #[OA\Delete(path: "/api/v1/admin/content-types/post-types/{slug}", operationId: "deletePostType", summary: "Delete a custom post type", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], responses: [new OA\Response(response: 200, description: "Deleted"), new OA\Response(response: 404, description: "Not found")])]
    public function destroyPostType(string $slug): JsonResponse
    {
        $types = $this->getStoredPostTypes();

        if (!isset($types[$slug])) {
            return $this->error('Post type not found.', 404);
        }

        unset($types[$slug]);
        $this->savePostTypes($types);

        return $this->success(['message' => "Post type '{$slug}' deleted."]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Custom Taxonomies
    // ═══════════════════════════════════════════════════════════

    #[OA\Get(path: "/api/v1/admin/content-types/taxonomies", operationId: "listCustomTaxonomies", summary: "List all custom taxonomy definitions", tags: ["Admin Content Types"], security: [["sanctum" => []]], responses: [new OA\Response(response: 200, description: "List of custom taxonomies")])]
    public function indexTaxonomies(): JsonResponse
    {
        $taxonomies = $this->getStoredTaxonomies();

        return $this->success(ContentTypeResource::collection(array_values($taxonomies)));
    }

    #[OA\Post(path: "/api/v1/admin/content-types/taxonomies", operationId: "createCustomTaxonomy", summary: "Register a custom taxonomy", tags: ["Admin Content Types"], security: [["sanctum" => []]], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminContentType")), responses: [new OA\Response(response: 201, description: "Created"), new OA\Response(response: 422, description: "Validation error")])]
    public function storeTaxonomy(StoreContentTypeRequest $request): JsonResponse
    {
        $validated = $request->validated();

        if (in_array($validated['slug'], self::RESERVED_TAXONOMY_SLUGS, true)) {
            return $this->error("The slug '{$validated['slug']}' is reserved.", 422);
        }

        $taxonomies = $this->getStoredTaxonomies();

        if (isset($taxonomies[$validated['slug']])) {
            return $this->error("A taxonomy with slug '{$validated['slug']}' already exists.", 422);
        }

        $definition = $this->buildDefinition($validated);
        $taxonomies[$validated['slug']] = $definition;
        $this->saveTaxonomies($taxonomies);

        return $this->success(new ContentTypeResource($definition), 201);
    }

    #[OA\Get(path: "/api/v1/admin/content-types/taxonomies/{slug}", operationId: "showCustomTaxonomy", summary: "Get a custom taxonomy definition", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], responses: [new OA\Response(response: 200, description: "Taxonomy details"), new OA\Response(response: 404, description: "Not found")])]
    public function showTaxonomy(string $slug): JsonResponse
    {
        $taxonomies = $this->getStoredTaxonomies();

        if (!isset($taxonomies[$slug])) {
            return $this->error('Taxonomy not found.', 404);
        }

        return $this->success(new ContentTypeResource($taxonomies[$slug]));
    }

    #[OA\Put(path: "/api/v1/admin/content-types/taxonomies/{slug}", operationId: "updateCustomTaxonomy", summary: "Update a custom taxonomy", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(ref: "#/components/schemas/AdminContentType")), responses: [new OA\Response(response: 200, description: "Updated"), new OA\Response(response: 404, description: "Not found")])]
    public function updateTaxonomy(UpdateContentTypeRequest $request, string $slug): JsonResponse
    {
        $taxonomies = $this->getStoredTaxonomies();

        if (!isset($taxonomies[$slug])) {
            return $this->error('Taxonomy not found.', 404);
        }

        $validated = $request->validated();
        $taxonomies[$slug] = array_merge($taxonomies[$slug], $validated);
        $this->saveTaxonomies($taxonomies);

        return $this->success(new ContentTypeResource($taxonomies[$slug]));
    }

    #[OA\Delete(path: "/api/v1/admin/content-types/taxonomies/{slug}", operationId: "deleteCustomTaxonomy", summary: "Delete a custom taxonomy", tags: ["Admin Content Types"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))], responses: [new OA\Response(response: 200, description: "Deleted"), new OA\Response(response: 404, description: "Not found")])]
    public function destroyTaxonomy(string $slug): JsonResponse
    {
        $taxonomies = $this->getStoredTaxonomies();

        if (!isset($taxonomies[$slug])) {
            return $this->error('Taxonomy not found.', 404);
        }

        unset($taxonomies[$slug]);
        $this->saveTaxonomies($taxonomies);

        return $this->success(['message' => "Taxonomy '{$slug}' deleted."]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    private function getStoredPostTypes(): array
    {
        $raw = $this->optionService->get('cptui_post_types', '{}');

        return json_decode($raw, true) ?: [];
    }

    private function savePostTypes(array $types): void
    {
        $this->optionService->set('cptui_post_types', $types);
    }

    private function getStoredTaxonomies(): array
    {
        $raw = $this->optionService->get('cptui_taxonomies', '{}');

        return json_decode($raw, true) ?: [];
    }

    private function saveTaxonomies(array $taxonomies): void
    {
        $this->optionService->set('cptui_taxonomies', $taxonomies);
    }

    /**
     * Normalise validated input into a full definition array with defaults.
     */
    private function buildDefinition(array $validated): array
    {
        return [
            'slug' => $validated['slug'],
            'label' => $validated['label'],
            'singular_label' => $validated['singular_label'] ?? $validated['label'],
            'labels' => $validated['labels'] ?? [],
            'public' => $validated['public'] ?? true,
            'show_ui' => $validated['show_ui'] ?? true,
            'has_archive' => $validated['has_archive'] ?? false,
            'hierarchical' => $validated['hierarchical'] ?? false,
            'supports' => $validated['supports'] ?? ['title', 'editor'],
            'taxonomies' => $validated['taxonomies'] ?? [],
            'menu_icon' => $validated['menu_icon'] ?? null,
            'menu_position' => $validated['menu_position'] ?? null,
        ];
    }
}
