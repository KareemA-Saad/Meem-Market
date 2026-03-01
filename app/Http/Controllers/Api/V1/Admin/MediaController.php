<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Http\Resources\V1\Admin\MediaResource;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

/**
 * Media Library API — upload, list, update metadata, and delete attachments.
 *
 * Media items are stored as Post records with type='attachment'.
 */
#[OA\Tag(name: "Admin Media", description: "Media Library upload, CRUD, and bulk operations")]
class MediaController extends ApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    // ─── List ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/media",
        operationId: "listMedia",
        summary: "List media items (paginated)",
        tags: ["Admin Media"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "type", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["image", "audio", "video", "document"])),
            new OA\Parameter(name: "month", in: "query", required: false, schema: new OA\Schema(type: "string", example: "2026-03")),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated media list"),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = Post::query()
            ->with('meta')
            ->where('type', 'attachment')
            ->orderByDesc('post_date');

        $this->applyFilters($query, $request);

        $perPage = min((int) $request->query('per_page', 20), 100);

        return $this->paginated($query->paginate($perPage), MediaResource::class);
    }

    // ─── Upload ──────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/media/upload",
        operationId: "uploadMedia",
        summary: "Upload one or more files",
        tags: ["Admin Media"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\MediaType(
                mediaType: "multipart/form-data",
                schema: new OA\Schema(
                    properties: [
                        new OA\Property(property: "files[]", type: "array", items: new OA\Items(type: "string", format: "binary")),
                    ]
                )
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Files uploaded"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $files = $request->file('files');
        $authorId = $request->user()->id;
        $uploaded = [];

        foreach ($files as $file) {
            $attachment = $this->mediaService->upload($file, $authorId);
            $attachment->load('meta');
            $uploaded[] = new MediaResource($attachment);
        }

        return $this->success($uploaded, 201);
    }

    // ─── Show ────────────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/media/{id}",
        operationId: "showMedia",
        summary: "Get a single media item",
        tags: ["Admin Media"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(response: 200, description: "Media details"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $attachment = Post::with('meta')
            ->where('type', 'attachment')
            ->find($id);

        if (!$attachment) {
            return $this->error('Media not found.', 404);
        }

        return $this->success(new MediaResource($attachment));
    }

    // ─── Update Metadata ─────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/media/{id}",
        operationId: "updateMedia",
        summary: "Update media metadata",
        tags: ["Admin Media"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            properties: [
                new OA\Property(property: "title", type: "string"),
                new OA\Property(property: "caption", type: "string"),
                new OA\Property(property: "alt_text", type: "string"),
                new OA\Property(property: "description", type: "string"),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Media updated"),
            new OA\Response(response: 404, description: "Not found"),
        ]
    )]
    public function update(UpdateMediaRequest $request, int $id): JsonResponse
    {
        $attachment = Post::with('meta')
            ->where('type', 'attachment')
            ->find($id);

        if (!$attachment) {
            return $this->error('Media not found.', 404);
        }

        $validated = $request->validated();

        // Post-level fields
        if (isset($validated['title'])) {
            $attachment->title = $validated['title'];
        }
        if (isset($validated['caption'])) {
            $attachment->excerpt = $validated['caption'];
        }
        if (isset($validated['description'])) {
            $attachment->content = $validated['description'];
        }

        $attachment->post_modified = now();
        $attachment->post_modified_gmt = now()->utc();
        $attachment->save();

        // Alt text is stored in post_meta
        if (isset($validated['alt_text'])) {
            $attachment->meta()->updateOrCreate(
                ['meta_key' => '_wp_attachment_image_alt'],
                ['meta_value' => $validated['alt_text']],
            );
        }

        $attachment->load('meta');

        return $this->success(new MediaResource($attachment));
    }

    // ─── Delete ──────────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/media/{id}",
        operationId: "deleteMedia",
        summary: "Permanently delete a media item",
        tags: ["Admin Media"],
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
        $attachment = Post::with('meta')
            ->where('type', 'attachment')
            ->find($id);

        if (!$attachment) {
            return $this->error('Media not found.', 404);
        }

        $this->mediaService->deleteAttachment($attachment);

        return $this->success(['message' => 'Media deleted successfully.']);
    }

    // ─── Bulk ────────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/media/bulk",
        operationId: "bulkMediaAction",
        summary: "Bulk delete media items",
        tags: ["Admin Media"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(required: true, content: new OA\JsonContent(
            required: ["action", "media_ids"],
            properties: [
                new OA\Property(property: "action", type: "string", enum: ["delete"]),
                new OA\Property(property: "media_ids", type: "array", items: new OA\Items(type: "integer")),
            ]
        )),
        responses: [
            new OA\Response(response: 200, description: "Bulk action completed"),
        ]
    )]
    public function bulk(BulkMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $attachments = Post::with('meta')
            ->where('type', 'attachment')
            ->whereIn('id', $validated['media_ids'])
            ->get();

        if ($attachments->isEmpty()) {
            return $this->error('No valid media items found.', 422);
        }

        $affected = $attachments->count();

        foreach ($attachments as $attachment) {
            $this->mediaService->deleteAttachment($attachment);
        }

        return $this->success([
            'message' => "{$affected} media item(s) deleted.",
            'affected' => $affected,
        ]);
    }

    // ═══════════════════════════════════════════════════════════
    //  Private Helpers
    // ═══════════════════════════════════════════════════════════

    /**
     * Apply request filters to the media query.
     */
    private function applyFilters($query, Request $request): void
    {
        // MIME type category filter
        if ($typeFilter = $request->query('type')) {
            $mimePrefix = match ($typeFilter) {
                'image' => 'image/',
                'audio' => 'audio/',
                'video' => 'video/',
                'document' => 'application/',
                default => null,
            };

            if ($mimePrefix) {
                $query->where('mime_type', 'LIKE', "{$mimePrefix}%");
            }
        }

        // Month filter (format: YYYY-MM)
        if ($month = $request->query('month')) {
            $query->whereRaw("strftime('%Y-%m', post_date) = ?", [$month]);
        }

        // Search by title
        if ($search = $request->query('search')) {
            $query->where('title', 'LIKE', "%{$search}%");
        }
    }
}
