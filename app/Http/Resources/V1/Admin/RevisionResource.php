<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * Revision representation — a snapshot of a post before an edit.
 */
#[OA\Schema(
    schema: "PostRevision",
    properties: [
        new OA\Property(property: "id", type: "integer"),
        new OA\Property(property: "parent_id", type: "integer"),
        new OA\Property(property: "title", type: "string"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "excerpt", type: "string"),
        new OA\Property(property: "author", ref: "#/components/schemas/AdminUser", nullable: true),
        new OA\Property(property: "post_modified", type: "string", format: "date-time"),
    ]
)]
class RevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'title' => $this->title,
            'content' => $this->content,
            'excerpt' => $this->excerpt,
            'author' => $this->whenLoaded('author', fn() => new UserResource($this->author)),
            'post_modified' => $this->post_modified?->toIso8601String(),
        ];
    }
}
