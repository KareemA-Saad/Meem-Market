<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use OpenApi\Attributes as OA;

/**
 * JSON representation of an attachment (media item).
 */
#[OA\Schema(
    schema: "AdminMedia",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 120),
        new OA\Property(property: "title", type: "string", example: "hero-banner"),
        new OA\Property(property: "slug", type: "string", example: "hero-banner"),
        new OA\Property(property: "mime_type", type: "string", example: "image/jpeg"),
        new OA\Property(property: "type", type: "string", example: "image"),
        new OA\Property(property: "url", type: "string", nullable: true),
        new OA\Property(property: "alt_text", type: "string", nullable: true),
        new OA\Property(property: "caption", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string", nullable: true),
        new OA\Property(property: "width", type: "integer", nullable: true),
        new OA\Property(property: "height", type: "integer", nullable: true),
        new OA\Property(property: "filesize", type: "integer", nullable: true),
        new OA\Property(property: "file_info", type: "object", nullable: true),
        new OA\Property(property: "sizes", type: "object", nullable: true),
        new OA\Property(property: "attached_to", type: "object", nullable: true),
        new OA\Property(property: "author_id", type: "integer"),
        new OA\Property(property: "uploaded_at", type: "string", format: "date-time"),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", nullable: true),
    ]
)]
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        $metadata = [];
        if (isset($metaMap['_wp_attachment_metadata'])) {
            $decoded = json_decode((string) $metaMap['_wp_attachment_metadata'], true);
            if (is_array($decoded)) {
                $metadata = $decoded;
            }
        }

        $relativePath = $metaMap['_wp_attached_file'] ?? ($metadata['file'] ?? null);
        $url = $relativePath ? Storage::disk('public')->url($relativePath) : null;

        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'slug'        => $this->slug,
            'mime_type'   => $this->mime_type,
            'type'        => $this->resolveType($this->mime_type),
            'url'         => $url,
            'alt_text'    => $metaMap['_wp_attachment_image_alt'] ?? null,
            'caption'     => $this->excerpt ?: null,
            'description' => $this->content ?: null,
            'width'       => isset($metadata['width']) ? (int) $metadata['width'] : null,
            'height'      => isset($metadata['height']) ? (int) $metadata['height'] : null,
            'filesize'    => isset($metadata['filesize']) ? (int) $metadata['filesize'] : null,
            'file_info'   => $relativePath ? [
                'relative_path' => $relativePath,
                'filename'      => basename($relativePath),
                'extension'     => pathinfo($relativePath, PATHINFO_EXTENSION),
            ] : null,
            'sizes'       => $metadata['sizes'] ?? null,
            'attached_to' => $this->parent_id > 0 ? [
                'id'    => $this->parent_id,
                'title' => $this->parent?->title,
            ] : null,
            'author_id'   => $this->author_id,
            'uploaded_at' => $this->post_date?->toIso8601String(),
            'updated_at'  => $this->post_modified?->toIso8601String(),
        ];
    }

    private function resolveType(?string $mime): string
    {
        $mime = strtolower((string) $mime);

        if (str_starts_with($mime, 'image/')) return 'image';
        if (str_starts_with($mime, 'audio/')) return 'audio';
        if (str_starts_with($mime, 'video/')) return 'video';

        return 'document';
    }
}