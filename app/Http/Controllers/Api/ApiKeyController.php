<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\SubAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiKeyController extends Controller
{
    /**
     * Liste des clés API de l'utilisateur
     *
     * @OA\Get(
     *     path="/api/api-keys",
     *     tags={"API Keys"},
     *     summary="List all API keys for the authenticated user",
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $apiKeys = ApiKey::where('user_id', $request->user()->id)
            ->with('subAccount:id,name')
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
     *
     * @OA\Post(
     *     path="/api/api-keys",
     *     tags={"API Keys"},
     *     summary="Create a new API key",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"name"},
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="type", type="string", enum={"production","test"}),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string", enum={"send_sms","view_history","manage_contacts","view_balance"})),
     *         @OA\Property(property="rate_limit", type="integer", minimum=1, maximum=10000)
     *     )),
     *     @OA\Response(response=201, description="API key created", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'sub_account_id' => 'required|integer|exists:sub_accounts,id',
            'type' => 'nullable|in:production,test',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:send_sms,view_history,manage_contacts,view_balance',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'required|ip',
        ]);

        // Vérifier que le sous-compte appartient à l'utilisateur
        $subAccount = SubAccount::where('parent_user_id', $request->user()->id)
            ->findOrFail($validated['sub_account_id']);

        // Générer une clé unique
        $prefix = ($validated['type'] ?? 'production') === 'test' ? 'sk_test_' : 'sk_live_';
        $key = $prefix . Str::random(32);

        $apiKey = ApiKey::create([
            'user_id' => $request->user()->id,
            'sub_account_id' => $subAccount->id,
            'name' => $validated['name'],
            'key' => $key,
            'provider' => $validated['type'] ?? 'production',
            'permissions' => $validated['permissions'] ?? ['send_sms', 'view_history'],
            'rate_limit' => $validated['rate_limit'] ?? 100,
            'allowed_ips' => $validated['allowed_ips'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Clé API créée avec succès',
            'data' => $this->formatApiKey($apiKey, true) // Show full key only on creation
        ], 201);
    }

    /**
     * Afficher une clé API
     *
     * @OA\Get(
     *     path="/api/api-keys/{id}",
     *     tags={"API Keys"},
     *     summary="Get a specific API key",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="API key not found")
     * )
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
     *
     * @OA\Put(
     *     path="/api/api-keys/{id}",
     *     tags={"API Keys"},
     *     summary="Update an API key",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="name", type="string", maxLength=255),
     *         @OA\Property(property="permissions", type="array", @OA\Items(type="string", enum={"send_sms","view_history","manage_contacts","view_balance"})),
     *         @OA\Property(property="rate_limit", type="integer", minimum=1, maximum=10000)
     *     )),
     *     @OA\Response(response=200, description="API key updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="API key not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, string $id)
    {
        $apiKey = ApiKey::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'sub_account_id' => 'nullable|integer|exists:sub_accounts,id',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string|in:send_sms,view_history,manage_contacts,view_balance',
            'rate_limit' => 'nullable|integer|min:1|max:10000',
            'allowed_ips' => 'nullable|array',
            'allowed_ips.*' => 'required|ip',
        ]);

        // Vérifier que le sous-compte appartient à l'utilisateur
        if (isset($validated['sub_account_id'])) {
            SubAccount::where('parent_user_id', $request->user()->id)
                ->findOrFail($validated['sub_account_id']);
        }

        $apiKey->update($validated);

        return response()->json([
            'message' => 'Clé API mise à jour',
            'data' => $this->formatApiKey($apiKey)
        ]);
    }

    /**
     * Révoquer une clé API
     *
     * @OA\Post(
     *     path="/api/api-keys/{id}/revoke",
     *     tags={"API Keys"},
     *     summary="Revoke an API key",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="API key revoked", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="API key not found")
     * )
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
     *
     * @OA\Delete(
     *     path="/api/api-keys/{id}",
     *     tags={"API Keys"},
     *     summary="Delete an API key",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="API key deleted", @OA\JsonContent(
     *         @OA\Property(property="message", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="API key not found")
     * )
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
     *
     * @OA\Post(
     *     path="/api/api-keys/{id}/regenerate",
     *     tags={"API Keys"},
     *     summary="Regenerate an API key with a new secret",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response=200, description="API key regenerated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="API key not found")
     * )
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
            'sub_account_id' => $apiKey->sub_account_id,
            'sub_account_name' => $apiKey->subAccount?->name,
            'permissions' => $apiKey->permissions ?? ['send_sms', 'view_history'],
            'rate_limit' => $apiKey->rate_limit ?? 100,
            'allowed_ips' => $apiKey->allowed_ips,
            'last_used_at' => $apiKey->last_used?->toISOString(),
            'created_at' => $apiKey->created_at->toISOString(),
        ];
    }
}
