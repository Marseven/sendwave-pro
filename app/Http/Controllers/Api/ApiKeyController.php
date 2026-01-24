<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Liste des clés API de l'utilisateur
     */
    public function index(Request $request)
    {
        $apiKeys = ApiKey::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($key) => $this->formatApiKey($key));

        return response()->json([
            'message' => 'Clés API récupérées',
            'data' => $apiKeys
        ]);
    }

    /**
     * Créer une nouvelle clé API
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'nullable|in:production,test',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:send_sms,view_history,manage_contacts,view_balance',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
        ]);

        // Générer une clé unique
        $prefix = ($validated['type'] ?? 'production') === 'test' ? 'sk_test_' : 'sk_live_';
        $key = $prefix . Str::random(32);

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'key' => $key,
            'provider' => $validated['type'] ?? 'production',
            'permissions' => $validated['permissions'] ?? ['send_sms', 'view_history'],
            'rate_limit' => $validated['rate_limit'] ?? 100,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Clé API créée avec succès',
            'data' => $this->formatApiKey($apiKey, true) // Show full key only on creation
        ], 201);
    }

    /**
     * Afficher une clé API
     */
    public function show(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json([
            'message' => 'Clé API récupérée',
            'data' => $this->formatApiKey($apiKey)
        ]);
    }

    /**
     * Mettre à jour une clé API
     */
    public function update(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:send_sms,view_history,manage_contacts,view_balance',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
        ]);

        $apiKey->update($validated);

        return response()->json([
            'message' => 'Clé API mise à jour',
            'data' => $this->formatApiKey($apiKey)
        ]);
    }

    /**
     * Révoquer une clé API
     */
    public function revoke(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $apiKey->update(['is_active' => false]);

        return response()->json([
            'message' => 'Clé API révoquée',
            'data' => $this->formatApiKey($apiKey)
        ]);
    }

    /**
     * Supprimer une clé API
     */
    public function destroy(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $apiKey->delete();

        return response()->json([
            'message' => 'Clé API supprimée'
        ]);
    }

    /**
     * Régénérer une clé API
     */
    public function regenerate(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $prefix = $apiKey->provider === 'test' ? 'sk_test_' : 'sk_live_';
        $newKey = $prefix . Str::random(32);

        $apiKey->update([
            'key' => $newKey,
            'is_active' => true
        ]);

        return response()->json([
            'message' => 'Clé API régénérée',
            'data' => $this->formatApiKey($apiKey, true)
        ]);
    }

    /**
     * Formater la réponse d'une clé API
     */
    protected function formatApiKey(ApiKey $apiKey, bool $showFullKey = false): array
    {
        $key = $apiKey->key;

        // Masquer la clé sauf lors de la création ou régénération
        if (!$showFullKey) {
            $key = substr($key, 0, 12) . '...' . substr($key, -4);
        }

        return [
            'id' => $apiKey->id,
            'name' => $apiKey->name,
            'key' => $key,
            'full_key' => $showFullKey ? $apiKey->key : null,
            'type' => $apiKey->provider === 'test' ? 'test' : 'production',
            'status' => $apiKey->is_active ? 'active' : 'revoked',
            'permissions' => $apiKey->permissions ?? ['send_sms', 'view_history'],
            'rate_limit' => $apiKey->rate_limit ?? 100,
            'last_used_at' => $apiKey->last_used?->toISOString(),
            'created_at' => $apiKey->created_at->toISOString(),
        ];
    }
}
