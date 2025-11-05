<?php

namespace App\Http\Controllers;

use App\Services\SMS\SmsRouter;
use App\Services\SMS\OperatorDetector;
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

                if ($result['success']) {
                    return response()->json([
                        'message' => 'Message envoyé avec succès',
                        'data' => [
                            'provider' => $result['provider'],
                            'phone' => $result['phone'],
                            'sms_count' => ceil(strlen($message) / 160),
                        ]
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

                Log::info('Bulk SMS sent', [
                    'total' => $result['total'],
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                ]);

                return response()->json([
                    'message' => 'Envoi terminé',
                    'data' => [
                        'total' => $result['total'],
                        'sent' => $result['sent'],
                        'failed' => $result['failed'],
                        'sms_count' => ceil(strlen($message) / 160),
                        'by_operator' => [
                            'airtel' => $analysis['airtel_count'],
                            'moov' => $analysis['moov_count'],
                            'unknown' => $analysis['unknown_count'],
                        ],
                        'details' => $result['details'],
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
