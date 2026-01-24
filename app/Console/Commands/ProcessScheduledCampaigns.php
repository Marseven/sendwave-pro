<?php

namespace App\Console\Commands;

use App\Models\Campaign;
use App\Models\CampaignSchedule;
use App\Models\Contact;
use App\Models\Message;
use App\Enums\CampaignStatus;
use App\Enums\MessageStatus;
use App\Services\SMS\SmsRouter;
use App\Services\WebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessScheduledCampaigns extends Command
{
    protected $signature = 'campaigns:process-scheduled';
    protected $description = 'Process scheduled campaigns that are ready to run';

    public function __construct(
        protected SmsRouter $smsRouter,
        protected WebhookService $webhookService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Processing scheduled campaigns...');

        $schedules = CampaignSchedule::readyToRun()
            ->with('campaign')
            ->get();

        if ($schedules->isEmpty()) {
            $this->info('No campaigns ready to run.');
            return Command::SUCCESS;
        }

        $this->info("Found {$schedules->count()} campaign(s) to process.");

        foreach ($schedules as $schedule) {
            $this->processCampaign($schedule);
        }

        $this->info('Done processing scheduled campaigns.');
        return Command::SUCCESS;
    }

    protected function processCampaign(CampaignSchedule $schedule): void
    {
        $campaign = $schedule->campaign;

        if (!$campaign) {
            $this->warn("Schedule {$schedule->id} has no associated campaign. Skipping.");
            $schedule->update(['is_active' => false]);
            return;
        }

        $this->info("Processing campaign: {$campaign->name} (ID: {$campaign->id})");

        try {
            // Get recipients from campaign's contact group or stored recipients
            $recipients = $this->getCampaignRecipients($campaign);

            if (empty($recipients)) {
                $this->warn("Campaign {$campaign->id} has no recipients. Skipping.");
                Log::warning('Scheduled campaign has no recipients', ['campaign_id' => $campaign->id]);
                $schedule->markAsExecuted();
                return;
            }

            $message = $campaign->message ?? $campaign->message_content ?? '';

            if (empty($message)) {
                $this->warn("Campaign {$campaign->id} has no message content. Skipping.");
                Log::warning('Scheduled campaign has no message', ['campaign_id' => $campaign->id]);
                $schedule->markAsExecuted();
                return;
            }

            // Update campaign status to sending
            $campaign->update(['status' => CampaignStatus::SENDING->value]);

            // Send SMS via router
            $result = $this->smsRouter->sendBulkSms($recipients, $message);

            // Save message records with contact association
            $totalCost = 0;
            $contactCache = [];

            foreach ($result['details'] as $detail) {
                $smsCount = ceil(strlen($message) / 160);
                $costPerSms = config("sms.{$detail['provider']}.cost_per_sms", 20);
                $cost = $smsCount * $costPerSms;
                $totalCost += $cost;

                $recipientPhone = $detail['phone'] ?? '';

                // Trouver le contact associé (avec cache)
                if (!isset($contactCache[$recipientPhone]) && $recipientPhone) {
                    $contactCache[$recipientPhone] = $this->findContactByPhone($campaign->user_id, $recipientPhone);
                }
                $contact = $contactCache[$recipientPhone] ?? null;

                Message::create([
                    'user_id' => $campaign->user_id,
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
            }

            // Update campaign stats
            $finalStatus = $result['failed'] === 0 ? CampaignStatus::COMPLETED->value :
                          ($result['sent'] === 0 ? CampaignStatus::FAILED->value : CampaignStatus::COMPLETED->value);

            $campaign->update([
                'status' => $finalStatus,
                'messages_sent' => ($campaign->messages_sent ?? 0) + $result['sent'],
                'recipients_count' => count($recipients),
                'sms_count' => ceil(strlen($message) / 160),
                'cost' => ($campaign->cost ?? 0) + $totalCost,
                'sent_at' => now(),
            ]);

            // Trigger webhooks
            $this->webhookService->trigger('campaign.completed', $campaign->user_id, [
                'campaign_id' => $campaign->id,
                'campaign_name' => $campaign->name,
                'total_sent' => $result['sent'],
                'total_failed' => $result['failed'],
                'total_cost' => $totalCost,
                'scheduled' => true,
            ]);

            // Mark schedule as executed (calculates next run for recurring)
            $schedule->markAsExecuted();

            $this->info("Campaign {$campaign->id} completed: {$result['sent']} sent, {$result['failed']} failed");
            Log::info('Scheduled campaign processed', [
                'campaign_id' => $campaign->id,
                'sent' => $result['sent'],
                'failed' => $result['failed'],
                'cost' => $totalCost,
            ]);

        } catch (\Exception $e) {
            $this->error("Error processing campaign {$campaign->id}: {$e->getMessage()}");
            Log::error('Scheduled campaign processing failed', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $campaign->update(['status' => CampaignStatus::FAILED->value]);
        }
    }

    protected function getCampaignRecipients(Campaign $campaign): array
    {
        $recipients = [];

        // Try to get recipients from contact group
        if ($campaign->group_id) {
            $group = \App\Models\ContactGroup::find($campaign->group_id);
            if ($group) {
                $recipients = $group->contacts()->pluck('phone')->toArray();
            }
        }

        // Fallback: get recent messages recipients if this is a recurring campaign
        if (empty($recipients) && $campaign->messages()->exists()) {
            $recipients = $campaign->messages()
                ->distinct()
                ->pluck('recipient_phone')
                ->toArray();
        }

        return array_filter($recipients);
    }

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
}
