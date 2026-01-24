<?php

namespace App\Jobs;

use App\Enums\MessageStatus;
use App\Models\Contact;
use App\Models\Message;
use App\Services\SMS\SmsRouter;
use App\Services\WebhookService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public array $backoff = [30, 60, 120];

    protected int $userId;
    protected string $recipient;
    protected string $message;
    protected ?int $campaignId;
    protected ?int $contactId;
    protected ?string $recipientName;

    public function __construct(
        int $userId,
        string $recipient,
        string $message,
        ?int $campaignId = null,
        ?int $contactId = null,
        ?string $recipientName = null
    ) {
        $this->userId = $userId;
        $this->recipient = $recipient;
        $this->message = $message;
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
        $this->recipientName = $recipientName;
    }

    public function handle(SmsRouter $smsRouter, WebhookService $webhookService): void
    {
        Log::info('SendSmsJob: Processing SMS', [
            'user_id' => $this->userId,
            'recipient' => $this->recipient,
            'message_length' => strlen($this->message),
            'campaign_id' => $this->campaignId,
        ]);

        try {
            // Send the SMS
            $result = $smsRouter->sendSms($this->recipient, $this->message);

            // Calculate cost
            $smsCount = ceil(strlen($this->message) / 160);
            $costPerSms = config("sms.{$result['provider']}.cost_per_sms", config('sms.cost_per_sms', 20));
            $cost = $smsCount * $costPerSms;

            // Find contact if not provided
            $contactId = $this->contactId;
            $recipientName = $this->recipientName;

            if (!$contactId) {
                $contact = $this->findContactByPhone($this->userId, $this->recipient);
                if ($contact) {
                    $contactId = $contact->id;
                    $recipientName = $contact->name;
                }
            }

            // Determine status
            $status = $result['success'] ? MessageStatus::SENT->value : MessageStatus::FAILED->value;

            // Save message to history
            $messageRecord = Message::create([
                'user_id' => $this->userId,
                'campaign_id' => $this->campaignId,
                'contact_id' => $contactId,
                'recipient_name' => $recipientName,
                'recipient_phone' => $result['phone'] ?? $this->recipient,
                'content' => $this->message,
                'type' => 'sms',
                'status' => $status,
                'provider' => $result['provider'] ?? 'unknown',
                'cost' => $cost,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Erreur inconnue'),
                'sent_at' => $result['success'] ? now() : null,
                'provider_response' => $result,
            ]);

            // Trigger webhooks
            if ($result['success']) {
                $webhookService->trigger('message.sent', $this->userId, [
                    'message_id' => $messageRecord->id,
                    'recipient' => $result['phone'] ?? $this->recipient,
                    'content' => $this->message,
                    'provider' => $result['provider'],
                    'cost' => $cost,
                    'campaign_id' => $this->campaignId,
                ]);

                Log::info('SendSmsJob: SMS sent successfully', [
                    'message_id' => $messageRecord->id,
                    'provider' => $result['provider'],
                ]);
            } else {
                $webhookService->trigger('message.failed', $this->userId, [
                    'message_id' => $messageRecord->id,
                    'recipient' => $this->recipient,
                    'content' => $this->message,
                    'error' => $result['message'] ?? 'Erreur inconnue',
                    'provider' => $result['provider'] ?? 'unknown',
                    'campaign_id' => $this->campaignId,
                ]);

                Log::warning('SendSmsJob: SMS failed', [
                    'message_id' => $messageRecord->id,
                    'error' => $result['message'] ?? 'Erreur inconnue',
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SendSmsJob: Exception during SMS sending', [
                'user_id' => $this->userId,
                'recipient' => $this->recipient,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Re-throw to trigger retry
            throw $e;
        }
    }

    /**
     * Handle a job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob: Job failed after all retries', [
            'user_id' => $this->userId,
            'recipient' => $this->recipient,
            'error' => $exception->getMessage(),
        ]);

        // Save failed message to history
        Message::create([
            'user_id' => $this->userId,
            'campaign_id' => $this->campaignId,
            'contact_id' => $this->contactId,
            'recipient_name' => $this->recipientName,
            'recipient_phone' => $this->recipient,
            'content' => $this->message,
            'type' => 'sms',
            'status' => MessageStatus::FAILED->value,
            'provider' => 'unknown',
            'cost' => 0,
            'error_message' => 'Job failed: ' . $exception->getMessage(),
            'sent_at' => null,
        ]);
    }

    /**
     * Find contact by phone number
     */
    protected function findContactByPhone(int $userId, string $phone): ?Contact
    {
        $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);

        if (str_starts_with($normalizedPhone, '00')) {
            $normalizedPhone = substr($normalizedPhone, 2);
        }

        return Contact::where('user_id', $userId)
            ->where(function ($query) use ($normalizedPhone, $phone) {
                $query->where('phone', $phone)
                    ->orWhere('phone', $normalizedPhone)
                    ->orWhere('phone', '+' . $normalizedPhone)
                    ->orWhere('phone', 'LIKE', '%' . substr($normalizedPhone, -8));
            })
            ->first();
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'sms',
            'user:' . $this->userId,
            $this->campaignId ? 'campaign:' . $this->campaignId : 'direct',
        ];
    }
}
