<?php

namespace App\Http\Controllers;

use App\Enums\MessageStatus;
use App\Services\SMS\SmsRouter;
use App\Services\SMS\OperatorDetector;
use App\Services\WebhookService;
use App\Services\AnalyticsService;
use App\Services\AnalyticsRecordService;
use App\Services\BudgetService;
use App\Services\StopWordService;
use App\Services\MessageVariableService;
use App\Models\Message;
use App\Models\Contact;
use App\Models\ContactGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MessageController extends Controller
{
    public function __construct(
        protected SmsRouter $smsRouter,
        protected WebhookService $webhookService,
        protected AnalyticsService $analyticsService,
        protected AnalyticsRecordService $analyticsRecordService,
        protected BudgetService $budgetService,
        protected StopWordService $stopWordService,
        protected MessageVariableService $messageVariableService
    ) {}

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
     * Normaliser un numéro de téléphone pour la recherche
     * Supprime les espaces, tirets, et préfixe +
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Supprimer tous les caractères non numériques
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Si commence par 00, remplacer par rien (format international sans +)
        if (str_starts_with($cleaned, '00')) {
            $cleaned = substr($cleaned, 2);
        }

        return $cleaned;
    }

    /**
     * Trouver un contact par numéro de téléphone
     */
    protected function findContactByPhone(int $userId, string $phone): ?Contact
    {
        $normalizedPhone = $this->normalizePhoneNumber($phone);

        // Recherche avec différentes variations du numéro
        return Contact::where('user_id', $userId)
            ->where(function ($query) use ($normalizedPhone, $phone) {
                // Numéro exact
                $query->where('phone', $phone)
                    // Numéro nettoyé
                    ->orWhere('phone', $normalizedPhone)
                    // Avec préfixe +
                    ->orWhere('phone', '+' . $normalizedPhone)
                    // Format Gabon: 241XXXXXXXX
                    ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8));
            })
            ->first();
    }

    /**
     * Construire les données du contact pour le message
     */
    protected function getContactData(int $userId, string $phone): array
    {
        $contact = $this->findContactByPhone($userId, $phone);

        return [
            'contact_id' => $contact?->id,
            'recipient_name' => $contact?->name,
            'contact' => $contact,
        ];
    }

    /**
     * Check if a message template contains variables
     */
    protected function hasVariables(string $message): bool
    {
        return (bool) preg_match('/\{[^}]+\}/', $message);
    }

    /**
     * Personalize a message for a specific contact
     */
    protected function personalizeMessage(string $message, ?Contact $contact): string
    {
        if (!$contact || !$this->hasVariables($message)) {
            return $message;
        }

        return $this->messageVariableService->replaceVariables($message, $contact);
    }

    /**
     * Enregistrer un message dans l'historique
     */
    protected function saveMessageToHistory(array $data): Message
    {
        $status = $data['status'];
        $isSent = $status === MessageStatus::SENT->value || $status === 'sent';

        return Message::create([
            'user_id' => $data['user_id'],
            'campaign_id' => $data['campaign_id'] ?? null,
            'contact_id' => $data['contact_id'] ?? null,
            'recipient_name' => $data['recipient_name'] ?? null,
            'recipient_phone' => $data['recipient_phone'],
            'content' => $data['content'],
            'type' => $data['type'] ?? 'sms',
            'status' => $status,
            'provider' => $data['provider'],
            'cost' => $data['cost'],
            'error_message' => $data['error_message'] ?? null,
            'sent_at' => $isSent ? now() : null,
            'provider_response' => $data['provider_response'] ?? null,
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/messages/send",
     *     tags={"Messages"},
     *     summary="Send SMS message(s)",
     *     description="Envoyer un ou plusieurs messages SMS avec routage automatique par opérateur. Les numéros blacklistés sont automatiquement filtrés.",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"message"},
     *             @OA\Property(property="recipients", type="array", @OA\Items(type="string"), description="Numéros de téléphone", example={"77123456", "60123456"}),
     *             @OA\Property(property="contact_ids", type="array", @OA\Items(type="integer"), description="IDs de contacts existants", example={1, 2, 3}),
     *             @OA\Property(property="group_ids", type="array", @OA\Items(type="integer"), description="IDs de groupes de contacts", example={1}),
     *             @OA\Property(property="message", type="string", maxLength=320, example="Bonjour, ceci est un message de test."),
     *             @OA\Property(property="type", type="string", enum={"immediate", "scheduled"}, example="immediate")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Message(s) envoyé(s) avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Message envoyé avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="message_id", type="integer", example=1),
     *                 @OA\Property(property="provider", type="string", example="airtel"),
     *                 @OA\Property(property="phone", type="string", example="24177123456"),
     *                 @OA\Property(property="sms_count", type="integer", example=1),
     *                 @OA\Property(property="cost", type="integer", example=20),
     *                 @OA\Property(property="blacklisted_skipped", type="integer", example=0)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Échec de l'envoi ou aucun destinataire valide",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Aucun destinataire valide"),
     *             @OA\Property(property="error", type="string", example="Tous les destinataires sont dans la liste noire")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(
     *         response=500,
     *         description="Erreur serveur",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Erreur lors de l'envoi du message"),
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function send(Request $request)
    {
        $validated = $request->validate([
            'recipients' => 'nullable|array',
            'recipients.*' => 'required|string',
            'contact_ids' => 'nullable|array',
            'contact_ids.*' => 'required|integer|exists:contacts,id',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'required|integer|exists:contact_groups,id',
            'message' => 'required|string|max:320',
            'type' => 'nullable|in:immediate,scheduled',
        ]);

        // At least one source of recipients is required
        if (empty($validated['recipients']) && empty($validated['contact_ids']) && empty($validated['group_ids'])) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => [
                    'recipients' => ['Au moins un des champs recipients, contact_ids ou group_ids est requis.'],
                ],
            ], 422);
        }

        try {
            $message = $validated['message'];
            $userId = $request->user()->id;

            // Resolve all phone numbers from the three sources
            $phoneNumbers = collect($validated['recipients'] ?? []);

            // Resolve contact_ids to phone numbers
            if (!empty($validated['contact_ids'])) {
                $contactPhones = Contact::where('user_id', $userId)
                    ->whereIn('id', $validated['contact_ids'])
                    ->pluck('phone');
                $phoneNumbers = $phoneNumbers->merge($contactPhones);
            }

            // Resolve group_ids to phone numbers
            if (!empty($validated['group_ids'])) {
                $groupPhones = Contact::where('user_id', $userId)
                    ->whereHas('groups', function ($query) use ($validated) {
                        $query->whereIn('contact_groups.id', $validated['group_ids']);
                    })
                    ->pluck('phone');
                $phoneNumbers = $phoneNumbers->merge($groupPhones);
            }

            // Deduplicate and filter empty values
            $recipients = $phoneNumbers->filter()->unique()->values()->all();

            if (empty($recipients)) {
                return response()->json([
                    'message' => 'Aucun destinataire valide',
                    'error' => 'Aucun numéro de téléphone trouvé pour les critères fournis.',
                ], 400);
            }

            // Filter out blacklisted numbers
            $blacklistFilter = $this->stopWordService->filterBlacklisted($userId, $recipients);
            $recipients = $blacklistFilter['allowed'];
            $blacklistedCount = $blacklistFilter['filtered_count'];

            // If all recipients are blacklisted
            if (empty($recipients)) {
                return response()->json([
                    'message' => 'Aucun destinataire valide',
                    'error' => 'Tous les destinataires sont dans la liste noire',
                    'blacklisted_count' => $blacklistedCount,
                    'blacklisted_numbers' => $blacklistFilter['filtered'],
                ], 400);
            }

            // Analyser les numéros avant l'envoi
            $analysis = $this->smsRouter->analyzeNumbers($recipients);

            Log::info('SMS Send Request', [
                'recipients_count' => count($recipients),
                'message_length' => strlen($message),
                'analysis' => $analysis,
                'blacklisted_count' => $blacklistedCount,
            ]);

            // Check if message contains variables for personalization
            $messageHasVariables = $this->hasVariables($message);

            // Envoyer les messages avec routage automatique
            if (count($recipients) === 1) {
                // Envoi simple — personalize if needed
                $contactData = $this->getContactData($userId, $recipients[0]);
                $personalizedMessage = $this->personalizeMessage($message, $contactData['contact']);

                $result = $this->smsRouter->sendSms($recipients[0], $personalizedMessage);

                // Calculer le coût
                $cost = $this->calculateCost($personalizedMessage, $result['provider'] ?? 'airtel');

                $recipientPhone = $result['phone'] ?? $recipients[0];

                // Enregistrer dans l'historique
                $messageRecord = $this->saveMessageToHistory([
                    'user_id' => $userId,
                    'contact_id' => $contactData['contact_id'],
                    'recipient_name' => $contactData['recipient_name'],
                    'recipient_phone' => $recipientPhone,
                    'content' => $personalizedMessage,
                    'status' => $result['success'] ? MessageStatus::SENT->value : MessageStatus::FAILED->value,
                    'provider' => $result['provider'] ?? 'unknown',
                    'cost' => $cost,
                    'error_message' => $result['success'] ? null : ($result['message'] ?? 'Erreur inconnue'),
                    'provider_response' => $result,
                ]);

                // Enregistrer dans sms_analytics pour la comptabilité
                $this->analyticsRecordService->recordSms($messageRecord, [
                    'message_type' => $request->input('type', 'transactional'),
                ]);

                if ($result['success']) {
                    // Trigger webhook for message.sent
                    $this->webhookService->trigger('message.sent', $userId, [
                        'message_id' => $messageRecord->id,
                        'recipient' => $result['phone'],
                        'content' => $message,
                        'provider' => $result['provider'],
                        'cost' => $cost,
                    ]);

                    // Update daily analytics
                    $this->analyticsService->updateDailyAnalytics($userId);

                    return response()->json([
                        'message' => 'Message envoyé avec succès',
                        'data' => [
                            'message_id' => $messageRecord->id,
                            'provider' => $result['provider'],
                            'phone' => $result['phone'],
                            'sms_count' => ceil(strlen($message) / 160),
                            'cost' => $cost,
                            'blacklisted_skipped' => $blacklistedCount,
                        ]
                    ]);
                } else {
                    // Trigger webhook for message.failed
                    $this->webhookService->trigger('message.failed', $userId, [
                        'recipient' => $recipients[0],
                        'content' => $message,
                        'error' => $result['message'] ?? 'Erreur inconnue',
                        'provider' => $result['provider'],
                    ]);

                    // Update daily analytics (even for failed messages)
                    $this->analyticsService->updateDailyAnalytics($userId);
                }

                return response()->json([
                    'message' => 'Échec de l\'envoi',
                    'error' => $result['message'] ?? 'Erreur inconnue',
                    'details' => $result,
                ], 400);

            } else {
                // Envoi en masse
                $totalCost = 0;
                $messageIds = [];
                $contactCache = [];
                $sentCount = 0;
                $failedCount = 0;

                if ($messageHasVariables) {
                    // Personalized sending: send individually to replace variables per contact
                    foreach ($recipients as $recipientPhone) {
                        if (!isset($contactCache[$recipientPhone])) {
                            $contactCache[$recipientPhone] = $this->getContactData($userId, $recipientPhone);
                        }
                        $contactData = $contactCache[$recipientPhone];

                        $personalizedMessage = $this->personalizeMessage($message, $contactData['contact']);
                        $detail = $this->smsRouter->sendSms($recipientPhone, $personalizedMessage);

                        $cost = $this->calculateCost($personalizedMessage, $detail['provider'] ?? 'airtel');
                        $totalCost += $cost;

                        $phone = $detail['phone'] ?? $recipientPhone;

                        $messageRecord = $this->saveMessageToHistory([
                            'user_id' => $userId,
                            'contact_id' => $contactData['contact_id'],
                            'recipient_name' => $contactData['recipient_name'],
                            'recipient_phone' => $phone,
                            'content' => $personalizedMessage,
                            'status' => $detail['success'] ? MessageStatus::SENT->value : MessageStatus::FAILED->value,
                            'provider' => $detail['provider'] ?? 'unknown',
                            'cost' => $cost,
                            'error_message' => $detail['success'] ? null : ($detail['message'] ?? 'Erreur inconnue'),
                            'provider_response' => $detail,
                        ]);

                        $this->analyticsRecordService->recordSms($messageRecord, [
                            'message_type' => $request->input('type', 'transactional'),
                        ]);

                        $messageIds[] = $messageRecord->id;
                        $detail['success'] ? $sentCount++ : $failedCount++;
                    }
                } else {
                    // No variables: use bulk send for performance
                    $result = $this->smsRouter->sendBulkSms($recipients, $message);

                    foreach ($result['details'] as $detail) {
                        $cost = $this->calculateCost($message, $detail['provider'] ?? 'airtel');
                        $totalCost += $cost;

                        $recipientPhone = $detail['phone'] ?? '';

                        if (!isset($contactCache[$recipientPhone])) {
                            $contactCache[$recipientPhone] = $this->getContactData($userId, $recipientPhone);
                        }
                        $contactData = $contactCache[$recipientPhone];

                        $messageRecord = $this->saveMessageToHistory([
                            'user_id' => $userId,
                            'contact_id' => $contactData['contact_id'],
                            'recipient_name' => $contactData['recipient_name'],
                            'recipient_phone' => $recipientPhone,
                            'content' => $message,
                            'status' => $detail['success'] ? MessageStatus::SENT->value : MessageStatus::FAILED->value,
                            'provider' => $detail['provider'] ?? 'unknown',
                            'cost' => $cost,
                            'error_message' => $detail['success'] ? null : ($detail['message'] ?? 'Erreur inconnue'),
                            'provider_response' => $detail,
                        ]);

                        $this->analyticsRecordService->recordSms($messageRecord, [
                            'message_type' => $request->input('type', 'transactional'),
                        ]);

                        $messageIds[] = $messageRecord->id;
                        $detail['success'] ? $sentCount++ : $failedCount++;
                    }
                }

                Log::info('Bulk SMS sent', [
                    'total' => count($recipients),
                    'sent' => $sentCount,
                    'failed' => $failedCount,
                    'personalized' => $messageHasVariables,
                    'total_cost' => $totalCost,
                ]);

                // Update daily analytics after bulk send
                $this->analyticsService->updateDailyAnalytics($userId);

                return response()->json([
                    'message' => 'Envoi terminé',
                    'data' => [
                        'total' => count($recipients),
                        'sent' => $sentCount,
                        'failed' => $failedCount,
                        'blacklisted_skipped' => $blacklistedCount,
                        'sms_count' => ceil(strlen($message) / 160),
                        'total_cost' => $totalCost,
                        'personalized' => $messageHasVariables,
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
     * @OA\Post(
     *     path="/api/messages/analyze",
     *     tags={"Messages"},
     *     summary="Analyze phone numbers",
     *     description="Analyser des numéros de téléphone pour déterminer l'opérateur et la validité",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_numbers"},
     *             @OA\Property(property="phone_numbers", type="array", @OA\Items(type="string"), example={"77123456", "60123456", "invalid"})
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Analyse effectuée",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Analyse effectuée"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="airtel_count", type="integer", example=1),
     *                 @OA\Property(property="moov_count", type="integer", example=1),
     *                 @OA\Property(property="unknown_count", type="integer", example=1)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     * @OA\Post(
     *     path="/api/messages/number-info",
     *     tags={"Messages"},
     *     summary="Get phone number info",
     *     description="Obtenir les informations d'un numéro de téléphone (opérateur, pays, validité)",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"phone_number"},
     *             @OA\Property(property="phone_number", type="string", example="77123456")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Informations du numéro",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Informations du numéro"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="operator", type="string", example="airtel"),
     *                 @OA\Property(property="country", type="string", example="GA"),
     *                 @OA\Property(property="is_valid", type="boolean", example=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
