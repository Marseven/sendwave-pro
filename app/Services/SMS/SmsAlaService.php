<?php

namespace App\Services\SMS;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsAlaService implements SmsServiceInterface
{
    protected string $apiKey;
    protected string $baseUrl = 'https://api.smsala.com/api/v1';
    protected string $senderId;

    public function __construct()
    {
        $this->apiKey = config('services.smsala.api_key');
        $this->senderId = config('services.smsala.sender_id', 'JOBSMS');
    }

    public function sendSms(string $to, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/sms/send", [
                'to' => $to,
                'message' => $message,
                'sender_id' => $this->senderId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('data.message_id'),
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Erreur inconnue')
            ];
        } catch (\Exception $e) {
            Log::error('SMSALA Send SMS Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function sendBulkSms(array $recipients, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json'
            ])->post("{$this->baseUrl}/sms/bulk", [
                'recipients' => $recipients,
                'message' => $message,
                'sender_id' => $this->senderId
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message_id' => $response->json('data.message_id'),
                    'total_sent' => count($recipients),
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'error' => $response->json('message', 'Erreur inconnue')
            ];
        } catch (\Exception $e) {
            Log::error('SMSALA Bulk SMS Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getMessageStatus(string $messageId): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->get("{$this->baseUrl}/sms/status/{$messageId}");

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
            Log::error('SMSALA Status Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    public function getBalance(): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey
            ])->get("{$this->baseUrl}/account/balance");

            if ($response->successful()) {
                return [
                    'success' => true,
                    'balance' => $response->json('data.balance')
                ];
            }

            return [
                'success' => false,
                'error' => 'Impossible de récupérer le solde'
            ];
        } catch (\Exception $e) {
            Log::error('SMSALA Balance Error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
