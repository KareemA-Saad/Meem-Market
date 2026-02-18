<?php

namespace App\Http\Resources\V1\Admin;

use App\Services\RoleService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "AdminUser",
    title: "Admin User",
    description: "Admin user profile representation",
    properties: [
        new OA\Property(property: "id", type: "integer", example: 1),
        new OA\Property(property: "login", type: "string", example: "admin"),
        new OA\Property(property: "email", type: "string", format: "email", example: "admin@meemmark.com"),
        new OA\Property(property: "display_name", type: "string", example: "Admin"),
        new OA\Property(property: "nicename", type: "string", example: "admin"),
        new OA\Property(property: "url", type: "string", example: ""),
        new OA\Property(property: "registered_at", type: "string", format: "date-time"),
        new OA\Property(property: "status", type: "integer", example: 0),
        new OA\Property(property: "role", type: "string", example: "administrator"),
        new OA\Property(property: "capabilities", type: "object", example: '{"manage_options": true, "edit_posts": true}'),
        new OA\Property(property: "avatar_url", type: "string", example: "https://www.gravatar.com/avatar/abc123?s=96&d=mm&r=g"),
    ]
)]
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var \App\Models\User $user */
        $user = $this->resource;

        $roleService = app(RoleService::class);
        $roleName = $roleService->getUserRole($user);
        $capabilities = $roleService->getUserCapabilities($user);

        return [
            'id' => $user->id,
            'login' => $user->login,
            'email' => $user->email,
            'display_name' => $user->display_name ?? $user->name,
            'nicename' => $user->nicename,
            'url' => $user->url,
            'registered_at' => $user->registered_at?->toIso8601String(),
            'status' => $user->status,
            'role' => $roleName,
            'capabilities' => $capabilities,
            'avatar_url' => $this->getGravatarUrl($user->email),
            'meta' => $this->when($this->relationLoaded('meta'), function () use ($user) {
                return $this->formatMeta($user);
            }),
        ];
    }

    /**
     * Generate Gravatar URL from email (matches WP's get_avatar_url behaviour).
     */
    private function getGravatarUrl(string $email): string
    {
        $hash = md5(strtolower(trim($email)));

        return "https://www.gravatar.com/avatar/{$hash}?s=96&d=mm&r=g";
    }

    /**
     * Format user meta into a key-value object for common fields.
     */
    private function formatMeta($user): array
    {
        $metaKeys = ['first_name', 'last_name', 'nickname', 'description', 'rich_editing', 'admin_color'];

        return $user->meta
            ->whereIn('meta_key', $metaKeys)
            ->pluck('meta_value', 'meta_key')
            ->toArray();
    }
}
