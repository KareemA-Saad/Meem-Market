<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Models\Comment;
use App\Models\Post;
use App\Models\PostMeta;
use App\Models\Term;
use App\Models\TermRelationship;
use App\Models\TermTaxonomy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Content export tool — produces a downloadable JSON package
 * of selected CMS content including posts, meta, terms, and comments.
 */
#[OA\Tag(name: "Admin Tools", description: "Export, import, and site health tools")]
class ExportController extends ApiController
{
    #[OA\Post(
        path: "/api/v1/admin/tools/export",
        operationId: "exportContent",
        summary: "Export CMS content as JSON",
        tags: ["Admin Tools"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: false, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "type", type: "string", example: "post"),
                new OA\Property(property: "category", type: "integer"),
                new OA\Property(property: "author", type: "integer"),
                new OA\Property(property: "start_date", type: "string", format: "date"),
                new OA\Property(property: "end_date", type: "string", format: "date"),
                new OA\Property(property: "status", type: "string"),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Exported JSON"),
        ]
    )]
    public function export(Request $request): JsonResponse
    {
        $query = Post::with(['meta', 'author', 'comments.meta', 'taxonomies.term'])
            ->notRevision();

        // Filters
        if ($type = $request->input('type')) {
            $query->ofType($type);
        }

        if ($author = $request->input('author')) {
            $query->where('author_id', $author);
        }

        if ($status = $request->input('status')) {
            $query->ofStatus($status);
        }

        if ($startDate = $request->input('start_date')) {
            $query->where('post_date', '>=', $startDate);
        }

        if ($endDate = $request->input('end_date')) {
            $query->where('post_date', '<=', $endDate . ' 23:59:59');
        }

        if ($category = $request->input('category')) {
            $query->whereHas('taxonomies', function ($q) use ($category) {
                $q->where('taxonomy', 'category')
                    ->where('term_taxonomy.id', $category);
            });
        }

        $posts = $query->get();

        $exportData = [
            'generator' => 'MeemMark Admin API',
            'exported_at' => now()->toIso8601String(),
            'post_count' => $posts->count(),
            'posts' => $posts->map(fn(Post $post) => $this->formatPostForExport($post)),
        ];

        return response()->json($exportData)
            ->header('Content-Disposition', 'attachment; filename="meemmark-export-' . now()->format('Y-m-d') . '.json"');
    }

    /**
     * Build a self-contained export representation of a single post.
     */
    private function formatPostForExport(Post $post): array
    {
        return [
            'id' => $post->id,
            'title' => $post->title,
            'slug' => $post->slug,
            'content' => $post->content,
            'excerpt' => $post->excerpt,
            'status' => $post->status,
            'type' => $post->type,
            'post_date' => $post->post_date?->toIso8601String(),
            'post_modified' => $post->post_modified?->toIso8601String(),
            'author' => [
                'id' => $post->author?->id,
                'login' => $post->author?->login,
                'display_name' => $post->author?->display_name ?? $post->author?->name,
            ],
            'meta' => $post->meta->pluck('meta_value', 'meta_key')->toArray(),
            'terms' => $post->taxonomies->map(fn($tt) => [
                'taxonomy' => $tt->taxonomy,
                'name' => $tt->term?->name,
                'slug' => $tt->term?->slug,
            ])->values()->toArray(),
            'comments' => $post->comments->map(fn(Comment $c) => [
                'author_name' => $c->author_name,
                'author_email' => $c->author_email,
                'content' => $c->content,
                'approved' => $c->approved,
                'comment_date' => $c->comment_date?->toIso8601String(),
            ])->toArray(),
        ];
    }
}
