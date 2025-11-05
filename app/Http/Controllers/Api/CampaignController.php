<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Message;
use App\Models\Contact;
use App\Services\SMS\SmsRouter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CampaignController extends Controller
{
    protected SmsRouter $smsRouter;

    public function __construct()
    {
        $this->smsRouter = new SmsRouter();
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
}
