<?php

namespace App\Http\Middleware;

use App\Services\RoleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Parameterised middleware that checks if the authenticated user
 * has a specific WordPress-style capability.
 *
 * Usage in routes: ->middleware('can_do:manage_options')
 */
class CheckCapability
{
    public function __construct(
        private readonly RoleService $roleService,
    ) {}

    public function handle(Request $request, Closure $next, string $capability): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if (!$this->roleService->userCan($user, $capability)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to perform this action.',
            ], 403);
        }

        return $next($request);
    }
}
