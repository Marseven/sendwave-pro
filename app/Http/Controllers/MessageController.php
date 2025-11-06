<?php

namespace App\Http\Controllers;

use App\Services\SMS\SmsRouter;
use App\Services\SMS\OperatorDetector;
use App\Models\Message;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    protected SmsRouter $smsRouter;

    public function __construct()
    {
        $this->smsRouter = new SmsRouter();
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
     * Utilise des queues pour éviter les erreurs 500 sur envois simultanés
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
            $userId = $request->user()->id;

            // Analyser les numéros avant l'envoi
            $analysis = $this->smsRouter->analyzeNumbers($recipients);

            Log::info('SMS Send Request (Queue)', [
                'user_id' => $userId,
                'recipients_count' => count($recipients),
                'message_length' => strlen($message),
                'analysis' => $analysis,
            ]);

            // Calculer le coût estimé
            $smsCount = ceil(strlen($message) / 160);
            $estimatedCostPerSms = 20; // Moyenne Airtel/Moov
            $totalEstimatedCost = count($recipients) * $smsCount * $estimatedCostPerSms;

            // Dispatcher les jobs en arrière-plan
            if (count($recipients) === 1) {
                // Envoi simple
                \App\Jobs\SendSmsJob::dispatch(
                    $userId,
                    $recipients[0],
                    $message
                )->onQueue('sms');

                return response()->json([
                    'message' => 'Message mis en file d\'attente pour envoi',
                    'data' => [
                        'recipients' => 1,
                        'sms_count' => $smsCount,
                        'estimated_cost' => $smsCount * $estimatedCostPerSms,
                        'status' => 'queued',
                        'info' => 'Le message sera envoyé dans quelques secondes. Consultez l\'historique pour le statut.'
                    ]
                ]);
            } else {
                // Envoi en masse
                \App\Jobs\SendBulkSmsJob::dispatch(
                    $userId,
                    $recipients,
                    $message
                )->onQueue('bulk-sms');

                return response()->json([
                    'message' => 'Envoi en masse mis en file d\'attente',
                    'data' => [
                        'total' => count($recipients),
                        'sms_count' => $smsCount,
                        'estimated_cost' => $totalEstimatedCost,
                        'by_operator' => [
                            'airtel' => $analysis['airtel_count'],
                            'moov' => $analysis['moov_count'],
                            'unknown' => $analysis['unknown_count'],
                        ],
                        'status' => 'queued',
                        'info' => 'Les messages seront envoyés progressivement. Consultez l\'historique pour suivre l\'avancement.'
                    ]
                ]);
            }

        } catch (\Exception $e) {
            Log::error('Message Send Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise en file d\'attente',
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
