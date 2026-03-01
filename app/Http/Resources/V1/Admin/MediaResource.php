<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of an attachment (media item).
 */
#[OA\Schema(
    schema: "AdminMedia",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 10),
        new OA\Property(property: "title", type: "string", example: "company-logo"),
        new OA\Property(property: "slug", type: "string", example: "company-logo"),
        new OA\Property(property: "mime_type", type: "string", example: "image/png"),
        new OA\Property(property: "url", type: "string", example: "https://example.com/storage/uploads/2026/03/company-logo.png"),
        new OA\Property(property: "alt_text", type: "string", nullable: true),
        new OA\Property(property: "caption", type: "string", nullable: true),
        new OA\Property(property: "description", type: "string"),
        new OA\Property(property: "width", type: "integer", nullable: true),
        new OA\Property(property: "height", type: "integer", nullable: true),
        new OA\Property(property: "filesize", type: "integer", nullable: true),
        new OA\Property(property: "author_id", type: "integer"),
        new OA\Property(property: "uploaded_at", type: "string", format: "date-time"),
    ]
)]
class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $metaMap = $this->whenLoaded('meta', function () {
            return $this->meta->pluck('meta_value', 'meta_key');
        }, collect());

        $attachmentMeta = json_decode($metaMap['_wp_attachment_metadata'] ?? '{}', true) ?: [];

        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'mime_type' => $this->mime_type,
            'url' => $this->guid,
            'alt_text' => $metaMap['_wp_attachment_image_alt'] ?? null,
            'caption' => $this->excerpt ?: null,
            'description' => $this->content,
            'width' => $attachmentMeta['width'] ?? null,
            'height' => $attachmentMeta['height'] ?? null,
            'filesize' => $attachmentMeta['filesize'] ?? null,
            'file' => $metaMap['_wp_attached_file'] ?? null,
            'sizes' => $attachmentMeta['sizes'] ?? null,
            'author_id' => $this->author_id,
            'uploaded_at' => $this->post_date?->toIso8601String(),
        ];
    }
}
