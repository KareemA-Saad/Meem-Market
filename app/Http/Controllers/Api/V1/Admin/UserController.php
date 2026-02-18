<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\BulkUserRequest;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Http\Resources\V1\Admin\UserCollection;
use App\Http\Resources\V1\Admin\UserResource;
use App\Mail\NewUserRegistrationMail;
use App\Models\Post;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Full CRUD + bulk operations for admin user management.
 * Mirrors WP Users → All Users screen.
 */
#[OA\Tag(name: "Admin Users", description: "User management CRUD and bulk operations")]
class UserController extends ApiController
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    // ─── List Users ──────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/users",
        operationId: "listUsers",
        summary: "List all users (paginated)",
        description: "Returns a paginated list of users with optional role/search filters. Includes role count metadata. Requires 'list_users' capability.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "role", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Filter by role slug"),
            new OA\Parameter(name: "search", in: "query", required: false, schema: new OA\Schema(type: "string"), description: "Search by name, login, or email"),
            new OA\Parameter(name: "sort_by", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["name", "email", "login", "registered_at", "id"], default: "id")),
            new OA\Parameter(name: "sort_dir", in: "query", required: false, schema: new OA\Schema(type: "string", enum: ["asc", "desc"], default: "desc")),
            new OA\Parameter(name: "per_page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 20)),
            new OA\Parameter(name: "page", in: "query", required: false, schema: new OA\Schema(type: "integer", default: 1)),
        ],
        responses: [
            new OA\Response(response: 200, description: "Paginated user list with role counts"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
        ]
    )]
    public function index(Request $request): JsonResponse
    {
        $query = User::query()->with('meta');

        // Filter by role — join user_meta where wp_capabilities contains the role
        if ($role = $request->query('role')) {
            $query->whereHas('meta', function ($q) use ($role) {
                $q->where('meta_key', 'wp_capabilities')
                    ->where('meta_value', 'LIKE', "%\"{$role}\"%");
            });
        }

        // Search across multiple fields
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('login', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('display_name', 'LIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->query('sort_by', 'id');
        $sortDir = $request->query('sort_dir', 'desc');
        $allowedSorts = ['name', 'email', 'login', 'registered_at', 'id'];

        if (in_array($sortBy, $allowedSorts, true)) {
            $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        $perPage = min((int) $request->query('per_page', 20), 100);

        $paginator = $query->paginate($perPage);

        return (new UserCollection($paginator))
            ->response()
            ->setStatusCode(200);
    }

    // ─── Create User ─────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/users",
        operationId: "createUser",
        summary: "Create a new user",
        description: "Creates a new user with specified role. Auto-generates password if not provided. Optionally sends notification email. Requires 'create_users' capability.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["login", "email", "role"],
                properties: [
                    new OA\Property(property: "login", type: "string", example: "johndoe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "password", type: "string", example: "SecurePass123"),
                    new OA\Property(property: "first_name", type: "string", example: "John"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                    new OA\Property(property: "url", type: "string", example: "https://example.com"),
                    new OA\Property(property: "role", type: "string", example: "editor"),
                    new OA\Property(property: "send_notification", type: "boolean", example: true),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "User created",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/AdminUser"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function store(StoreUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $plainPassword = $validated['password'] ?? Str::random(16);

        $user = User::create([
            'name' => $validated['login'],
            'login' => $validated['login'],
            'nicename' => Str::slug($validated['login']),
            'email' => $validated['email'],
            'password' => Hash::make($plainPassword),
            'display_name' => $validated['login'],
            'registered_at' => now(),
            'url' => $validated['url'] ?? '',
            'activation_key' => '',
            'status' => 0,
        ]);

        // Assign role
        $this->roleService->setUserRole($user, $validated['role']);

        // Seed user meta
        $metaDefaults = [
            'nickname' => $validated['login'],
            'first_name' => $validated['first_name'] ?? '',
            'last_name' => $validated['last_name'] ?? '',
            'description' => '',
            'rich_editing' => 'true',
            'admin_color' => 'fresh',
        ];

        foreach ($metaDefaults as $key => $value) {
            UserMeta::create([
                'user_id' => $user->id,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }

        // Send notification email if requested
        if ($request->boolean('send_notification')) {
            Mail::to($user->email)->send(new NewUserRegistrationMail($user, $plainPassword));
        }

        $user->load('meta');

        return $this->success(new UserResource($user), 201);
    }

    // ─── Show User ───────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/users/{id}",
        operationId: "showUser",
        summary: "Get a single user",
        description: "Returns a user's full profile. Requires 'edit_users' capability or own profile.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User profile",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/AdminUser"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "User not found"),
        ]
    )]
    public function show(int $id): JsonResponse
    {
        $user = User::with('meta')->find($id);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        // Allow viewing own profile or require edit_users capability
        $currentUser = request()->user();
        $isOwnProfile = $currentUser->id === $user->id;

        if (!$isOwnProfile && !$this->roleService->userCan($currentUser, 'edit_users')) {
            return $this->error('You do not have permission to view this user.', 403);
        }

        return $this->success(new UserResource($user));
    }

    // ─── Update User ─────────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/users/{id}",
        operationId: "updateUser",
        summary: "Update a user",
        description: "Updates a user's profile and/or role. Role changes require 'promote_users' capability. Requires 'edit_users' capability.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "first_name", type: "string"),
                    new OA\Property(property: "last_name", type: "string"),
                    new OA\Property(property: "nickname", type: "string"),
                    new OA\Property(property: "display_name", type: "string"),
                    new OA\Property(property: "email", type: "string", format: "email"),
                    new OA\Property(property: "url", type: "string"),
                    new OA\Property(property: "bio", type: "string"),
                    new OA\Property(property: "password", type: "string", format: "password"),
                    new OA\Property(property: "role", type: "string", example: "editor"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "User updated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/AdminUser"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        $user = User::with('meta')->find($id);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        $validated = $request->validated();

        // Handle role change — requires promote_users capability
        if (isset($validated['role'])) {
            $currentUser = $request->user();

            if (!$this->roleService->userCan($currentUser, 'promote_users')) {
                return $this->error('You do not have permission to change user roles.', 403);
            }

            $this->roleService->setUserRole($user, $validated['role']);
        }

        // Update model fields
        $modelFields = ['display_name', 'email', 'url'];
        $modelUpdates = array_intersect_key($validated, array_flip($modelFields));

        if (isset($validated['password'])) {
            $modelUpdates['password'] = Hash::make($validated['password']);
        }

        if (!empty($modelUpdates)) {
            $user->update($modelUpdates);
        }

        // Update meta fields
        $metaFields = ['first_name', 'last_name', 'nickname', 'bio'];

        foreach ($metaFields as $field) {
            if (!isset($validated[$field])) {
                continue;
            }

            $metaKey = $field === 'bio' ? 'description' : $field;

            UserMeta::updateOrCreate(
                ['user_id' => $user->id, 'meta_key' => $metaKey],
                ['meta_value' => $validated[$field]],
            );
        }

        $user->refresh();
        $user->load('meta');

        return $this->success(new UserResource($user));
    }

    // ─── Delete User ─────────────────────────────────────────────

    #[OA\Delete(
        path: "/api/v1/admin/users/{id}",
        operationId: "deleteUser",
        summary: "Delete a user",
        description: "Deletes a user and optionally reassigns their content to another user. Requires 'delete_users' capability.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, schema: new OA\Schema(type: "integer")),
            new OA\Parameter(name: "reassign_to", in: "query", required: false, schema: new OA\Schema(type: "integer"), description: "User ID to reassign content to"),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "User deleted",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "User deleted successfully."),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "User not found"),
        ]
    )]
    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = User::find($id);

        if (!$user) {
            return $this->error('User not found.', 404);
        }

        // Prevent self-deletion
        if ($request->user()->id === $user->id) {
            return $this->error('You cannot delete your own account.', 403);
        }

        // Reassign or delete content
        $reassignTo = $request->query('reassign_to');

        if ($reassignTo) {
            $targetUser = User::find($reassignTo);

            if (!$targetUser) {
                return $this->error('Reassignment target user not found.', 404);
            }

            Post::where('author_id', $user->id)->update(['author_id' => $targetUser->id]);
        } else {
            // Delete all user's posts and their meta
            $postIds = Post::where('author_id', $user->id)->pluck('id');
            \App\Models\PostMeta::whereIn('post_id', $postIds)->delete();
            Post::where('author_id', $user->id)->delete();
        }

        // Cascade delete user_meta
        UserMeta::where('user_id', $user->id)->delete();

        // Revoke all tokens
        $user->tokens()->delete();

        // Delete the user
        $user->delete();

        return $this->success(['message' => 'User deleted successfully.']);
    }

    // ─── Bulk Action ─────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/users/bulk",
        operationId: "bulkUserAction",
        summary: "Perform bulk user action",
        description: "Delete multiple users or change their roles in bulk. Requires 'delete_users' capability for delete, 'promote_users' for role change.",
        tags: ["Admin Users"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["action", "user_ids"],
                properties: [
                    new OA\Property(property: "action", type: "string", enum: ["delete", "change_role"]),
                    new OA\Property(property: "user_ids", type: "array", items: new OA\Items(type: "integer"), example: [2, 3, 5]),
                    new OA\Property(property: "role", type: "string", example: "editor", description: "Required for change_role action"),
                    new OA\Property(property: "reassign_to", type: "integer", example: 1, description: "User ID to reassign content to (for delete)"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Bulk action completed",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string"),
                                new OA\Property(property: "affected", type: "integer"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function bulk(BulkUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $action = $validated['action'];
        $userIds = $validated['user_ids'];

        // Exclude current user from bulk operations
        $currentUserId = $request->user()->id;
        $userIds = array_filter($userIds, fn(int $id) => $id !== $currentUserId);

        if (empty($userIds)) {
            return $this->error('No valid users to process (you cannot include yourself).', 422);
        }

        if ($action === 'delete') {
            return $this->bulkDelete($userIds, $validated['reassign_to'] ?? null);
        }

        return $this->bulkChangeRole($userIds, $validated['role']);
    }

    /**
     * Bulk delete users and optionally reassign their content.
     */
    private function bulkDelete(array $userIds, ?int $reassignTo): JsonResponse
    {
        if ($reassignTo) {
            $targetUser = User::find($reassignTo);

            if (!$targetUser) {
                return $this->error('Reassignment target user not found.', 404);
            }

            Post::whereIn('author_id', $userIds)->update(['author_id' => $targetUser->id]);
        } else {
            $postIds = Post::whereIn('author_id', $userIds)->pluck('id');
            \App\Models\PostMeta::whereIn('post_id', $postIds)->delete();
            Post::whereIn('author_id', $userIds)->delete();
        }

        UserMeta::whereIn('user_id', $userIds)->delete();

        // Revoke tokens for all selected users
        \Laravel\Sanctum\PersonalAccessToken::whereIn('tokenable_id', $userIds)
            ->where('tokenable_type', User::class)
            ->delete();

        $deleted = User::whereIn('id', $userIds)->delete();

        return $this->success([
            'message' => "{$deleted} user(s) deleted successfully.",
            'affected' => $deleted,
        ]);
    }

    /**
     * Bulk change role for selected users.
     */
    private function bulkChangeRole(array $userIds, string $role): JsonResponse
    {
        $users = User::whereIn('id', $userIds)->get();
        $affected = 0;

        foreach ($users as $user) {
            $this->roleService->setUserRole($user, $role);
            $affected++;
        }

        return $this->success([
            'message' => "{$affected} user(s) updated to role '{$role}'.",
            'affected' => $affected,
        ]);
    }
}
