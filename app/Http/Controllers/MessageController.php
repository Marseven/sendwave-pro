<?php

namespace App\Http\Controllers;

use App\Models\SmsProvider;
use App\Services\SmsProviderFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    /**
     * Envoyer un ou plusieurs messages SMS
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:320',
            'type' => 'nullable|in:immediate,scheduled',
            'provider_code' => 'nullable|string|in:msg91,smsala,wapi',
        ]);

        try {
            // Déterminer quel provider utiliser
            $provider = $this->selectProvider($validated['provider_code'] ?? null);

            if (!$provider) {
                return response()->json([
                    'message' => 'Aucun provider SMS actif configuré',
                ], 400);
            }

            // Créer une instance du service
            $providerService = SmsProviderFactory::make($provider->code, [
                'api_key' => $provider->api_key,
                'sender_id' => $provider->sender_id,
                'config' => $provider->config ?? [],
            ]);

            // Envoyer le message
            $result = $providerService->send(
                $validated['recipients'],
                $validated['message']
            );

            if ($result['success']) {
                // Log de l'envoi réussi
                Log::info('SMS sent successfully', [
                    'provider' => $provider->code,
                    'recipients_count' => count($validated['recipients']),
                    'message_length' => strlen($validated['message']),
                ]);

                return response()->json([
                    'message' => 'Messages envoyés avec succès',
                    'data' => [
                        'provider' => $provider->code,
                        'recipients_count' => count($validated['recipients']),
                        'sms_count' => ceil(strlen($validated['message']) / 160),
                        'cost' => count($validated['recipients']) * ceil(strlen($validated['message']) / 160) * $provider->cost_per_sms,
                    ]
                ]);
            }

            return response()->json([
                'message' => 'Échec de l\'envoi',
                'error' => $result['message'] ?? 'Erreur inconnue'
            ], 400);

        } catch (\Exception $e) {
            Log::error('Message Send Error: ' . $e->getMessage());

            return response()->json([
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sélectionner le meilleur provider disponible
     */
    protected function selectProvider(?string $preferredCode = null): ?SmsProvider
    {
        // Si un provider spécifique est demandé
        if ($preferredCode) {
            $provider = SmsProvider::where('code', $preferredCode)
                ->where('is_active', true)
                ->first();

            if ($provider) {
                return $provider;
            }
        }

        // Sinon, prendre le provider actif avec la plus haute priorité
        return SmsProvider::active()
            ->orderedByPriority()
            ->first();
    }
}
