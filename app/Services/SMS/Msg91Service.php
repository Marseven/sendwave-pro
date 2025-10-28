<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Msg91Service implements SmsServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.msg91.com/api';
    protected string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.msg91.api_key');
        $this->senderId = config('services.msg91.sender_id', 'JOBSMS');
    }

    public function sendSms(string $to, string $message): array
    {
        try {
            $response = Http::post("{$this->baseUrl}/sendhttp.php", [
                'authkey' => $this->apiKey,
                'mobiles' => $to,
                'message' => $message,
                'sender' => $this->senderId,
                'route' => '4',
                'country' => '241'
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Erreur inconnue')
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Send SMS Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendBulkSms(array $recipients, string $message): array
    {
        try {
            $mobiles = implode(',', $recipients);

            $response = Http::post("{$this->baseUrl}/sendhttp.php", [
                'authkey' => $this->apiKey,
                'mobiles' => $mobiles,
                'message' => $message,
                'sender' => $this->senderId,
                'route' => '4',
                'country' => '241'
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('message_id'),
                    'total_sent' => count($recipients),
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Erreur inconnue')
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Bulk SMS Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/v2/reports/{$messageId}/status", [
                'authkey' => $this->apiKey
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'status' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de récupérer le statut'
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Status Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getBalance(): array
    {
        try {
            $response = Http::get("{$this->baseUrl}/balance.php", [
                'authkey' => $this->apiKey
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->body()
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de récupérer le solde'
            ];
        } catch (\Exception $e) {
            Log::error('MSG91 Balance Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
