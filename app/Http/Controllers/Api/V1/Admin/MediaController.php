<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkMediaRequest;
use App\Http\Requests\Admin\EditMediaRequest;
use App\Http\Requests\Admin\UpdateMediaRequest;
use App\Http\Requests\Admin\UploadMediaRequest;
use App\Http\Resources\V1\Admin\MediaResource;
use App\Models\Post;
use App\Services\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

#[OA\Tag(name: "Admin Media", description: "Media library upload and management")]
class MediaController extends ApiController
{
    public function __construct(
        private readonly MediaService $mediaService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $query = Post::query()
            ->where('type', 'attachment')
            ->with(['meta', 'author', 'parent'])
            ->orderByDesc('post_date');

        if ($type = $request->query('type')) {
            $this->applyTypeFilter($query, (string) $type);
        }

        if ($month = $request->query('month')) {
            $parts = explode('-', (string) $month);
            if (count($parts) === 2) {
                $query->whereYear('post_date', (int) $parts[0])
                    ->whereMonth('post_date', (int) $parts[1]);
            }
        }

        if ($search = $request->query('search')) {
            $search = (string) $search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('attached_to')) {
            $query->where('parent_id', (int) $request->query('attached_to'));
        }

        $perPage = min((int) $request->query('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => MediaResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function upload(UploadMediaRequest $request): JsonResponse
    {
        $files = $request->file('files', []);
        $attachedTo = $request->input('attached_to');

        $uploaded = $this->mediaService->upload($files, $request->user(), $attachedTo ? (int) $attachedTo : null);

        return $this->success(MediaResource::collection(collect($uploaded)), 201);
    }

    public function show(int $id): JsonResponse
    {
        $media = Post::with(['meta', 'author', 'parent'])
            ->where('type', 'attachment')
            ->find($id);

        if (!$media) {
            return $this->error('Media not found.', 404);
        }

        return $this->success(new MediaResource($media));
    }

    public function update(UpdateMediaRequest $request, int $id): JsonResponse
    {
        $media = Post::where('type', 'attachment')->find($id);

        if (!$media) {
            return $this->error('Media not found.', 404);
        }

        $updated = $this->mediaService->updateAttachment($media, $request->validated());

        return $this->success(new MediaResource($updated));
    }

    public function destroy(int $id): JsonResponse
    {
        $media = Post::where('type', 'attachment')->find($id);

        if (!$media) {
            return $this->error('Media not found.', 404);
        }

        $this->mediaService->deleteAttachment($media);

        return $this->success(['message' => 'Media deleted successfully.']);
    }

    public function bulk(BulkMediaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $ids = $validated['media_ids'];

        $items = Post::where('type', 'attachment')->whereIn('id', $ids)->get();

        if ($items->isEmpty()) {
            return $this->error('No valid media items found.', 422);
        }

        $affected = 0;
        foreach ($items as $media) {
            $this->mediaService->deleteAttachment($media);
            $affected++;
        }

        return $this->success([
            'message' => "{$affected} media item(s) deleted successfully.",
            'affected' => $affected,
        ]);
    }

    public function edit(EditMediaRequest $request, int $id): JsonResponse
    {
        $media = Post::with('meta')->where('type', 'attachment')->find($id);

        if (!$media) {
            return $this->error('Media not found.', 404);
        }

        $validated = $request->validated();

        try {
            $updated = $this->mediaService->editAttachment(
                $media,
                $validated['action'],
                $validated['params'] ?? [],
            );
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 422);
        }

        return $this->success(new MediaResource($updated));
    }

    private function applyTypeFilter($query, string $type): void
    {
        $type = strtolower($type);

        if ($type === 'image') {
            $query->where('mime_type', 'LIKE', 'image/%');
            return;
        }

        if ($type === 'audio') {
            $query->where('mime_type', 'LIKE', 'audio/%');
            return;
        }

        if ($type === 'video') {
            $query->where('mime_type', 'LIKE', 'video/%');
            return;
        }

        if ($type === 'document') {
            $query->where(function ($q) {
                $q->where('mime_type', 'NOT LIKE', 'image/%')
                    ->where('mime_type', 'NOT LIKE', 'audio/%')
                    ->where('mime_type', 'NOT LIKE', 'video/%');
            });
        }
    }
}
