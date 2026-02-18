<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkPostRequest;
use App\Http\Requests\Admin\StorePostRequest;
use App\Http\Requests\Admin\UpdatePostRequest;
use App\Http\Resources\V1\Admin\PostCollection;
use App\Http\Resources\V1\Admin\PostResource;
use App\Http\Resources\V1\Admin\RevisionResource;
use App\Models\Comment;
use App\Models\CommentMeta;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Shared controller for Posts and Pages.
 *
 * The content type is resolved from the route prefix:
 * - /admin/posts → type = 'post'
 * - /admin/pages → type = 'page'
 */
#[OA\Tag(name: "Admin Posts", description: "Post & Page CRUD, revisions, trash, bulk operations")]
class PostController extends ApiController
{
    /**
     * Resolve the content type from the current route prefix.
     */
    private function resolveType(Request $request): string
    {
        // Route prefix ends with 'pages' → type = 'page', otherwise 'post'
        $prefix = $request->route()->getPrefix() ?? '';

        return str_contains($prefix, 'pages') ? 'page' : 'post';
    }

    /**
     * Standard eager-load set used by most endpoints.
     */
    private function eagerLoads(): array
    {
        return ['author', 'meta', 'taxonomies.term'];
    }

    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/posts",
        operationId: "listPosts",
        summary: "List posts (paginated)",
        description: "Returns a paginated list of posts with status count metadata. Supports filters: status, category, tag, author, search, month, sorting.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "category", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "tag", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "author", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "month", in: "query", required: false, schema: new OA\Schema(type: "string", example: "2026-02")),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["title", "post_date", "post_modified", "id"], default: "post_date")),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "desc")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated post list with status counts"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $type = $this->resolveType($request);

        $query = Post::query()
            ->with($this->eagerLoads())
            ->ofType($type)
            ->notRevision();

        // Status filter — default to everything except trash
        if ($status = $request->query('status')) {
            $query->ofStatus($status);
        } else {
            $query->where('status', '!=', 'trash');
        }

        // Category filter (posts only)
        if ($type === 'post' && ($categoryId = $request->query('category'))) {
            $query->whereHas('taxonomies', fn($q) => $q->where('term_taxonomy.id', $categoryId));
        }

        // Tag filter (posts only)
        if ($type === 'post' && ($tagId = $request->query('tag'))) {
            $query->whereHas('taxonomies', fn($q) => $q->where('term_taxonomy.id', $tagId));
        }

        // Author filter
        if ($authorId = $request->query('author')) {
            $query->where('author_id', $authorId);
        }

        // Search
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('content', 'LIKE', "%{$search}%");
            });
        }

        // Month filter (format: YYYY-MM)
        if ($month = $request->query('month')) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $query->whereYear('post_date', $parts[0])
                    ->whereMonth('post_date', $parts[1]);
            }
        }

        // Parent filter (pages only)
        if ($type === 'page' && $request->has('parent')) {
            $query->where('parent_id', $request->query('parent'));
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'post_date');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['title', 'post_date', 'post_modified', 'id'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $collection = new PostCollection($paginator);
        $collection->postType = $type;

        return $collection->response()->setStatusCode(200);
    }

    // ─── Create ──────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/posts",
        operationId: "createPost",
        summary: "Create a new post",
        description: "Creates a new post or page. Auto-generates slug from title if not provided. Syncs category and tag relationships.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "My First Post"),
                    new OA\Property(property: "content", type: "string"),
                    new OA\Property(property: "excerpt", type: "string"),
                    new OA\Property(property: "status", type: "string", enum: ["publish", "draft", "pending", "private", "future"], example: "draft"),
                    new OA\Property(property: "slug", type: "string"),
                    new OA\Property(property: "password", type: "string"),
                    new OA\Property(property: "categories", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "integer")),
                    new OA\Property(property: "featured_image_id", type: "integer", nullable: true),
                    new OA\Property(property: "menu_order", type: "integer"),
                    new OA\Property(property: "author_id", type: "integer"),
                    new OA\Property(property: "scheduled_at", type: "string", format: "date-time"),
                    new OA\Property(property: "parent_id", type: "integer", nullable: true, description: "Pages only"),
                    new OA\Property(property: "template", type: "string", description: "Pages only"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Post created", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", ref: "#/components/schemas/AdminPost"),
            ])),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StorePostRequest $request): JsonResponse
    {
        $type = $this->resolveType($request);
        $validated = $request->validated();
        $now = now();
        $nowGmt = now('UTC');

        $status = $validated['status'] ?? 'draft';

        // Handle scheduled posts
        if ($status === 'future' && isset($validated['scheduled_at'])) {
            $postDate = $validated['scheduled_at'];
        } else {
            $postDate = ($status === 'publish') ? $now : null;
        }

        $slug = $this->generateUniqueSlug(
            $validated['slug'] ?? $validated['title'],
            $type,
        );

        $post = Post::create([
            'author_id' => $validated['author_id'] ?? $request->user()->id,
            'post_date' => $postDate,
            'post_date_gmt' => $postDate ? (clone $postDate)->setTimezone('UTC') : null,
            'content' => $validated['content'] ?? '',
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'] ?? '',
            'status' => $status,
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => $validated['password'] ?? '',
            'slug' => $slug,
            'post_modified' => $now,
            'post_modified_gmt' => $nowGmt,
            'content_filtered' => '',
            'parent_id' => $validated['parent_id'] ?? 0,
            'guid' => '',
            'menu_order' => $validated['menu_order'] ?? 0,
            'type' => $type,
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        // Set GUID after creation (uses post ID)
        $post->update(['guid' => url("/api/v1/admin/posts/{$post->id}")]);

        // Featured image
        if (isset($validated['featured_image_id'])) {
            PostMeta::create([
                'post_id' => $post->id,
                'meta_key' => '_thumbnail_id',
                'meta_value' => $validated['featured_image_id'],
            ]);
        }

        // Page template
        if ($type === 'page' && isset($validated['template'])) {
            PostMeta::create([
                'post_id' => $post->id,
                'meta_key' => '_wp_page_template',
                'meta_value' => $validated['template'],
            ]);
        }

        // Sync taxonomies (posts only — categories and tags)
        if ($type === 'post') {
            $this->syncTaxonomies($post, $validated);
        }

        $post->load($this->eagerLoads());

        return $this->success(new PostResource($post), 201);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/posts/{id}",
        operationId: "showPost",
        summary: "Get a single post",
        description: "Returns a full post with meta, categories, tags, featured image, and author.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Post details", content: new OA\JsonContent(properties: [
                new OA\Property(property: "success", type: "boolean", example: true),
                new OA\Property(property: "data", ref: "#/components/schemas/AdminPost"),
            ])),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function show(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::with($this->eagerLoads())->ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        return $this->success(new PostResource($post));
    }

    // ─── Update ──────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/posts/{id}",
        operationId: "updatePost",
        summary: "Update a post",
        description: "Updates a post. Creates a revision snapshot before saving changes.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "title", type: "string"),
                new OA\Property(property: "content", type: "string"),
                new OA\Property(property: "excerpt", type: "string"),
                new OA\Property(property: "status", type: "string"),
                new OA\Property(property: "slug", type: "string"),
                new OA\Property(property: "categories", type: "array", items: new OA\Items(type: "integer")),
                new OA\Property(property: "tags", type: "array", items: new OA\Items(type: "integer")),
                new OA\Property(property: "featured_image_id", type: "integer", nullable: true),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Post updated"),
            new OA\Response(response: 404, description: "Post not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(UpdatePostRequest $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::with($this->eagerLoads())->ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        $validated = $request->validated();

        // Create revision snapshot before changing anything
        $this->createRevision($post);

        $now = now();
        $updates = [
            'post_modified' => $now,
            'post_modified_gmt' => now('UTC'),
        ];

        // Map validated fields to model columns
        $directFields = ['title', 'content', 'excerpt', 'status', 'password', 'menu_order', 'author_id', 'parent_id'];
        foreach ($directFields as $field) {
            if (array_key_exists($field, $validated)) {
                $updates[$field] = $validated[$field];
            }
        }

        // Slug update — ensure uniqueness
        if (isset($validated['slug'])) {
            $updates['slug'] = $this->generateUniqueSlug($validated['slug'], $type, $post->id);
        }

        // Set post_date on first publish
        if (isset($validated['status']) && $validated['status'] === 'publish' && !$post->post_date) {
            $updates['post_date'] = $now;
            $updates['post_date_gmt'] = now('UTC');
        }

        // Handle scheduled posts
        if (isset($validated['status']) && $validated['status'] === 'future' && isset($validated['scheduled_at'])) {
            $updates['post_date'] = $validated['scheduled_at'];
            $updates['post_date_gmt'] = (clone $now)->setTimezone('UTC');
        }

        $post->update($updates);

        // Featured image
        if (array_key_exists('featured_image_id', $validated)) {
            PostMeta::updateOrCreate(
                ['post_id' => $post->id, 'meta_key' => '_thumbnail_id'],
                ['meta_value' => $validated['featured_image_id']],
            );
        }

        // Page template
        if ($type === 'page' && array_key_exists('template', $validated)) {
            PostMeta::updateOrCreate(
                ['post_id' => $post->id, 'meta_key' => '_wp_page_template'],
                ['meta_value' => $validated['template']],
            );
        }

        // Sync taxonomies
        if ($type === 'post') {
            $this->syncTaxonomies($post, $validated);
        }

        $post->refresh();
        $post->load($this->eagerLoads());

        return $this->success(new PostResource($post));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/posts/{id}",
        operationId: "deletePost",
        summary: "Delete a post",
        description: "Moves the post to trash (default) or permanently deletes it (?force=true). Permanent delete cascades: post_meta, term_relationships, comments + comment_meta.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "force", in: "query", required: false, schema: new OA\Schema(type: "boolean", default: false)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Post deleted or trashed"),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        if ($request->boolean('force')) {
            $this->permanentlyDelete($post);
            return $this->success(['message' => 'Post permanently deleted.']);
        }

        // Soft delete → move to trash
        $this->trashPost($post);

        return $this->success(['message' => 'Post moved to trash.']);
    }

    // ─── Trash ───────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/posts/{id}/trash",
        operationId: "trashPost",
        summary: "Move post to trash",
        description: "Stores the current status in post_meta and sets status to 'trash'.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Post trashed"),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function trash(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        if ($post->status === 'trash') {
            return $this->error('Post is already in trash.', 422);
        }

        $this->trashPost($post);

        return $this->success(['message' => 'Post moved to trash.']);
    }

    // ─── Restore ─────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/posts/{id}/restore",
        operationId: "restorePost",
        summary: "Restore post from trash",
        description: "Reads the previously stored status from post_meta and restores it.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Post restored"),
            new OA\Response(response: 404, description: "Post not found"),
            new OA\Response(response: 422, description: "Post is not in trash"),
        ]
    )]
    public function restore(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        if ($post->status !== 'trash') {
            return $this->error('Post is not in trash.', 422);
        }

        $this->restorePost($post);

        $post->load($this->eagerLoads());

        return $this->success(new PostResource($post));
    }

    // ─── Bulk ────────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/posts/bulk",
        operationId: "bulkPostAction",
        summary: "Perform bulk post action",
        description: "Supports actions: trash, restore, delete (permanent), edit (quick-edit fields).",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["action", "post_ids"],
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["trash", "restore", "delete", "edit"]),
                new OA\Property(property: "post_ids", type: "array", items: new OA\Items(type: "integer")),
                new OA\Property(property: "data", type: "object", properties: [
                    new OA\Property(property: "status", type: "string"),
                    new OA\Property(property: "category", type: "integer"),
                    new OA\Property(property: "tag", type: "integer"),
                ]),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function bulk(BulkPostRequest $request): JsonResponse
    {
        $type = $this->resolveType($request);
        $validated = $request->validated();
        $action = $validated['action'];
        $postIds = $validated['post_ids'];

        $posts = Post::ofType($type)->notRevision()->whereIn('id', $postIds)->get();

        if ($posts->isEmpty()) {
            return $this->error('No valid posts found.', 422);
        }

        $affected = $posts->count();

        return match ($action) {
            'trash' => $this->bulkTrash($posts, $affected),
            'restore' => $this->bulkRestore($posts, $affected),
            'delete' => $this->bulkDelete($posts, $affected),
            'edit' => $this->bulkEdit($posts, $validated['data'] ?? [], $affected),
        };
    }

    // ─── List Revisions ──────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/posts/{id}/revisions",
        operationId: "listPostRevisions",
        summary: "List post revisions",
        description: "Returns all revision snapshots for a post.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Revision list"),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function listRevisions(Request $request, int $id): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        $revisions = $post->revisions()->with('author')->get();

        return $this->success(RevisionResource::collection($revisions));
    }

    // ─── Restore Revision ────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/posts/{id}/revisions/{revisionId}/restore",
        operationId: "restorePostRevision",
        summary: "Restore a revision",
        description: "Replaces the current post content with the revision's content. Creates a new revision of the current state first.",
        tags: ["Admin Posts"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "revisionId", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Revision restored"),
            new OA\Response(response: 404, description: "Post or revision not found"),
        ]
    )]
    public function restoreRevision(Request $request, int $id, int $revisionId): JsonResponse
    {
        $type = $this->resolveType($request);
        $post = Post::ofType($type)->notRevision()->find($id);

        if (!$post) {
            return $this->error('Post not found.', 404);
        }

        $revision = Post::where('id', $revisionId)
            ->where('parent_id', $post->id)
            ->where('type', 'revision')
            ->first();

        if (!$revision) {
            return $this->error('Revision not found.', 404);
        }

        // Snapshot current state before restoring
        $this->createRevision($post);

        // Restore content from revision
        $post->update([
            'title' => $revision->title,
            'content' => $revision->content,
            'excerpt' => $revision->excerpt,
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ]);

        $post->load($this->eagerLoads());

        return $this->success(new PostResource($post));
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Generate a unique slug for the given type.
     * Appends -2, -3, etc. if a collision is found.
     */
    private function generateUniqueSlug(string $text, string $type, ?int $excludeId = null): string
    {
        $baseSlug = Str::slug($text) ?: 'untitled';
        $slug = $baseSlug;
        $counter = 2;

        while (true) {
            $query = Post::ofType($type)->where('slug', $slug);

            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if (!$query->exists()) {
                break;
            }

            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    /**
     * Create a revision snapshot of the current post state.
     */
    private function createRevision(Post $post): void
    {
        Post::create([
            'author_id' => $post->author_id,
            'post_date' => $post->post_date,
            'post_date_gmt' => $post->post_date_gmt,
            'content' => $post->content,
            'title' => $post->title,
            'excerpt' => $post->excerpt,
            'status' => 'inherit',
            'comment_status' => $post->comment_status,
            'ping_status' => $post->ping_status,
            'password' => $post->password,
            'slug' => "{$post->slug}-revision-v1",
            'post_modified' => $post->post_modified,
            'post_modified_gmt' => $post->post_modified_gmt,
            'content_filtered' => '',
            'parent_id' => $post->id,
            'guid' => '',
            'menu_order' => $post->menu_order,
            'type' => 'revision',
            'mime_type' => '',
            'comment_count' => 0,
        ]);
    }

    /**
     * Move a post to trash, storing old status in meta.
     */
    private function trashPost(Post $post): void
    {
        PostMeta::updateOrCreate(
            ['post_id' => $post->id, 'meta_key' => '_wp_trash_meta_status'],
            ['meta_value' => $post->status],
        );

        $post->update([
            'status' => 'trash',
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ]);
    }

    /**
     * Restore a post from trash using stored status.
     */
    private function restorePost(Post $post): void
    {
        $trashMeta = PostMeta::where('post_id', $post->id)
            ->where('meta_key', '_wp_trash_meta_status')
            ->first();

        $restoredStatus = $trashMeta?->meta_value ?: 'draft';

        $post->update([
            'status' => $restoredStatus,
            'post_modified' => now(),
            'post_modified_gmt' => now('UTC'),
        ]);

        // Clean up the trash meta
        PostMeta::where('post_id', $post->id)
            ->where('meta_key', '_wp_trash_meta_status')
            ->delete();
    }

    /**
     * Permanently delete a post and cascade all related records.
     */
    private function permanentlyDelete(Post $post): void
    {
        // Delete revisions
        Post::where('parent_id', $post->id)->where('type', 'revision')->delete();

        // Delete post meta
        PostMeta::where('post_id', $post->id)->delete();

        // Delete term relationships
        TermRelationship::where('object_id', $post->id)->delete();

        // Delete comments and their meta
        $commentIds = Comment::where('post_id', $post->id)->pluck('id');
        if ($commentIds->isNotEmpty()) {
            CommentMeta::whereIn('comment_id', $commentIds)->delete();
            Comment::where('post_id', $post->id)->delete();
        }

        $post->delete();
    }

    /**
     * Sync category and tag relationships for a post.
     */
    private function syncTaxonomies(Post $post, array $validated): void
    {
        $taxonomyIds = [];

        if (isset($validated['categories'])) {
            $taxonomyIds = array_merge($taxonomyIds, $validated['categories']);
        }

        if (isset($validated['tags'])) {
            $taxonomyIds = array_merge($taxonomyIds, $validated['tags']);
        }

        // Only sync if categories or tags were provided in the request
        if (isset($validated['categories']) || isset($validated['tags'])) {
            $post->taxonomies()->sync($taxonomyIds);

            // Update counts on term_taxonomy records
            $this->updateTaxonomyCounts($taxonomyIds);
        }
    }

    /**
     * Update the `count` column on term_taxonomy for the given IDs.
     */
    private function updateTaxonomyCounts(array $taxonomyIds): void
    {
        foreach ($taxonomyIds as $ttId) {
            $count = TermRelationship::where('term_taxonomy_id', $ttId)->count();
            TermTaxonomy::where('id', $ttId)->update(['count' => $count]);
        }
    }

    // ─── Bulk Helpers ────────────────────────────────────────────

    private function bulkTrash($posts, int $affected): JsonResponse
    {
        foreach ($posts as $post) {
            if ($post->status !== 'trash') {
                $this->trashPost($post);
            }
        }

        return $this->success([
            'message' => "{$affected} post(s) moved to trash.",
            'affected' => $affected,
        ]);
    }

    private function bulkRestore($posts, int $affected): JsonResponse
    {
        foreach ($posts as $post) {
            if ($post->status === 'trash') {
                $this->restorePost($post);
            }
        }

        return $this->success([
            'message' => "{$affected} post(s) restored.",
            'affected' => $affected,
        ]);
    }

    private function bulkDelete($posts, int $affected): JsonResponse
    {
        foreach ($posts as $post) {
            $this->permanentlyDelete($post);
        }

        return $this->success([
            'message' => "{$affected} post(s) permanently deleted.",
            'affected' => $affected,
        ]);
    }

    private function bulkEdit($posts, array $data, int $affected): JsonResponse
    {
        foreach ($posts as $post) {
            if (isset($data['status'])) {
                $post->update(['status' => $data['status']]);
            }

            // Attach category (additive, not replacing)
            if (isset($data['category'])) {
                $post->taxonomies()->syncWithoutDetaching([$data['category']]);
                $this->updateTaxonomyCounts([$data['category']]);
            }

            // Attach tag (additive)
            if (isset($data['tag'])) {
                $post->taxonomies()->syncWithoutDetaching([$data['tag']]);
                $this->updateTaxonomyCounts([$data['tag']]);
            }
        }

        return $this->success([
            'message' => "{$affected} post(s) updated.",
            'affected' => $affected,
        ]);
    }
}
