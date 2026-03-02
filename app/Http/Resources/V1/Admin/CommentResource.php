<?php

namespace App\Http\Resources\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

/**
 * JSON representation of a comment for admin moderation.
 */
#[OA\Schema(
    schema: "AdminComment",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "post_id", type: "integer", example: 42),
        new OA\Property(property: "post_title", type: "string", nullable: true),
        new OA\Property(property: "author_name", type: "string", example: "John Doe"),
        new OA\Property(property: "author_email", type: "string", example: "john@example.com"),
        new OA\Property(property: "author_url", type: "string"),
        new OA\Property(property: "content", type: "string"),
        new OA\Property(property: "approved", type: "string", enum: ["0", "1", "spam", "trash"]),
        new OA\Property(property: "type", type: "string"),
        new OA\Property(property: "parent_id", type: "integer", nullable: true),
        new OA\Property(property: "user_id", type: "integer", nullable: true),
        new OA\Property(property: "comment_date", type: "string", format: "date-time"),
    ]
)]
class CommentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'post_id' => $this->post_id,
            'post_title' => $this->whenLoaded('post', fn() => $this->post?->title),
            'author_name' => $this->author_name,
            'author_email' => $this->author_email,
            'author_url' => $this->author_url,
            'content' => $this->content,
            'approved' => $this->approved,
            'type' => $this->type,
            'parent_id' => $this->parent_id ?: null,
            'user_id' => $this->user_id ?: null,
            'comment_date' => $this->comment_date?->toIso8601String(),
        ];
    }
}
