<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\PublicPostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Public read-only endpoints for blog posts and pages.
 * Only serves published content — no auth required.
 */
#[OA\Tag(name: "Blog", description: "Public blog posts")]
#[OA\Tag(name: "Pages", description: "Public pages")]
class ContentController extends Controller
{
    private const EAGER_LOADS = ['author', 'meta', 'taxonomies.term'];

    // ─── Blog List ───────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/blog",
        operationId: "getPublicBlogPosts",
        summary: "List published blog posts",
        description: "Returns a paginated list of published blog posts. Supports filtering by category slug, tag slug, author, search, and month.",
        tags: ["Blog"],
        parameters: [
            new OA\Parameter(name: "category", in: "query", required: false, description: "Filter by category slug", schema: new OA\Schema(type: "string", example: "news")),
            new OA\Parameter(name: "tag", in: "query", required: false, description: "Filter by tag slug", schema: new OA\Schema(type: "string", example: "featured")),
            new OA\Parameter(name: "author", in: "query", required: false, description: "Filter by author ID", schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "month", in: "query", required: false, description: "Filter by month (YYYY-MM)", schema: new OA\Schema(type: "string", example: "2026-02")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 10)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated blog posts", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", type: "array", items: new OA\Items(ref: "#/components/schemas/PublicPost")),
            ])),
        ]
    )]
    public function blogIndex(Request $request): JsonResponse
    {
        $query = Post::query()
            ->with(self::EAGER_LOADS)
            ->ofType('post')
            ->published()
            ->notRevision()
            ->orderByDesc('post_date');

        // Category filter (by slug)
        if ($categorySlug = $request->query('category')) {
            $query->whereHas('taxonomies', function ($q) use ($categorySlug) {
                $q->where('taxonomy', 'category')
                    ->whereHas('term', fn($t) => $t->where('slug', $categorySlug));
            });
        }

        // Tag filter (by slug)
        if ($tagSlug = $request->query('tag')) {
            $query->whereHas('taxonomies', function ($q) use ($tagSlug) {
                $q->where('taxonomy', 'post_tag')
                    ->whereHas('term', fn($t) => $t->where('slug', $tagSlug));
            });
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

        // Month filter (YYYY-MM)
        if ($month = $request->query('month')) {
            $parts = explode('-', $month);
            if (count($parts) === 2) {
                $query->whereYear('post_date', $parts[0])
                    ->whereMonth('post_date', $parts[1]);
            }
        }

        $perPage = min((int) $request->query('per_page', 10), 50);

        return response()->json(
            PublicPostResource::collection($query->paginate($perPage))
        );
    }

    // ─── Blog Single ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/blog/{slug}",
        operationId: "getPublicBlogPost",
        summary: "Get a published blog post by slug",
        description: "Returns a single published blog post with full content, categories, tags, and author.",
        tags: ["Blog"],
        parameters: [
            new OA\Parameter(name: "slug", in: "path", required: true, description: "Post slug", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Blog post details", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", ref: "#/components/schemas/PublicPost"),
            ])),
            new OA\Response(response: 404, description: "Post not found"),
        ]
    )]
    public function blogShow(string $slug): JsonResponse
    {
        $post = Post::with(self::EAGER_LOADS)
            ->ofType('post')
            ->published()
            ->notRevision()
            ->where('slug', $slug)
            ->first();

        if (!$post) {
            return response()->json(['message' => 'Post not found.'], 404);
        }

        return response()->json([
            'data' => new PublicPostResource($post),
        ]);
    }

    // ─── Page Single ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/pages/{slug}",
        operationId: "getPublicPage",
        summary: "Get a published page by slug",
        description: "Returns a single published page with full content and author. Pages don't have categories or tags.",
        tags: ["Pages"],
        parameters: [
            new OA\Parameter(name: "slug", in: "path", required: true, description: "Page slug", schema: new OA\Schema(type: "string")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Page details", content: new OA\JsonContent(properties: [
                new OA\Property(property: "data", ref: "#/components/schemas/PublicPost"),
            ])),
            new OA\Response(response: 404, description: "Page not found"),
        ]
    )]
    public function pageShow(string $slug): JsonResponse
    {
        $page = Post::with(self::EAGER_LOADS)
            ->ofType('page')
            ->published()
            ->notRevision()
            ->where('slug', $slug)
            ->first();

        if (!$page) {
            return response()->json(['message' => 'Page not found.'], 404);
        }

        return response()->json([
            'data' => new PublicPostResource($page),
        ]);
    }
}
