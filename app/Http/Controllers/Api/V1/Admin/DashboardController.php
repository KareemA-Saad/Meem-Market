<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Dashboard stats and quick-draft endpoints.
 * Mirrors the WP admin dashboard widgets (At a Glance, Quick Draft, Activity).
 */
#[OA\Tag(name: "Admin Dashboard", description: "Dashboard statistics and quick actions")]
class DashboardController extends ApiController
{
    // ─── Stats ───────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/dashboard/stats",
        operationId: "getDashboardStats",
        summary: "Get dashboard statistics",
        description: "Returns content counts, recent posts, recent comments, and recent drafts. Requires 'read' capability.",
        tags: ["Admin Dashboard"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Dashboard statistics",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "posts_count", type: "integer", example: 12),
                                new OA\Property(property: "pages_count", type: "integer", example: 5),
                                new OA\Property(property: "comments_count", type: "integer", example: 23),
                                new OA\Property(property: "comments_pending", type: "integer", example: 3),
                                new OA\Property(
                                    property: "recent_posts",
                                    type: "array",
                                    items: new OA\Items(type: "object")
                                ),
                                new OA\Property(
                                    property: "recent_comments",
                                    type: "array",
                                    items: new OA\Items(type: "object")
                                ),
                                new OA\Property(
                                    property: "recent_drafts",
                                    type: "array",
                                    items: new OA\Items(type: "object")
                                ),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function stats(): JsonResponse
    {
        return $this->success([
            'posts_count' => Post::ofType('post')->ofStatus('publish')->count(),
            'pages_count' => Post::ofType('page')->ofStatus('publish')->count(),
            'comments_count' => Comment::approved()->count(),
            'comments_pending' => Comment::pending()->count(),
            'recent_posts' => Post::ofType('post')
                ->ofStatus('publish')
                ->with('author:id,name,display_name')
                ->latest('post_date')
                ->take(5)
                ->get(['id', 'title', 'status', 'author_id', 'post_date']),
            'recent_comments' => Comment::with('post:id,title')
                ->latest('comment_date')
                ->take(5)
                ->get(['id', 'post_id', 'author_name', 'content', 'approved', 'comment_date']),
            'recent_drafts' => Post::ofType('post')
                ->ofStatus('draft')
                ->where('author_id', request()->user()->id)
                ->latest('post_modified')
                ->take(4)
                ->get(['id', 'title', 'post_date', 'post_modified']),
        ]);
    }

    // ─── Quick Draft ─────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/dashboard/quick-draft",
        operationId: "createQuickDraft",
        summary: "Create a quick draft post",
        description: "Creates a new draft post with title and optional content. Requires 'edit_posts' capability.",
        tags: ["Admin Dashboard"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "My Quick Draft"),
                    new OA\Property(property: "content", type: "string", example: "Some draft content..."),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Draft created",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", type: "object"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function quickDraft(Request $request): JsonResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
        ]);

        $now = now();

        $post = Post::create([
            'author_id' => $request->user()->id,
            'post_date' => $now,
            'post_date_gmt' => $now->utc(),
            'content' => $request->input('content', ''),
            'title' => $request->input('title'),
            'excerpt' => '',
            'status' => 'draft',
            'comment_status' => 'open',
            'ping_status' => 'open',
            'password' => '',
            'slug' => '',
            'post_modified' => $now,
            'post_modified_gmt' => $now->utc(),
            'content_filtered' => '',
            'parent_id' => 0,
            'guid' => '',
            'menu_order' => 0,
            'type' => 'post',
            'mime_type' => '',
            'comment_count' => 0,
        ]);

        return $this->success($post, 201);
    }
}
