<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkTermRequest;
use App\Http\Requests\Admin\StoreTermRequest;
use App\Http\Requests\Admin\UpdateTermRequest;
use App\Http\Resources\V1\Admin\TermCollection;
use App\Http\Resources\V1\Admin\TermResource;
use App\Models\Term;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use App\Services\OptionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Shared controller for Categories, Tags, and Custom Taxonomies.
 *
 * The taxonomy type is resolved from the route prefix or path parameter:
 * - /admin/categories → taxonomy = 'category'
 * - /admin/tags → taxonomy = 'post_tag'
 * - /admin/taxonomies/{taxonomy}/terms → taxonomy from parameter
 */
#[OA\Tag(name: "Admin Taxonomies", description: "Category, Tag, and Custom Taxonomy CRUD")]
class TaxonomyController extends ApiController
{
    public function __construct(
        private readonly OptionService $optionService
    ) {}

    /**
     * Resolve the taxonomy type from the current route.
     */
    private function resolveTaxonomy(Request $request): string
    {
        $prefix = $request->route()->getPrefix() ?? '';
        $taxonomy = $request->route('taxonomy');

        if ($taxonomy) {
            return $taxonomy;
        }

        if (str_contains($prefix, 'categories')) {
            return 'category';
        }

        if (str_contains($prefix, 'tags')) {
            return 'post_tag';
        }

        return 'category'; // Default
    }

    /**
     * Standard eager-load set.
     */
    private function eagerLoads(): array
    {
        return ['term', 'parentTerm.term'];
    }

    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/categories",
        operationId: "listCategories",
        summary: "List categories (paginated)",
        description: "Returns a paginated list of categories with optional filters.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "parent", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "hide_empty", in: "query", required: false, schema: new OA\Schema(type: "boolean", default: false)),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["name", "count", "id"], default: "name")),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "asc")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated term list"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);

        $query = TermTaxonomy::query()
            ->with($this->eagerLoads())
            ->where('taxonomy', $taxonomy);

        // Search filter
        if ($search = $request->query('search')) {
            $query->whereHas('term', fn($q) => $q->where('name', 'LIKE', "%{$search}%"));
        }

        // Parent filter (hierarchical taxonomies only)
        if ($request->has('parent')) {
            $query->where('parent', $request->query('parent'));
        }

        // Hide empty terms
        if ($request->boolean('hide_empty')) {
            $query->where('count', '>', 0);
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'name');
        $sortDir = $request->query('sort_dir', 'asc');

        if ($sortBy === 'name') {
            $query->join('terms', 'term_taxonomy.term_id', '=', 'terms.id')
                ->orderBy('terms.name', $sortDir)
                ->select('term_taxonomy.*');
        } elseif (in_array($sortBy, ['count', 'id'], true)) {
            $query->orderBy($sortBy, $sortDir);
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $collection = new TermCollection($paginator);
        $collection->taxonomy = $taxonomy;

        return $collection->response()->setStatusCode(200);
    }

    // ─── Create ──────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/categories",
        operationId: "createCategory",
        summary: "Create a new category",
        description: "Creates a new category/tag. Auto-generates slug from name if not provided.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["name"],
                properties: [
                    new OA\Property(property: "name", type: "string", example: "Technology"),
                    new OA\Property(property: "slug", type: "string", example: "technology"),
                    new OA\Property(property: "parent_id", type: "integer", nullable: true, example: null),
                    new OA\Property(property: "description", type: "string", example: "Tech-related posts"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Term created"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StoreTermRequest $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $validated = $request->validated();

        // Generate unique slug
        $slug = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['name'],
            $taxonomy
        );

        // Create term
        $term = Term::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'term_group' => 0,
        ]);

        // Create term taxonomy
        $termTaxonomy = TermTaxonomy::create([
            'term_id' => $term->id,
            'taxonomy' => $taxonomy,
            'description' => $validated['description'] ?? '',
            'parent' => $validated['parent_id'] ?? 0,
            'count' => 0,
        ]);

        $termTaxonomy->load($this->eagerLoads());

        return $this->success(new TermResource($termTaxonomy), 201);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/categories/{id}",
        operationId: "showCategory",
        summary: "Get a single category",
        description: "Returns a full category/tag with details.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Term details"),
            new OA\Response(response: 404, description: "Term not found"),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::with($this->eagerLoads())
            ->where('taxonomy', $taxonomy)
            ->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        return $this->success(new TermResource($termTaxonomy));
    }

    // ─── Update ──────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/categories/{id}",
        operationId: "updateCategory",
        summary: "Update a category",
        description: "Updates a category/tag.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "name", type: "string"),
                new OA\Property(property: "slug", type: "string"),
                new OA\Property(property: "parent_id", type: "integer", nullable: true),
                new OA\Property(property: "description", type: "string"),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Term updated"),
            new OA\Response(response: 404, description: "Term not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(UpdateTermRequest $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::with($this->eagerLoads())
            ->where('taxonomy', $taxonomy)
            ->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        $validated = $request->validated();

        // Update term (name, slug)
        if (isset($validated['name']) || isset($validated['slug'])) {
            $termUpdates = [];

            if (isset($validated['name'])) {
                $termUpdates['name'] = $validated['name'];
            }

            if (isset($validated['slug'])) {
                $termUpdates['slug'] = $this->generateUniqueSlug(
                    $validated['slug'],
                    $taxonomy,
                    $termTaxonomy->term_id
                );
            }

            if (!empty($termUpdates)) {
                $termTaxonomy->term->update($termUpdates);
            }
        }

        // Update term taxonomy (description, parent)
        $taxonomyUpdates = [];

        if (array_key_exists('description', $validated)) {
            $taxonomyUpdates['description'] = $validated['description'];
        }

        if (array_key_exists('parent_id', $validated)) {
            // Prevent circular parent relationship
            if ($validated['parent_id'] == $termTaxonomy->id) {
                return $this->error('A term cannot be its own parent.', 422);
            }
            $taxonomyUpdates['parent'] = $validated['parent_id'] ?? 0;
        }

        if (!empty($taxonomyUpdates)) {
            $termTaxonomy->update($taxonomyUpdates);
        }

        $termTaxonomy->refresh();
        $termTaxonomy->load($this->eagerLoads());

        return $this->success(new TermResource($termTaxonomy));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/categories/{id}",
        operationId: "deleteCategory",
        summary: "Delete a category",
        description: "Deletes a category/tag. Cannot delete the default category. Only removes relationships (posts are not deleted).",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Term deleted"),
            new OA\Response(response: 404, description: "Term not found"),
            new OA\Response(response: 422, description: "Cannot delete default category"),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $termTaxonomy = TermTaxonomy::where('taxonomy', $taxonomy)->find($id);

        if (!$termTaxonomy) {
            return $this->error('Term not found.', 404);
        }

        // Prevent deleting default category
        if ($taxonomy === 'category') {
            $defaultCategoryId = $this->optionService->get('default_category', 1);
            if ($termTaxonomy->id == $defaultCategoryId) {
                return $this->error('Cannot delete the default category.', 422);
            }
        }

        // Delete relationships
        TermRelationship::where('term_taxonomy_id', $termTaxonomy->id)->delete();

        // Delete term taxonomy
        $termTaxonomy->delete();

        // Delete term if no other taxonomies reference it
        $remainingTaxonomies = TermTaxonomy::where('term_id', $termTaxonomy->term_id)->count();
        if ($remainingTaxonomies === 0) {
            Term::where('id', $termTaxonomy->term_id)->delete();
        }

        return $this->success(['message' => 'Term deleted successfully.']);
    }

    // ─── Bulk ────────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/categories/bulk",
        operationId: "bulkCategoryAction",
        summary: "Perform bulk term action",
        description: "Supports action: delete. Removes selected terms and their relationships.",
        tags: ["Admin Taxonomies"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["action", "term_ids"],
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["delete"]),
                new OA\Property(property: "term_ids", type: "array", items: new OA\Items(type: "integer")),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function bulk(BulkTermRequest $request): JsonResponse
    {
        $taxonomy = $this->resolveTaxonomy($request);
        $validated = $request->validated();
        $termIds = $validated['term_ids'];

        // Prevent bulk-deleting default category
        if ($taxonomy === 'category') {
            $defaultCategoryId = $this->optionService->get('default_category', 1);
            if (in_array($defaultCategoryId, $termIds, false)) {
                return $this->error('Cannot delete the default category.', 422);
            }
        }

        $terms = TermTaxonomy::where('taxonomy', $taxonomy)
            ->whereIn('id', $termIds)
            ->get();

        if ($terms->isEmpty()) {
            return $this->error('No valid terms found.', 422);
        }

        $affected = $terms->count();

        foreach ($terms as $termTaxonomy) {
            // Delete relationships
            TermRelationship::where('term_taxonomy_id', $termTaxonomy->id)->delete();

            // Delete term taxonomy
            $termTaxonomy->delete();

            // Delete term if no other taxonomies reference it
            $remainingTaxonomies = TermTaxonomy::where('term_id', $termTaxonomy->term_id)->count();
            if ($remainingTaxonomies === 0) {
                Term::where('id', $termTaxonomy->term_id)->delete();
            }
        }

        return $this->success([
            'message' => "{$affected} term(s) deleted successfully.",
            'affected' => $affected,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate a unique slug within the taxonomy.
     * Appends -2, -3, etc. if a collision is found.
     */
    private function generateUniqueSlug(string $text, string $taxonomy, ?int $excludeTermId = null): string
    {
        $baseSlug = Str::slug($text) ?: 'untitled';
        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            // Check if slug exists in any term used by this taxonomy
            $query = TermTaxonomy::where('taxonomy', $taxonomy)
                ->whereHas('term', fn($q) => $q->where('slug', $slug));

            if ($excludeTermId) {
                $query->where('term_id', '!=', $excludeTermId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }
}
