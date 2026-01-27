<?php

namespace App\Http\Middleware;

use App\Models\SubAccount;
use App\Models\User;
use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission  The required permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        // Handle SubAccount authentication
        if ($user instanceof SubAccount) {
            return $this->handleSubAccount($user, $permission, $next, $request);
        }

        // Handle User authentication
        if ($user instanceof User) {
            return $this->handleUser($user, $permission, $next, $request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Type d\'utilisateur non reconnu.',
        ], 403);
    }

    /**
     * Handle permission check for SubAccount
     */
    protected function handleSubAccount(SubAccount $subAccount, string $permission, Closure $next, Request $request): Response
    {
        // Check if sub-account is active
        if ($subAccount->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est suspendu.',
            ], 403);
        }

        // Check if sub-account has required permission
        if (!$subAccount->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Permission refusée. Vous n'avez pas l'autorisation: {$permission}",
            ], 403);
        }

        return $next($request);
    }

    /**
     * Handle permission check for User
     */
    protected function handleUser(User $user, string $permission, Closure $next, Request $request): Response
    {
        // SuperAdmin bypasses all permission checks
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has required permission
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Permission refusée. Vous n'avez pas l'autorisation: {$permission}",
            ], 403);
        }

        return $next($request);
    }
}
