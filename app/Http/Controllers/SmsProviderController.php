<?php

namespace App\Http\Controllers;

use App\Models\SmsProvider;
use Illuminate\Http\Request;
use App\Services\SmsProviderFactory;

class SmsProviderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $providers = SmsProvider::orderBy('priority')->get();

        // Masquer les clés API complètes (montrer seulement les derniers caractères)
        $providers->each(function ($provider) {
            if ($provider->api_key) {
                $provider->api_key_preview = '***' . substr($provider->api_key, -4);
            }
        });

        return response()->json([
            'data' => $providers
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|in:msg91,smsala,wapi',
            'name' => 'required|string|max:255',
            'api_key' => 'nullable|string',
            'sender_id' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:1|max:10',
            'cost_per_sms' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        $provider = SmsProvider::updateOrCreate(
            ['code' => $validated['code']],
            $validated
        );

        return response()->json([
            'message' => 'Configuration enregistrée avec succès',
            'data' => $provider
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $code)
    {
        $provider = SmsProvider::where('code', $code)->firstOrFail();

        return response()->json([
            'data' => $provider
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $provider = SmsProvider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'api_key' => 'nullable|string',
            'sender_id' => 'nullable|string|max:255',
            'priority' => 'nullable|integer|min:1|max:10',
            'cost_per_sms' => 'nullable|numeric|min:0',
            'is_active' => 'nullable|boolean',
            'config' => 'nullable|array',
        ]);

        $provider->update($validated);

        return response()->json([
            'message' => 'Provider mis à jour avec succès',
            'data' => $provider
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $provider = SmsProvider::findOrFail($id);
        $provider->delete();

        return response()->json([
            'message' => 'Provider supprimé avec succès'
        ]);
    }

    /**
     * Test la connexion avec le provider
     */
    public function test(Request $request, string $code)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'sender_id' => 'nullable|string',
            'config' => 'nullable|array',
        ]);

        try {
            // Créer une instance du service provider
            $providerService = SmsProviderFactory::make($code, [
                'api_key' => $validated['api_key'],
                'sender_id' => $validated['sender_id'] ?? null,
                'config' => $validated['config'] ?? [],
            ]);

            // Tester la connexion
            $result = $providerService->testConnection();

            if ($result['success']) {
                // Mettre à jour le statut
                $provider = SmsProvider::where('code', $code)->first();
                if ($provider) {
                    $provider->update(['status' => 'connected']);
                }

                return response()->json([
                    'message' => 'Connexion réussie avec ' . ucfirst($code),
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'message' => 'Échec de la connexion',
                    'error' => $result['message'] ?? 'Erreur inconnue'
                ], 400);
            }
        } catch (\Exception $e) {
            // Mettre à jour le statut en erreur
            $provider = SmsProvider::where('code', $code)->first();
            if ($provider) {
                $provider->update(['status' => 'error']);
            }

            return response()->json([
                'message' => 'Erreur lors du test de connexion',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
