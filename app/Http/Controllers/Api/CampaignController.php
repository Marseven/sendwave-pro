<?php

namespace App\Http\Controllers\Api;

use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignSchedule;
use App\Models\CampaignVariant;
use App\Models\Message;
use App\Models\Contact;
use App\Services\SMS\SmsRouter;
use App\Services\BudgetService;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class CampaignController extends Controller
{
    public function __construct(
        protected SmsRouter $smsRouter,
        protected WebhookService $webhookService,
        protected BudgetService $budgetService
    ) {}

    /**
     * Trouver un contact par numéro de téléphone
     */
    protected function findContactByPhone(int $userId, string $phone): ?Contact
    {
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        return Contact::where('user_id', $userId)
            ->where(function ($query) use ($cleaned, $phone) {
                $query->where('phone', $phone)
                    ->orWhere('phone', $cleaned)
                    ->orWhere('phone', '+' . $cleaned)
                    ->orWhere('phone', 'LIKE', '%' . substr($cleaned, -8));
            })
            ->first();
    }
    /**
     * @OA\Get(
     *     path="/api/campaigns",
     *     tags={"Campaigns"},
     *     summary="List all campaigns",
     *     description="Retrieve all campaigns for the authenticated user",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of campaigns",
     *         @OA\JsonContent(type="array", @OA\Items(type="object",
     *             @OA\Property(property="id", type="integer", example=1),
     *             @OA\Property(property="name", type="string", example="Promo Noel"),
     *             @OA\Property(property="status", type="string", example="draft"),
     *             @OA\Property(property="messages_sent", type="integer", example=0),
     *             @OA\Property(property="delivery_rate", type="number", example=95.5)
     *         ))
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($campaigns);
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns",
     *     tags={"Campaigns"},
     *     summary="Create a new campaign",
     *     description="Store a newly created campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Promo Noel"),
     *             @OA\Property(property="status", type="string", example="draft"),
     *             @OA\Property(property="group_id", type="integer", example=1),
     *             @OA\Property(property="messages_sent", type="integer", example=0),
     *             @OA\Property(property="delivery_rate", type="number", example=0),
     *             @OA\Property(property="ctr", type="number", example=0),
     *             @OA\Property(property="sms_provider", type="string", example="airtel"),
     *             @OA\Property(property="message", type="string", example="Bonjour {prenom}, profitez de nos offres!"),
     *             @OA\Property(property="scheduled_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign created successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => ['nullable', Rule::in(CampaignStatus::values())],
            'group_id' => 'nullable|integer|exists:contact_groups,id',
            'messages_sent' => 'nullable|integer',
            'delivery_rate' => 'nullable|numeric|min:0|max:100',
            'ctr' => 'nullable|numeric|min:0|max:100',
            'sms_provider' => 'nullable|string',
            'message' => 'nullable|string|max:320',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            'status' => $validated['status'] ?? CampaignStatus::DRAFT->value,
            ...collect($validated)->except('status')->toArray()
        ]);

        return response()->json($campaign, 201);
    }

    /**
     * @OA\Get(
     *     path="/api/campaigns/{id}",
     *     tags={"Campaigns"},
     *     summary="Get a specific campaign",
     *     description="Retrieve a single campaign by ID",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign details",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function show(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($campaign);
    }

    /**
     * @OA\Put(
     *     path="/api/campaigns/{id}",
     *     tags={"Campaigns"},
     *     summary="Update a campaign",
     *     description="Update an existing campaign by ID",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Promo Noel Updated"),
     *             @OA\Property(property="status", type="string", example="scheduled"),
     *             @OA\Property(property="group_id", type="integer", example=1),
     *             @OA\Property(property="messages_sent", type="integer"),
     *             @OA\Property(property="delivery_rate", type="number"),
     *             @OA\Property(property="ctr", type="number"),
     *             @OA\Property(property="sms_provider", type="string"),
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(property="scheduled_at", type="string", format="date-time")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign updated successfully",
     *         @OA\JsonContent(type="object")
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function update(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => ['sometimes', Rule::in(CampaignStatus::values())],
            'group_id' => 'nullable|integer|exists:contact_groups,id',
            'messages_sent' => 'sometimes|integer',
            'delivery_rate' => 'sometimes|numeric|min:0|max:100',
            'ctr' => 'sometimes|numeric|min:0|max:100',
            'sms_provider' => 'sometimes|string',
            'message' => 'sometimes|string|max:320',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    /**
     * @OA\Delete(
     *     path="/api/campaigns/{id}",
     *     tags={"Campaigns"},
     *     summary="Delete a campaign",
     *     description="Remove a campaign by ID",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campagne supprimee avec succes")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function destroy(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->delete();

        return response()->json(['message' => 'Campagne supprimée avec succès']);
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/clone",
     *     tags={"Campaigns"},
     *     summary="Clone a campaign",
     *     description="Create a copy of an existing campaign with reset counters and draft status",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID to clone",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Campaign cloned successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campagne clonee avec succes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function clone(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        // Clone the campaign with a new name
        $clone = $campaign->replicate();
        $clone->name = $campaign->name . ' (copie)';
        $clone->status = CampaignStatus::DRAFT->value;
        $clone->messages_sent = 0;
        $clone->recipients_count = 0;
        $clone->cost = 0;
        $clone->sent_at = null;
        $clone->scheduled_at = null;
        $clone->created_at = now();
        $clone->updated_at = now();
        $clone->save();

        // Clone variants if they exist
        foreach ($campaign->variants as $variant) {
            $variantClone = $variant->replicate();
            $variantClone->campaign_id = $clone->id;
            $variantClone->messages_sent = 0;
            $variantClone->delivered = 0;
            $variantClone->failed = 0;
            $variantClone->save();
        }

        Log::info('Campaign cloned', [
            'original_id' => $campaign->id,
            'clone_id' => $clone->id,
            'user_id' => $request->user()->id,
        ]);

        return response()->json([
            'message' => 'Campagne clonée avec succès',
            'data' => $clone->load('variants')
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/send",
     *     tags={"Campaigns"},
     *     summary="Send a campaign",
     *     description="Send SMS campaign to specified recipients with automatic operator routing",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"recipients", "message"},
     *             @OA\Property(property="recipients", type="array", @OA\Items(type="string"), example={"+24177123456", "+24166123456"}),
     *             @OA\Property(property="message", type="string", example="Bonjour, profitez de nos offres!")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Campaign sent successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Campagne envoyee avec succes"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="campaign_id", type="integer"),
     *                 @OA\Property(property="total", type="integer"),
     *                 @OA\Property(property="sent", type="integer"),
     *                 @OA\Property(property="failed", type="integer"),
     *                 @OA\Property(property="total_cost", type="number"),
     *                 @OA\Property(property="by_operator", type="object",
     *                     @OA\Property(property="airtel", type="integer"),
     *                     @OA\Property(property="moov", type="integer"),
     *                     @OA\Property(property="unknown", type="integer")
     *                 ),
     *                 @OA\Property(property="message_ids", type="array", @OA\Items(type="integer"))
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found"),
     *     @OA\Response(response=422, description="Validation error"),
     *     @OA\Response(response=500, description="Send error")
     * )
     */
    public function send(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'required|string',
            'message' => 'required|string|max:320',
        ]);

        try {
            $recipients = $validated['recipients'];
            $message = $validated['message'];
            $user = $request->user();
            $subAccount = $request->attributes->get('sub_account') ?? $user->getDefaultSubAccount();

            // === Budget Check ===
            if ($subAccount) {
                $smsCountPerMessage = ceil(strlen($message) / 160);
                $estimatedCostPerSms = config('sms.cost_per_sms', 20);
                $estimatedTotalCost = count($recipients) * $smsCountPerMessage * $estimatedCostPerSms;

                $budgetCheck = $this->budgetService->checkBudget($subAccount, $estimatedTotalCost);
                if (!$budgetCheck['allowed']) {
                    return response()->json([
                        'message' => 'Budget dépassé',
                        'error' => $budgetCheck['message'] ?? 'Budget mensuel dépassé. Envoi bloqué.',
                        'error_code' => $budgetCheck['error_code'] ?? 'BUDGET_EXCEEDED',
                    ], 403);
                }

                if (!$subAccount->canSendSms()) {
                    return response()->json([
                        'message' => 'Envoi non autorisé',
                        'error' => 'Limite de crédits atteinte ou compte inactif.',
                        'error_code' => 'CREDITS_EXCEEDED',
                    ], 403);
                }

                if ((float) $subAccount->sms_credits < $estimatedTotalCost) {
                    return response()->json([
                        'message' => 'Crédits insuffisants',
                        'error' => 'Pas assez de crédits SMS pour cet envoi.',
                        'error_code' => 'CREDITS_INSUFFICIENT',
                    ], 403);
                }
            }

            // Analyser les numéros
            $analysis = $this->smsRouter->analyzeNumbers($recipients);

            Log::info('Campaign SMS Send', [
                'campaign_id' => $campaign->id,
                'recipients_count' => count($recipients),
                'analysis' => $analysis,
            ]);

            // Envoyer via le routeur
            $result = $this->smsRouter->sendBulkSms($recipients, $message);

            // Enregistrer chaque message dans l'historique
            $totalCost = 0;
            $messageIds = [];

            // Cache des contacts pour optimisation
            $userId = $request->user()->id;
            $contactCache = [];

            foreach ($result['details'] as $detail) {
                $smsCount = ceil(strlen($message) / 160);
                $costPerSms = config("sms.{$detail['provider']}.cost_per_sms", 20);
                $cost = $smsCount * $costPerSms;
                $totalCost += $cost;

                $recipientPhone = $detail['phone'] ?? '';

                // Trouver le contact associé (avec cache)
                if (!isset($contactCache[$recipientPhone]) && $recipientPhone) {
                    $contactCache[$recipientPhone] = $this->findContactByPhone($userId, $recipientPhone);
                }
                $contact = $contactCache[$recipientPhone] ?? null;

                $messageRecord = Message::create([
                    'user_id' => $userId,
                    'campaign_id' => $campaign->id,
                    'contact_id' => $contact?->id,
                    'recipient_name' => $contact?->name,
                    'recipient_phone' => $recipientPhone,
                    'content' => $message,
                    'type' => 'sms',
                    'status' => $detail['success'] ? MessageStatus::SENT->value : MessageStatus::FAILED->value,
                    'provider' => $detail['provider'] ?? 'unknown',
                    'cost' => $cost,
                    'error_message' => $detail['success'] ? null : ($detail['message'] ?? 'Erreur inconnue'),
                    'sent_at' => $detail['success'] ? now() : null,
                    'provider_response' => $detail,
                ]);

                $messageIds[] = $messageRecord->id;
            }

            // Débiter les crédits pour les messages envoyés
            $sentCost = collect($result['details'])
                ->filter(fn($d) => $d['success'])
                ->sum(fn($d) => ceil(strlen($message) / 160) * config("sms.{$d['provider']}.cost_per_sms", 20));

            if ($subAccount && $sentCost > 0) {
                $subAccount->useCredits($sentCost);
            }

            // Mettre à jour la campagne
            $finalStatus = $result['failed'] === 0 ? CampaignStatus::COMPLETED->value :
                          ($result['sent'] === 0 ? CampaignStatus::FAILED->value : CampaignStatus::COMPLETED->value);

            $campaign->update([
                'status' => $finalStatus,
                'messages_sent' => $result['sent'],
                'recipients_count' => count($recipients),
                'sms_count' => ceil(strlen($message) / 160),
                'cost' => $totalCost,
                'sent_at' => now(),
            ]);

            // Trigger webhook for campaign completion
            if ($result['failed'] === 0) {
                $this->webhookService->trigger('campaign.completed', $request->user()->id, [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_sent' => $result['sent'],
                    'total_cost' => $totalCost,
                ]);
            } elseif ($result['sent'] === 0) {
                $this->webhookService->trigger('campaign.failed', $request->user()->id, [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_failed' => $result['failed'],
                ]);
            } else {
                $this->webhookService->trigger('campaign.started', $request->user()->id, [
                    'campaign_id' => $campaign->id,
                    'campaign_name' => $campaign->name,
                    'total_recipients' => count($recipients),
                ]);
            }

            return response()->json([
                'message' => 'Campagne envoyée avec succès',
                'data' => [
                    'campaign_id' => $campaign->id,
                    'total' => $result['total'],
                    'sent' => $result['sent'],
                    'failed' => $result['failed'],
                    'total_cost' => $totalCost,
                    'by_operator' => [
                        'airtel' => $analysis['airtel_count'],
                        'moov' => $analysis['moov_count'],
                        'unknown' => $analysis['unknown_count'],
                    ],
                    'message_ids' => $messageIds,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Campaign Send Error', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'envoi de la campagne',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/schedule",
     *     tags={"Campaigns"},
     *     summary="Create or update campaign schedule",
     *     description="Set up recurring or one-time schedule for a campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"frequency", "time"},
     *             @OA\Property(property="frequency", type="string", enum={"once", "daily", "weekly", "monthly"}, example="weekly"),
     *             @OA\Property(property="day_of_week", type="integer", minimum=1, maximum=7, description="Required for weekly frequency"),
     *             @OA\Property(property="day_of_month", type="integer", minimum=1, maximum=31, description="Required for monthly frequency"),
     *             @OA\Property(property="time", type="string", example="08:00"),
     *             @OA\Property(property="start_date", type="string", format="date"),
     *             @OA\Property(property="end_date", type="string", format="date"),
     *             @OA\Property(property="is_active", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Schedule created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Planification creee avec succes"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function storeSchedule(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'frequency' => 'required|in:once,daily,weekly,monthly',
            'day_of_week' => 'nullable|integer|min:1|max:7',
            'day_of_month' => 'nullable|integer|min:1|max:31',
            'time' => 'required|date_format:H:i',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'is_active' => 'nullable|boolean',
        ]);

        // Validate frequency-specific fields
        if ($validated['frequency'] === 'weekly' && empty($validated['day_of_week'])) {
            return response()->json(['message' => 'day_of_week requis pour la fréquence hebdomadaire'], 422);
        }

        if ($validated['frequency'] === 'monthly' && empty($validated['day_of_month'])) {
            return response()->json(['message' => 'day_of_month requis pour la fréquence mensuelle'], 422);
        }

        $schedule = $campaign->schedule()->updateOrCreate(
            ['campaign_id' => $campaign->id],
            array_merge($validated, [
                'next_run_at' => null // Will be calculated by the model
            ])
        );

        // Calculate next run
        $schedule->next_run_at = $schedule->calculateNextRun();
        $schedule->save();

        return response()->json([
            'message' => 'Planification créée avec succès',
            'data' => $schedule
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/campaigns/{id}/schedule",
     *     tags={"Campaigns"},
     *     summary="Get campaign schedule",
     *     description="Retrieve the schedule configuration for a campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule details",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign or schedule not found")
     * )
     */
    public function getSchedule(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $schedule = $campaign->schedule;

        if (!$schedule) {
            return response()->json(['message' => 'Aucune planification trouvée'], 404);
        }

        return response()->json(['data' => $schedule]);
    }

    /**
     * @OA\Delete(
     *     path="/api/campaigns/{id}/schedule",
     *     tags={"Campaigns"},
     *     summary="Delete campaign schedule",
     *     description="Remove the schedule configuration for a campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Schedule deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Planification supprimee avec succes")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function deleteSchedule(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->schedule()->delete();

        return response()->json(['message' => 'Planification supprimée avec succès']);
    }

    /**
     * @OA\Post(
     *     path="/api/campaigns/{id}/variants",
     *     tags={"Campaigns"},
     *     summary="Create campaign variants for A/B testing",
     *     description="Create or replace campaign variants. Percentages must sum to 100.",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"variants"},
     *             @OA\Property(property="variants", type="array", minItems=2, maxItems=5,
     *                 @OA\Items(type="object",
     *                     required={"variant_name", "message", "percentage"},
     *                     @OA\Property(property="variant_name", type="string", example="Variant A"),
     *                     @OA\Property(property="message", type="string", example="Bonjour!"),
     *                     @OA\Property(property="percentage", type="integer", example=50)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Variants created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Variantes A/B creees avec succes"),
     *             @OA\Property(property="data", type="array", @OA\Items(type="object"))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found"),
     *     @OA\Response(response=422, description="Validation error or percentages do not sum to 100")
     * )
     */
    public function storeVariants(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'variants' => 'required|array|min:2|max:5',
            'variants.*.variant_name' => 'required|string|max:50',
            'variants.*.message' => 'required|string|max:320',
            'variants.*.percentage' => 'required|integer|min:1|max:100',
        ]);

        // Validate that percentages sum to 100
        $totalPercentage = array_sum(array_column($validated['variants'], 'percentage'));
        if ($totalPercentage !== 100) {
            return response()->json([
                'message' => 'La somme des pourcentages doit être égale à 100',
                'total' => $totalPercentage
            ], 422);
        }

        // Delete existing variants
        $campaign->variants()->delete();

        // Create new variants
        $variants = [];
        foreach ($validated['variants'] as $variantData) {
            $variant = $campaign->variants()->create($variantData);
            $variants[] = $variant;
        }

        return response()->json([
            'message' => 'Variantes A/B créées avec succès',
            'data' => $variants
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/campaigns/{id}/variants",
     *     tags={"Campaigns"},
     *     summary="Get campaign variants",
     *     description="Retrieve all A/B testing variants for a campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of campaign variants",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="variant_name", type="string"),
     *                 @OA\Property(property="message", type="string"),
     *                 @OA\Property(property="percentage", type="integer")
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function getVariants(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $variants = $campaign->variants;

        return response()->json(['data' => $variants]);
    }

    /**
     * @OA\Delete(
     *     path="/api/campaigns/{id}/variants",
     *     tags={"Campaigns"},
     *     summary="Delete campaign variants",
     *     description="Remove all A/B testing variants for a campaign",
     *     security={{"sanctum":{}},{"apiKey":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Campaign ID",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Variants deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Variantes supprimees avec succes")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Campaign not found")
     * )
     */
    public function deleteVariants(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->variants()->delete();

        return response()->json(['message' => 'Variantes supprimées avec succès']);
    }
}
