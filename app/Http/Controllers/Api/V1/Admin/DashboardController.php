<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\StoreQuickDraftRequest;
use App\Models\Branch;
use App\Models\Career;
use App\Models\Comment;
use App\Models\CompetitiveFeature;
use App\Models\ContactMessage;
use App\Models\Country;
use App\Models\Offer;
use App\Models\OfferCategory;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Section;
use App\Models\Slider;
use Illuminate\Http\JsonResponse;
use OpenApi\Attributes as OA;

/**
 * Dashboard stats and quick-draft endpoints.
 * Mirrors the WP admin dashboard widgets (At a Glance, Quick Draft, Activity).
 */
#[OA\Tag(name: "Admin Dashboard", description: "Dashboard statistics and quick actions")]
class DashboardController extends ApiController
{
    // أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬ Stats أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬

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
        $homepageSummary = [
            'sliders' => [
                'total' => Slider::count(),
                'active' => Slider::where('is_active', true)->count(),
            ],
            'sections' => [
                'total' => Section::count(),
                'active' => Section::where('is_active', true)->count(),
            ],
            'partners' => [
                'total' => Partner::count(),
                'active' => Partner::where('is_active', true)->count(),
            ],
            'features' => [
                'total' => CompetitiveFeature::count(),
                'active' => CompetitiveFeature::where('is_active', true)->count(),
            ],
        ];

        return $this->success([
            'posts_count' => Post::ofType('post')->ofStatus('publish')->count(),
            'pages_count' => Post::ofType('page')->ofStatus('publish')->count(),
            'comments_count' => Comment::approved()->count(),
            'comments_pending' => Comment::pending()->count(),
            'business_summary' => [
                'countries_count' => Country::count(),
                'branches_count' => Branch::count(),
                'offer_categories_count' => OfferCategory::count(),
                'offers_count' => Offer::count(),
                'active_offers_count' => Offer::where('is_active', true)->count(),
                'careers_count' => Career::count(),
                'active_careers_count' => Career::where('is_active', true)->count(),
                'unread_contact_messages_count' => ContactMessage::where('is_read', false)->count(),
            ],
            'homepage_summary' => [
                ...$homepageSummary,
                'is_publish_ready' => $homepageSummary['sliders']['active'] > 0
                    && $homepageSummary['sections']['active'] > 0
                    && $homepageSummary['partners']['active'] > 0
                    && $homepageSummary['features']['active'] > 0,
            ],
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

    // أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬ Quick Draft أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬أ¢â€‌â‚¬

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
    public function quickDraft(StoreQuickDraftRequest $request): JsonResponse
    {
        $now = now();
        $validated = $request->validated();

        $post = Post::create([
            'author_id' => $request->user()->id,
            'post_date' => $now,
            'post_date_gmt' => $now->utc(),
            'content' => (string) ($validated['content'] ?? ''),
            'title' => (string) $validated['title'],
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
