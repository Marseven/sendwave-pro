<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminOnly
{
    /**
     * Restrict access to SuperAdmin users only.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié.',
            ], 401);
        }

        if (!($user instanceof User) || !$user->isSuperAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Accès réservé au Super Administrateur.',
            ], 403);
        }

        return $next($request);
    }
}
