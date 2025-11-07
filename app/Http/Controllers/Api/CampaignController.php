<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignSchedule;
use App\Models\CampaignVariant;
use App\Models\Message;
use App\Models\Contact;
use App\Services\SMS\SmsRouter;
use App\Services\WebhookService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    protected SmsRouter $smsRouter;
    protected WebhookService $webhookService;

    public function __construct(WebhookService $webhookService)
    {
        $this->smsRouter = new SmsRouter();
        $this->webhookService = $webhookService;
    }
    public function index(Request $request)
    {
        $campaigns = Campaign::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($campaigns);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'nullable|in:Actif,Terminé,Planifié',
            'messages_sent' => 'nullable|integer',
            'delivery_rate' => 'nullable|numeric|min:0|max:100',
            'ctr' => 'nullable|numeric|min:0|max:100',
            'sms_provider' => 'nullable|in:msg91,smsala,wapi',
            'message_content' => 'nullable|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign = Campaign::create([
            'user_id' => $request->user()->id,
            ...$validated
        ]);

        return response()->json($campaign, 201);
    }

    public function show(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($campaign);
    }

    public function update(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:Actif,Terminé,Planifié',
            'messages_sent' => 'sometimes|integer',
            'delivery_rate' => 'sometimes|numeric|min:0|max:100',
            'ctr' => 'sometimes|numeric|min:0|max:100',
            'sms_provider' => 'sometimes|in:msg91,smsala,wapi',
            'message_content' => 'sometimes|string',
            'scheduled_at' => 'nullable|date',
        ]);

        $campaign->update($validated);

        return response()->json($campaign);
    }

    public function destroy(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->delete();

        return response()->json(['message' => 'Campagne supprimée avec succès']);
    }

    /**
     * Envoyer une campagne SMS
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

            foreach ($result['details'] as $detail) {
                $smsCount = ceil(strlen($message) / 160);
                $costPerSms = config("sms.{$detail['provider']}.cost_per_sms", 20);
                $cost = $smsCount * $costPerSms;
                $totalCost += $cost;

                $messageRecord = Message::create([
                    'user_id' => $request->user()->id,
                    'campaign_id' => $campaign->id,
                    'recipient_phone' => $detail['phone'] ?? '',
                    'content' => $message,
                    'type' => 'sms',
                    'status' => $detail['success'] ? 'sent' : 'failed',
                    'provider' => $detail['provider'] ?? 'unknown',
                    'cost' => $cost,
                    'error_message' => $detail['success'] ? null : ($detail['message'] ?? 'Erreur inconnue'),
                    'sent_at' => $detail['success'] ? now() : null,
                    'provider_response' => $detail,
                ]);

                $messageIds[] = $messageRecord->id;
            }

            // Mettre à jour la campagne
            $campaign->update([
                'status' => 'Actif',
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
     * Create or update campaign schedule
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
     * Get campaign schedule
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
     * Delete campaign schedule
     */
    public function deleteSchedule(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->schedule()->delete();

        return response()->json(['message' => 'Planification supprimée avec succès']);
    }

    /**
     * Create or update campaign variants (A/B testing)
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
     * Get campaign variants
     */
    public function getVariants(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $variants = $campaign->variants;

        return response()->json(['data' => $variants]);
    }

    /**
     * Delete campaign variants
     */
    public function deleteVariants(Request $request, string $id)
    {
        $campaign = Campaign::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $campaign->variants()->delete();

        return response()->json(['message' => 'Variantes supprimées avec succès']);
    }
}
