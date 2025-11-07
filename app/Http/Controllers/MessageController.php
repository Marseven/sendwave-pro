<?php

namespace App\Http\Controllers;

use App\Services\SMS\SmsRouter;
use App\Services\SMS\OperatorDetector;
use App\Services\WebhookService;
use App\Models\Message;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected SmsRouter $smsRouter;
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->smsRouter = new SmsRouter();
        $this->webhookService = $webhookService;
    }

    /**
     * Calculer le coût d'un SMS
     */
    protected function calculateCost(string $message, string $operator = 'airtel'): int
    {
        // Nombre de SMS selon la longueur (160 caractères par SMS)
        $smsCount = ceil(strlen($message) / 160);

        // Coût par SMS selon l'opérateur
        $costPerSms = config("sms.{$operator}.cost_per_sms", config('sms.cost_per_sms', 20));

        return $smsCount * $costPerSms;
    }

    /**
     * Enregistrer un message dans l'historique
     */
    protected function saveMessageToHistory(array $data): Message
    {
        return Message::create([
            'user_id' => $data['user_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_phone' => $data['recipient_phone'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'sms',
            'status' => $data['status'], // 'sent', 'failed', 'pending'
            'provider' => $data['provider'], // 'airtel', 'moov'
            'cost' => $data['cost'],
            'error_message' => $data['error_message'] ?? null,
            'sent_at' => $data['status'] === 'sent' ? now() : null,
            'provider_response' => $data['provider_response'] ?? null,
        ]);
    }

    /**
     * Envoyer un ou plusieurs messages SMS avec routage automatique par opérateur
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:320',
            'type' => 'nullable|in:immediate,scheduled',
        ]);

        try {
            $recipients = $validated['recipients'];
            $message = $validated['message'];

            // Analyser les numéros avant l'envoi
            $analysis = $this->smsRouter->analyzeNumbers($recipients);

            Log::info('SMS Send Request', [
                'recipients_count' => count($recipients),
                'message_length' => strlen($message),
                'analysis' => $analysis,
            ]);

            // Envoyer les messages avec routage automatique
            if (count($recipients) === 1) {
                // Envoi simple
                $result = $this->smsRouter->sendSms($recipients[0], $message);

                // Calculer le coût
                $cost = $this->calculateCost($message, $result['provider'] ?? 'airtel');

                // Enregistrer dans l'historique
                $messageRecord = $this->saveMessageToHistory([
                    'user_id' => $request->user()->id,
                    'recipient_phone' => $result['phone'] ?? $recipients[0],
                    'content' => $message,
                    'status' => $result['success'] ? 'sent' : 'failed',
                    'provider' => $result['provider'] ?? 'unknown',
                    'cost' => $cost,
                    'error_message' => $result['success'] ? null : ($result['message'] ?? 'Erreur inconnue'),
                    'provider_response' => $result,
                ]);

                if ($result['success']) {
                    // Trigger webhook for message.sent
                    $this->webhookService->trigger('message.sent', $request->user()->id, [
                        'message_id' => $messageRecord->id,
                        'recipient' => $result['phone'],
                        'content' => $message,
                        'provider' => $result['provider'],
                        'cost' => $cost,
                    ]);

                    return response()->json([
                        'message' => 'Message envoyé avec succès',
                        'data' => [
                            'message_id' => $messageRecord->id,
                            'provider' => $result['provider'],
                            'phone' => $result['phone'],
                            'sms_count' => ceil(strlen($message) / 160),
                            'cost' => $cost,
                        ]
                    ]);
                } else {
                    // Trigger webhook for message.failed
                    $this->webhookService->trigger('message.failed', $request->user()->id, [
                        'recipient' => $recipients[0],
                        'content' => $message,
                        'error' => $result['message'] ?? 'Erreur inconnue',
                        'provider' => $result['provider'],
                    ]);
                }

                return response()->json([
                    'message' => 'Échec de l\'envoi',
                    'error' => $result['message'] ?? 'Erreur inconnue',
                    'details' => $result,
                ], 400);

            } else {
                // Envoi en masse
                $result = $this->smsRouter->sendBulkSms($recipients, $message);

                // Enregistrer chaque message dans l'historique
                $totalCost = 0;
                $messageIds = [];

                foreach ($result['details'] as $detail) {
                    $cost = $this->calculateCost($message, $detail['provider'] ?? 'airtel');
                    $totalCost += $cost;

                    $messageRecord = $this->saveMessageToHistory([
                        'user_id' => $request->user()->id,
                        'recipient_phone' => $detail['phone'] ?? '',
                        'content' => $message,
                        'status' => $detail['success'] ? 'sent' : 'failed',
                        'provider' => $detail['provider'] ?? 'unknown',
                        'cost' => $cost,
                        'error_message' => $detail['success'] ? null : ($detail['message'] ?? 'Erreur inconnue'),
                        'provider_response' => $detail,
                    ]);

                    $messageIds[] = $messageRecord->id;
                }

                Log::info('Bulk SMS sent', [
                    'total' => $result['total'],
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                    'total_cost' => $totalCost,
                ]);

                return response()->json([
                    'message' => 'Envoi terminé',
                    'data' => [
                        'total' => $result['total'],
                        'sent' => $result['sent'],
                        'failed' => $result['failed'],
                        'sms_count' => ceil(strlen($message) / 160),
                        'total_cost' => $totalCost,
                        'by_operator' => [
                            'airtel' => $analysis['airtel_count'],
                            'moov' => $analysis['moov_count'],
                            'unknown' => $analysis['unknown_count'],
                        ],
                        'message_ids' => $messageIds,
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Message Send Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'envoi du message',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Analyser des numéros de téléphone
     */
    public function analyzeNumbers(Request $request)
    {
        $validated = $request->validate([
            'phone_numbers' => 'required|array|min:1',
            'phone_numbers.*' => 'required|string',
        ]);

        $analysis = $this->smsRouter->analyzeNumbers($validated['phone_numbers']);

        return response()->json([
            'message' => 'Analyse effectuée',
            'data' => $analysis,
        ]);
    }

    /**
     * Obtenir les informations d'un numéro
     */
    public function getNumberInfo(Request $request)
    {
        $validated = $request->validate([
            'phone_number' => 'required|string',
        ]);

        $info = OperatorDetector::getInfo($validated['phone_number']);

        return response()->json([
            'message' => 'Informations du numéro',
            'data' => $info,
        ]);
    }
}
