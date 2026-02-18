<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Requests\Admin\ForgotPasswordRequest;
use App\Http\Requests\Admin\LoginRequest;
use App\Http\Requests\Admin\RegisterRequest;
use App\Http\Requests\Admin\ResetPasswordRequest;
use App\Http\Requests\Admin\UpdateProfileRequest;
use App\Http\Resources\V1\Admin\UserResource;
use App\Mail\NewUserRegistrationMail;
use App\Mail\PasswordResetMail;
use App\Models\Option;
use App\Models\User;
use App\Models\UserMeta;
use App\Services\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use OpenApi\Attributes as OA;

/**
 * Handles all authentication operations for the admin CMS layer.
 *
 * Replicates wp-login.php logic as API endpoints:
 * - Login/logout with Sanctum tokens
 * - Password reset with 24-hour expiry tokens
 * - Self-registration (when enabled via options)
 * - Profile viewing and self-update
 */
#[OA\Tag(name: "Admin Auth", description: "Authentication endpoints for the admin CMS layer")]
class AuthController extends ApiController
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    // ─── Login ───────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/auth/login",
        operationId: "adminLogin",
        summary: "Authenticate admin user",
        description: "Authenticate with username/email and password. Returns a Sanctum bearer token with mapped capabilities as token abilities. Rate limited to 5 attempts per minute per IP.",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["login", "password"],
                properties: [
                    new OA\Property(property: "login", type: "string", example: "admin", description: "Username or email address"),
                    new OA\Property(property: "password", type: "string", example: "password", description: "User password"),
                    new OA\Property(property: "remember_me", type: "boolean", example: false, description: "Extended token lifetime"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Login successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "token", type: "string", example: "1|abc123..."),
                                new OA\Property(property: "user", type: "object", ref: "#/components/schemas/AdminUser"),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Invalid credentials"),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 429, description: "Too many login attempts"),
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
    {
        $loginField = $request->input('login');
        $password = $request->input('password');

        // Resolve user by username or email (WP supports both)
        $user = User::where('login', $loginField)
            ->orWhere('email', $loginField)
            ->first();

        if (!$user) {
            return $this->error(
                "Unknown username. Check again or try your email address.",
                401,
            );
        }

        if (!Hash::check($password, $user->password)) {
            return $this->error(
                "The password you entered for the username {$user->login} is incorrect.",
                401,
            );
        }

        // Map user capabilities to Sanctum token abilities
        $capabilities = $this->roleService->getUserCapabilities($user);
        $abilities = array_keys(array_filter($capabilities));

        $tokenName = $request->boolean('remember_me') ? 'admin-remember' : 'admin-session';
        $token = $user->createToken($tokenName, $abilities);

        $user->load('meta');

        return $this->success([
            'token' => $token->plainTextToken,
            'user' => new UserResource($user),
        ]);
    }

    // ─── Logout ──────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/auth/logout",
        operationId: "adminLogout",
        summary: "Logout current user",
        description: "Revokes the current Sanctum bearer token.",
        tags: ["Admin Auth"],
        security: [["sanctum" => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: "Logged out successfully",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Logged out successfully."),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
        ]
    )]
    public function logout(): JsonResponse
    {
        $user = request()->user();
        $user->currentAccessToken()->delete();

        return $this->success(['message' => 'Logged out successfully.']);
    }

    // ─── Forgot Password ─────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/auth/forgot-password",
        operationId: "adminForgotPassword",
        summary: "Request password reset",
        description: "Generates a password reset token and sends it via email. Token is valid for 24 hours.",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["login"],
                properties: [
                    new OA\Property(property: "login", type: "string", example: "admin", description: "Username or email address"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Reset email sent",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Password reset link has been sent to your email address."),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $loginField = $request->input('login');

        $user = User::where('login', $loginField)
            ->orWhere('email', $loginField)
            ->first();

        if (!$user) {
            return $this->error(
                'There is no account with that username or email address.',
                404,
            );
        }

        // Generate a secure token and store with expiry
        $token = Str::random(64);
        $expiresAt = now()->addHours(24)->toIso8601String();

        UserMeta::updateOrCreate(
            ['user_id' => $user->id, 'meta_key' => 'password_reset_token'],
            ['meta_value' => hash('sha256', $token)],
        );

        UserMeta::updateOrCreate(
            ['user_id' => $user->id, 'meta_key' => 'password_reset_expires'],
            ['meta_value' => $expiresAt],
        );

        Mail::to($user->email)->send(new PasswordResetMail($user, $token));

        return $this->success([
            'message' => 'Password reset link has been sent to your email address.',
        ]);
    }

    // ─── Reset Password ──────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/auth/reset-password",
        operationId: "adminResetPassword",
        summary: "Reset password with token",
        description: "Validates the reset token (24hr expiry), sets the new password, and revokes all existing API tokens.",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["token", "email", "password", "password_confirmation"],
                properties: [
                    new OA\Property(property: "token", type: "string", description: "Reset token from email"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "admin@meemmark.com"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "NewPassword123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "NewPassword123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Password reset successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Password has been reset successfully. Please log in with your new password."),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 400, description: "Invalid or expired token"),
            new OA\Response(response: 404, description: "User not found"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = User::where('email', $request->input('email'))->first();

        if (!$user) {
            return $this->error('Invalid email address.', 404);
        }

        // Retrieve stored token hash and expiry
        $storedHash = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'password_reset_token')
            ->value('meta_value');

        $expiresAt = UserMeta::where('user_id', $user->id)
            ->where('meta_key', 'password_reset_expires')
            ->value('meta_value');

        if (!$storedHash || !$expiresAt) {
            return $this->error('No password reset request found.', 400);
        }

        // Verify token hasn't expired
        if (now()->isAfter($expiresAt)) {
            return $this->error('Password reset token has expired.', 400);
        }

        // Verify token matches
        if (!hash_equals($storedHash, hash('sha256', $request->input('token')))) {
            return $this->error('Invalid password reset token.', 400);
        }

        // Reset password
        $user->update(['password' => Hash::make($request->input('password'))]);

        // Invalidate token
        UserMeta::where('user_id', $user->id)
            ->whereIn('meta_key', ['password_reset_token', 'password_reset_expires'])
            ->delete();

        // Revoke all existing tokens (force re-login)
        $user->tokens()->delete();

        return $this->success([
            'message' => 'Password has been reset successfully. Please log in with your new password.',
        ]);
    }

    // ─── Register ────────────────────────────────────────────────

    #[OA\Post(
        path: "/api/v1/admin/auth/register",
        operationId: "adminRegister",
        summary: "Register a new user",
        description: "Creates a new user account (only if users_can_register option is enabled). Auto-generates a password and assigns the default role. Sends credentials via email.",
        tags: ["Admin Auth"],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["username", "email"],
                properties: [
                    new OA\Property(property: "username", type: "string", example: "johndoe", description: "Unique username (3-60 chars)"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: "Registration successful",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(
                            property: "data",
                            type: "object",
                            properties: [
                                new OA\Property(property: "message", type: "string", example: "Registration complete. Check your email for login credentials."),
                            ]
                        ),
                    ]
                )
            ),
            new OA\Response(response: 403, description: "Registration is disabled"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        // Guard: check if registration is enabled
        $registrationEnabled = Option::get('users_can_register', '0');

        if ($registrationEnabled !== '1') {
            return $this->error('User registration is currently not allowed.', 403);
        }

        $username = $request->input('username');
        $email = $request->input('email');
        $plainPassword = Str::random(16);
        $defaultRole = Option::get('default_role', 'subscriber');

        $user = User::create([
            'name' => $username,
            'login' => $username,
            'nicename' => Str::slug($username),
            'email' => $email,
            'password' => Hash::make($plainPassword),
            'display_name' => $username,
            'registered_at' => now(),
            'url' => '',
            'activation_key' => '',
            'status' => 0,
        ]);

        // Assign default role
        $this->roleService->setUserRole($user, $defaultRole);

        // Seed default user meta
        $defaultMeta = [
            'nickname' => $username,
            'first_name' => '',
            'last_name' => '',
            'description' => '',
            'rich_editing' => 'true',
            'admin_color' => 'fresh',
        ];

        foreach ($defaultMeta as $key => $value) {
            UserMeta::create([
                'user_id' => $user->id,
                'meta_key' => $key,
                'meta_value' => $value,
            ]);
        }

        Mail::to($email)->send(new NewUserRegistrationMail($user, $plainPassword));

        return $this->success([
            'message' => 'Registration complete. Check your email for login credentials.',
        ], 201);
    }

    // ─── Get Profile ─────────────────────────────────────────────

    #[OA\Get(
        path: "/api/v1/admin/auth/me",
        operationId: "adminGetProfile",
        summary: "Get current user profile",
        description: "Returns the authenticated user's profile with role, capabilities, and avatar URL.",
        tags: ["Admin Auth"],
        security: [["sanctum" => []]],
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
        ]
    )]
    public function me(): JsonResponse
    {
        $user = request()->user();
        $user->load('meta');

        return $this->success(new UserResource($user));
    }

    // ─── Update Profile ──────────────────────────────────────────

    #[OA\Put(
        path: "/api/v1/admin/auth/me",
        operationId: "adminUpdateProfile",
        summary: "Update current user profile",
        description: "Update the authenticated user's own profile fields. Supports display_name, email, url, first_name, last_name, nickname, bio, and password.",
        tags: ["Admin Auth"],
        security: [["sanctum" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "first_name", type: "string", example: "John"),
                    new OA\Property(property: "last_name", type: "string", example: "Doe"),
                    new OA\Property(property: "nickname", type: "string", example: "johnd"),
                    new OA\Property(property: "display_name", type: "string", example: "John Doe"),
                    new OA\Property(property: "email", type: "string", format: "email", example: "john@example.com"),
                    new OA\Property(property: "url", type: "string", example: "https://example.com"),
                    new OA\Property(property: "bio", type: "string", example: "A short biography"),
                    new OA\Property(property: "password", type: "string", format: "password", example: "NewPassword123"),
                    new OA\Property(property: "password_confirmation", type: "string", format: "password", example: "NewPassword123"),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: "Profile updated",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "success", type: "boolean", example: true),
                        new OA\Property(property: "data", ref: "#/components/schemas/AdminUser"),
                    ]
                )
            ),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 422, description: "Validation error"),
        ]
    )]
    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        // Separate model fields from meta fields
        $modelFields = ['display_name', 'email', 'url'];
        $metaFields = ['first_name', 'last_name', 'nickname', 'bio'];

        // Update model fields directly
        $modelUpdates = array_intersect_key($validated, array_flip($modelFields));

        if (isset($validated['password'])) {
            $modelUpdates['password'] = Hash::make($validated['password']);
        }

        if (!empty($modelUpdates)) {
            $user->update($modelUpdates);
        }

        // Update meta fields via user_meta table
        foreach ($metaFields as $field) {
            if (!isset($validated[$field])) {
                continue;
            }

            // Map 'bio' to WP's 'description' meta key
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
}
