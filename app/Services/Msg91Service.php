<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service implements SmsProviderInterface
{
    protected $apiKey;
    protected $senderId;
    protected $route;
    protected $baseUrl = 'https://api.msg91.com/api';

    public function __construct(array $config)
    {
        $this->apiKey = $config['api_key'];
        $this->senderId = $config['sender_id'] ?? null;
        $this->route = $config['config']['route'] ?? '4'; // Default: Transactional
    }

    /**
     * Envoyer un SMS via MSG91
     */
    public function send($to, string $message): array
    {
        try {
            $recipients = is_array($to) ? $to : [$to];

            $response = Http::post($this->baseUrl . '/sendhttp.php', [
                'authkey' => $this->apiKey,
                'mobiles' => implode(',', $recipients),
                'message' => $message,
                'sender' => $this->senderId,
                'route' => $this->route,
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return [
                    'success' => true,
                    'message' => 'SMS envoyé avec succès',
                    'provider' => 'msg91',
                    'recipients_count' => count($recipients),
                    'response' => $data
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de l\'envoi du SMS',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('MSG91 Send Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de l\'envoi',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Tester la connexion MSG91
     */
    public function testConnection(): array
    {
        try {
            // Utiliser l'endpoint de vérification du solde pour tester
            $response = Http::get($this->baseUrl . '/balance.php', [
                'authkey' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'Connexion réussie',
                    'balance' => $response->body()
                ];
            }

            return [
                'success' => false,
                'message' => 'Échec de la connexion',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('MSG91 Test Connection Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur de connexion',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtenir le solde MSG91
     */
    public function getBalance(): array
    {
        try {
            $response = Http::get($this->baseUrl . '/balance.php', [
                'authkey' => $this->apiKey,
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->body()
                ];
            }

            return [
                'success' => false,
                'message' => 'Impossible de récupérer le solde',
                'error' => $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('MSG91 Get Balance Error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Erreur lors de la récupération du solde',
                'error' => $e->getMessage()
            ];
        }
    }
}
