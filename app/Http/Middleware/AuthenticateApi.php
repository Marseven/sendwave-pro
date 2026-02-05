<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApi
{
    /**
     * Authenticate via X-API-Key header or fall back to Sanctum Bearer token.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Try API Key authentication
        if ($apiKeyValue = $request->header('X-API-Key')) {
            return $this->authenticateViaApiKey($request, $next, $apiKeyValue);
        }

        // 2. Fall back to Sanctum (Bearer token)
        $sanctumUser = Auth::guard('sanctum')->user();

        if ($sanctumUser) {
            $request->setUserResolver(fn() => $sanctumUser);
            return $next($request);
        }

        return response()->json([
            'success' => false,
            'message' => 'Non authentifié. Fournissez un Bearer token ou un header X-API-Key.',
        ], 401);
    }

    /**
     * Authenticate using an API key.
     */
    protected function authenticateViaApiKey(Request $request, Closure $next, string $apiKeyValue): Response
    {
        $apiKey = ApiKey::where('key', $apiKeyValue)
            ->where('is_active', true)
            ->first();

        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'message' => 'Clé API invalide ou révoquée.',
            ], 401);
        }

        // Check IP whitelist
        if (!$apiKey->isIpAllowed($request->ip())) {
            return response()->json([
                'success' => false,
                'message' => 'Adresse IP non autorisée pour cette clé API.',
            ], 403);
        }

        // Always resolve to the parent User for data scoping compatibility
        $user = $apiKey->user;

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Utilisateur associé à la clé API introuvable.',
            ], 401);
        }

        // If linked to a SubAccount, validate it and store separately for budget/credit checks
        if ($apiKey->sub_account_id) {
            $subAccount = $apiKey->subAccount;

            if (!$subAccount) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sous-compte associé à la clé API introuvable.',
                ], 401);
            }

            if ($subAccount->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Sous-compte suspendu ou inactif.',
                    'error' => 'ACCOUNT_BLOCKED',
                ], 403);
            }

            $request->attributes->set('sub_account', $subAccount);
        }

        // Update last used timestamp
        $apiKey->markAsUsed();

        // Set parent User as authenticated entity (preserves data scoping across all controllers)
        $request->setUserResolver(fn() => $user);

        // Store the API key on the request for permission checks
        $request->attributes->set('api_key', $apiKey);

        return $next($request);
    }
}
