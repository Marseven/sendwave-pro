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
     *
     * @OA\Get(
     *     path="/api/sms-configs",
     *     tags={"SMS Config"},
     *     summary="Get all SMS configurations",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(response=200, description="List of SMS configurations", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
            'data' => $configs->map(fn($config) => $this->formatConfigResponse($config))
        ]);
    }

    /**
     * Obtenir une configuration spécifique
     *
     * @OA\Get(
     *     path="/api/sms-configs/{provider}",
     *     tags={"SMS Config"},
     *     summary="Get a specific SMS provider configuration",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"airtel", "moov"})),
     *     @OA\Response(response=200, description="SMS configuration details", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=404, description="Configuration not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function show(string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            // Initialiser si non trouvé
            $this->initializeConfigs();
            $config = SmsConfig::where('provider', $provider)->first();
        }

        if (!$config) {
            return response()->json([
                'message' => 'Configuration non trouvée'
            ], 404);
        }

        return response()->json([
            'message' => 'Configuration récupérée',
            'data' => $this->formatConfigResponse($config)
        ]);
    }

    /**
     * Formater la réponse de configuration (masquer le mot de passe)
     */
    protected function formatConfigResponse(SmsConfig $config): array
    {
        return [
            'id' => $config->id,
            'provider' => $config->provider,
            'api_url' => $config->api_url,
            'port' => $config->port,
            'username' => $config->username,
            'password_set' => !empty($config->getRawPassword()),
            'origin_addr' => $config->origin_addr,
            'cost_per_sms' => $config->cost_per_sms,
            'is_active' => $config->is_active,
            'created_at' => $config->created_at,
            'updated_at' => $config->updated_at,
        ];
    }

    /**
     * Mettre à jour une configuration
     *
     * @OA\Put(
     *     path="/api/sms-configs/{provider}",
     *     tags={"SMS Config"},
     *     summary="Update an SMS provider configuration",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"airtel", "moov"})),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         @OA\Property(property="api_url", type="string"),
     *         @OA\Property(property="port", type="integer"),
     *         @OA\Property(property="username", type="string"),
     *         @OA\Property(property="password", type="string"),
     *         @OA\Property(property="origin_addr", type="string"),
     *         @OA\Property(property="cost_per_sms", type="integer"),
     *         @OA\Property(property="is_active", type="boolean")
     *     )),
     *     @OA\Response(response=200, description="Configuration updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request, string $provider)
    {
        $validated = $request->validate([
            'api_url' => 'nullable|string',
            'port' => 'nullable|integer|min:1|max:65535',
            'username' => 'nullable|string',
            'password' => 'nullable|string',
            'origin_addr' => 'nullable|string',
            'cost_per_sms' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Ne pas écraser le mot de passe si non fourni ou vide
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        // Créer ou mettre à jour la configuration
        $config = SmsConfig::updateOrCreate(
            ['provider' => $provider],
            $validated
        );

        Log::info('SMS Config Updated', [
            'provider' => $provider,
            'is_active' => $config->is_active
        ]);

        return response()->json([
            'message' => 'Configuration mise à jour avec succès',
            'data' => $this->formatConfigResponse($config)
        ]);
    }

    /**
     * Tester une configuration
     *
     * @OA\Post(
     *     path="/api/sms-configs/{provider}/test",
     *     tags={"SMS Config"},
     *     summary="Send a test SMS using a specific provider configuration",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"airtel", "moov"})),
     *     @OA\RequestBody(required=true, @OA\JsonContent(
     *         required={"phone_number"},
     *         @OA\Property(property="phone_number", type="string", example="24177123456"),
     *         @OA\Property(property="message", type="string", example="Test message")
     *     )),
     *     @OA\Response(response=200, description="Test successful", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=400, description="Test failed or configuration disabled"),
     *     @OA\Response(response=404, description="Configuration not found"),
     *     @OA\Response(response=500, description="Server error during test"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     *
     * @OA\Post(
     *     path="/api/sms-configs/{provider}/toggle",
     *     tags={"SMS Config"},
     *     summary="Toggle activation of an SMS provider configuration",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"airtel", "moov"})),
     *     @OA\Response(response=200, description="Configuration toggled", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=404, description="Configuration not found"),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function toggle(Request $request, string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if (!$config) {
            // Créer la config si elle n'existe pas
            $this->initializeConfigs();
            $config = SmsConfig::where('provider', $provider)->first();
        }

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
            'data' => $this->formatConfigResponse($config)
        ]);
    }

    /**
     * Initialiser les configurations par défaut depuis .env
     */
    protected function initializeConfigs()
    {
        // Airtel - Charger depuis .env
        SmsConfig::firstOrCreate(
            ['provider' => 'airtel'],
            [
                'api_url' => config('sms.airtel.api_url', 'https://messaging.airtel.ga:9002/smshttp/qs/'),
                'port' => null, // Airtel n'utilise pas de port séparé
                'username' => config('sms.airtel.username', ''),
                'password' => config('sms.airtel.password', ''),
                'origin_addr' => config('sms.airtel.origin_addr', ''),
                'cost_per_sms' => config('sms.airtel.cost_per_sms', 20),
                'is_active' => config('sms.airtel.enabled', false),
            ]
        );

        // Moov - Charger depuis .env (SMPP)
        SmsConfig::firstOrCreate(
            ['provider' => 'moov'],
            [
                'api_url' => config('sms.moov.host', '172.16.59.66'),
                'port' => config('sms.moov.port', 12775),
                'username' => config('sms.moov.system_id', ''),
                'password' => config('sms.moov.password', ''),
                'origin_addr' => config('sms.moov.source_addr', 'SENDWAVE'),
                'cost_per_sms' => config('sms.moov.cost_per_sms', 20),
                'is_active' => config('sms.moov.enabled', false),
            ]
        );
    }

    /**
     * Réinitialiser les configurations depuis .env
     *
     * @OA\Post(
     *     path="/api/sms-configs/{provider}/reset",
     *     tags={"SMS Config"},
     *     summary="Reset SMS provider configuration to default .env values",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(name="provider", in="path", required=true, @OA\Schema(type="string", enum={"airtel", "moov"})),
     *     @OA\Response(response=200, description="Configuration reset to defaults", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function reset(string $provider)
    {
        $config = SmsConfig::where('provider', $provider)->first();

        if ($config) {
            $config->delete();
        }

        $this->initializeConfigs();
        $config = SmsConfig::where('provider', $provider)->first();

        return response()->json([
            'message' => 'Configuration réinitialisée depuis les variables d\'environnement',
            'data' => $this->formatConfigResponse($config)
        ]);
    }
}
