<?php

namespace App\Http\Middleware;

use App\Models\SubAccount;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubAccountPermission
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

        // If regular user (not sub-account), allow all
        if (!$user instanceof SubAccount) {
            return $next($request);
        }

        // Check if sub-account is active
        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Votre compte est suspendu.',
            ], 403);
        }

        // Check if sub-account has required permission
        if (!$user->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => "Permission refusée. Vous n'avez pas l'autorisation: {$permission}",
            ], 403);
        }

        return $next($request);
    }
}
