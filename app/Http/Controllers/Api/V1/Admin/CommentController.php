<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkCommentRequest;
use App\Http\Requests\Admin\ReplyCommentRequest;
use App\Http\Requests\Admin\UpdateCommentRequest;
use App\Http\Resources\V1\Admin\CommentResource;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Comment moderation API — list, edit, approve/spam/trash, reply, bulk actions.
 */
#[OA\Tag(name: "Admin Comments", description: "Comment moderation and management")]
class CommentController extends ApiController
{
    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/comments",
        operationId: "listComments",
        summary: "List comments (paginated with status counts)",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "status", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["approved", "pending", "spam", "trash"])),
            new OA\Parameter(name: "post_id", in: "query", required: false, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated comment list with status counts"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Comment::query()
            ->with('post')
            ->orderByDesc('comment_date');

        // Status filter
        if ($status = $request->query('status')) {
            $approvedValue = match ($status) {
                'approved' => '1',
                'pending' => '0',
                'spam' => 'spam',
                'trash' => 'trash',
                default => null,
            };

            if ($approvedValue !== null) {
                $query->where('approved', $approvedValue);
            }
        }

        if ($postId = $request->query('post_id')) {
            $query->where('post_id', $postId);
        }

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('content', 'LIKE', "%{$search}%")
                    ->orWhere('author_name', 'LIKE', "%{$search}%")
                    ->orWhere('author_email', 'LIKE', "%{$search}%");
            });
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $statusCounts = $this->getStatusCounts();

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'status_counts' => $statusCounts,
            ],
        ]);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/comments/{id}",
        operationId: "showComment",
        summary: "Get a single comment",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Comment details"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $comment = Comment::with('post')->find($id);

        if (!$comment) {
            return $this->error('Comment not found.', 404);
        }

        return $this->success(new CommentResource($comment));
    }

    // ─── Update ──────────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/comments/{id}",
        operationId: "updateComment",
        summary: "Edit a comment",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "author_name", type: "string"),
                new OA\Property(property: "author_email", type: "string"),
                new OA\Property(property: "author_url", type: "string"),
                new OA\Property(property: "content", type: "string"),
                new OA\Property(property: "status", type: "string", enum: ["0", "1", "spam", "trash"]),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Comment updated"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function update(UpdateCommentRequest $request, int $id): JsonResponse
    {
        $comment = Comment::with('post')->find($id);

        if (!$comment) {
            return $this->error('Comment not found.', 404);
        }

        $validated = $request->validated();

        if (isset($validated['status'])) {
            $validated['approved'] = $validated['status'];
            unset($validated['status']);
        }

        $comment->update($validated);
        $comment->refresh();

        return $this->success(new CommentResource($comment));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/comments/{id}",
        operationId: "deleteComment",
        summary: "Permanently delete a comment",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Deleted"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function destroy(int $id): JsonResponse
    {
        $comment = Comment::find($id);

        if (!$comment) {
            return $this->error('Comment not found.', 404);
        }

        // Cascade: delete child comments (replies) and meta
        $comment->meta()->delete();
        Comment::where('parent_id', $comment->id)->delete();
        $comment->delete();

        return $this->success(['message' => 'Comment deleted permanently.']);
    }

    // ─── Status Transitions ─────────────────────────────────────

    #[OA\Post(path: "/api/v1/admin/comments/{id}/approve", operationId: "approveComment", summary: "Approve a comment", tags: ["Admin Comments"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Approved"), new OA\Response(response: 404, description: "Not found")])]
    public function approve(int $id): JsonResponse
    {
        return $this->changeStatus($id, '1');
    }

    #[OA\Post(path: "/api/v1/admin/comments/{id}/unapprove", operationId: "unapproveComment", summary: "Mark comment as pending", tags: ["Admin Comments"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Unapproved"), new OA\Response(response: 404, description: "Not found")])]
    public function unapprove(int $id): JsonResponse
    {
        return $this->changeStatus($id, '0');
    }

    #[OA\Post(path: "/api/v1/admin/comments/{id}/spam", operationId: "spamComment", summary: "Mark comment as spam", tags: ["Admin Comments"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Marked as spam"), new OA\Response(response: 404, description: "Not found")])]
    public function spam(int $id): JsonResponse
    {
        return $this->changeStatus($id, 'spam');
    }

    #[OA\Post(path: "/api/v1/admin/comments/{id}/trash", operationId: "trashComment", summary: "Move comment to trash", tags: ["Admin Comments"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Trashed"), new OA\Response(response: 404, description: "Not found")])]
    public function trash(int $id): JsonResponse
    {
        return $this->changeStatus($id, 'trash');
    }

    #[OA\Post(path: "/api/v1/admin/comments/{id}/restore", operationId: "restoreComment", summary: "Restore comment from trash", tags: ["Admin Comments"], security: [["sanctum" => []]], parameters: [new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer"))], responses: [new OA\Response(response: 200, description: "Restored"), new OA\Response(response: 404, description: "Not found")])]
    public function restore(int $id): JsonResponse
    {
        return $this->changeStatus($id, '0');
    }

    // ─── Reply ───────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/comments/{id}/reply",
        operationId: "replyComment",
        summary: "Reply to a comment as the authenticated user",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["content"],
            properties: [new OA\Property(property: "content", type: "string")]
        )),
        responses: [
            new OA\Response(response: 201, description: "Reply created"),
            new OA\Response(response: 404, description: "Parent comment not found"),
        ]
    )]
    public function reply(ReplyCommentRequest $request, int $id): JsonResponse
    {
        $parentComment = Comment::find($id);

        if (!$parentComment) {
            return $this->error('Comment not found.', 404);
        }

        $user = $request->user();

        $reply = Comment::create([
            'post_id' => $parentComment->post_id,
            'author_name' => $user->display_name ?? $user->name,
            'author_email' => $user->email,
            'author_url' => $user->url ?? '',
            'author_ip' => $request->ip(),
            'comment_date' => now(),
            'comment_date_gmt' => now()->utc(),
            'content' => $request->validated('content'),
            'karma' => 0,
            'approved' => '1', // Admin replies are auto-approved
            'agent' => $request->userAgent() ?? '',
            'type' => 'comment',
            'parent_id' => $parentComment->id,
            'user_id' => $user->id,
        ]);

        $reply->load('post');

        return $this->success(new CommentResource($reply), 201);
    }

    // ─── Bulk ────────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/comments/bulk",
        operationId: "bulkCommentAction",
        summary: "Bulk comment moderation",
        tags: ["Admin Comments"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["action", "comment_ids"],
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["approve", "unapprove", "spam", "trash", "delete"]),
                new OA\Property(property: "comment_ids", type: "array", items: new OA\Items(type: "integer")),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
        ]
    )]
    public function bulk(BulkCommentRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $comments = Comment::whereIn('id', $validated['comment_ids'])->get();

        if ($comments->isEmpty()) {
            return $this->error('No valid comments found.', 422);
        }

        $affected = $comments->count();

        if ($validated['action'] === 'delete') {
            foreach ($comments as $comment) {
                $comment->meta()->delete();
                Comment::where('parent_id', $comment->id)->delete();
                $comment->delete();
            }
        } else {
            $statusMap = [
                'approve' => '1',
                'unapprove' => '0',
                'spam' => 'spam',
                'trash' => 'trash',
            ];

            $newStatus = $statusMap[$validated['action']];

            Comment::whereIn('id', $validated['comment_ids'])
                ->update(['approved' => $newStatus]);
        }

        return $this->success([
            'message' => "{$affected} comment(s) processed ({$validated['action']}).",
            'affected' => $affected,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Transition a comment to a new approval status.
     */
    private function changeStatus(int $id, string $newStatus): JsonResponse
    {
        $comment = Comment::with('post')->find($id);

        if (!$comment) {
            return $this->error('Comment not found.', 404);
        }

        $comment->update(['approved' => $newStatus]);

        return $this->success(new CommentResource($comment));
    }

    /**
     * Aggregate comment counts by status for the list meta.
     */
    private function getStatusCounts(): array
    {
        return [
            'all' => Comment::count(),
            'approved' => Comment::where('approved', '1')->count(),
            'pending' => Comment::where('approved', '0')->count(),
            'spam' => Comment::where('approved', 'spam')->count(),
            'trash' => Comment::where('approved', 'trash')->count(),
        ];
    }
}
