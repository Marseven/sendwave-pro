<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SmsConfig;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SmsConfigController extends Controller
{
    /**
     * Obtenir toutes les configurations SMS
     */
    public function index()
    {
        $configs = SmsConfig::all();

        // Initialiser les configs si vides
        if ($configs->isEmpty()) {
            $this->initializeConfigs();
            $configs = SmsConfig::all();
        }

        return response()->json([
            'message' => 'Configurations SMS récupérées',
            'data' => $configs
        ]);
    }

    /**
     * Obtenir une configuration spécifique
     */
    public function show(string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuration non trouvée'
            ], 404);
        }

        return response()->json([
            'message' => 'Configuration récupérée',
            'data' => $config
        ]);
    }

    /**
     * Mettre à jour une configuration
     */
    public function update(Request $request, string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuration non trouvée'
            ], 404);
        }

        $validated = $request->validate([
            'api_url' => 'nullable|string',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'origin_addr' => 'nullable|string',
            'cost_per_sms' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        $config->update($validated);

        Log::info('SMS Config Updated', [
            'provider' => $provider,
            'is_active' => $config->is_active
        ]);

        return response()->json([
            'message' => 'Configuration mise à jour avec succès',
            'data' => $config
        ]);
    }

    /**
     * Tester une configuration
     */
    public function test(Request $request, string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuration non trouvée'
            ], 404);
        }

        if (!$config->is_active) {
            return response()->json([
                'message' => 'Cette configuration est désactivée'
            ], 400);
        }

        $validated = $request->validate([
            'phone_number' => 'required|string',
            'message' => 'nullable|string',
        ]);

        try {
            // Utiliser le SmsRouter pour envoyer un SMS de test
            $smsRouter = new \App\Services\SMS\SmsRouter();
            $result = $smsRouter->sendSms(
                $validated['phone_number'],
                $validated['message'] ?? 'Test de configuration API ' . strtoupper($provider)
            );

            if ($result['success']) {
                return response()->json([
                    'message' => 'Test réussi',
                    'data' => $result
                ]);
            }

            return response()->json([
                'message' => 'Test échoué',
                'error' => $result['message'] ?? 'Erreur inconnue',
                'data' => $result
            ], 400);

        } catch (\Exception $e) {
            Log::error('SMS Config Test Error', [
                'provider' => $provider,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors du test',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activer/Désactiver une configuration
     */
    public function toggle(Request $request, string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            return response()->json([
                'message' => 'Configuration non trouvée'
            ], 404);
        }

        $config->update([
            'is_active' => !$config->is_active
        ]);

        return response()->json([
            'message' => $config->is_active ? 'Configuration activée' : 'Configuration désactivée',
            'data' => $config
        ]);
    }

    /**
     * Initialiser les configurations par défaut
     */
    protected function initializeConfigs()
    {
        // Airtel
        SmsConfig::firstOrCreate(
            ['provider' => 'airtel'],
            [
                'api_url' => 'https://messaging.airtel.ga:9002/smshttp/qs/',
                'username' => '',
                'password' => '',
                'origin_addr' => '',
                'cost_per_sms' => 20,
                'is_active' => false,
            ]
        );

        // Moov
        SmsConfig::firstOrCreate(
            ['provider' => 'moov'],
            [
                'api_url' => '',
                'username' => '',
                'password' => '',
                'origin_addr' => '',
                'cost_per_sms' => 20,
                'is_active' => false,
            ]
        );
    }
}
