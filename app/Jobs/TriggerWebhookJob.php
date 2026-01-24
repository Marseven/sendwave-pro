<?php

namespace App\Jobs;

use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TriggerWebhookJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 5;
    public array $backoff = [30, 60, 120, 300, 600]; // 30s, 1m, 2m, 5m, 10m

    protected int $webhookId;
    protected string $event;
    protected array $data;

    public function __construct(int $webhookId, string $event, array $data)
    {
        $this->webhookId = $webhookId;
        $this->event = $event;
        $this->data = $data;
    }

    public function handle(): void
    {
        $webhook = Webhook::find($this->webhookId);

        if (!$webhook) {
            Log::warning('TriggerWebhookJob: Webhook not found', [
                'webhook_id' => $this->webhookId,
            ]);
            return;
        }

        if (!$webhook->is_active) {
            Log::info('TriggerWebhookJob: Webhook is inactive, skipping', [
                'webhook_id' => $this->webhookId,
            ]);
            return;
        }

        $payload = [
            'event' => $this->event,
            'timestamp' => now()->toIso8601String(),
            'data' => $this->data,
        ];

        $signature = $webhook->generateSignature($payload);
        $attempt = $this->attempts();

        Log::info('TriggerWebhookJob: Sending webhook', [
            'webhook_id' => $webhook->id,
            'event' => $this->event,
            'attempt' => $attempt,
        ]);

        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $this->event,
                    'X-Webhook-Attempt' => (string) $attempt,
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'SendWave-Webhook/1.0',
                ])
                ->post($webhook->url, $payload);

            $success = $response->successful();

            // Log the delivery
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $this->event,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 1000), // Limit response size
                'error_message' => $success ? null : 'HTTP ' . $response->status(),
                'attempt' => $attempt,
                'success' => $success,
                'triggered_at' => now(),
            ]);

            if ($success) {
                $webhook->recordSuccess();
                Log::info('TriggerWebhookJob: Webhook delivered successfully', [
                    'webhook_id' => $webhook->id,
                    'event' => $this->event,
                    'status_code' => $response->status(),
                ]);
            } else {
                $webhook->recordFailure();
                Log::warning('TriggerWebhookJob: Webhook delivery failed', [
                    'webhook_id' => $webhook->id,
                    'event' => $this->event,
                    'status_code' => $response->status(),
                    'attempt' => $attempt,
                ]);

                // Throw exception to trigger retry
                throw new \Exception('Webhook delivery failed with HTTP ' . $response->status());
            }

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->logFailure($webhook, $attempt, 'Connection error: ' . $e->getMessage());
            throw $e;

        } catch (\Exception $e) {
            $this->logFailure($webhook, $attempt, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Log a webhook failure
     */
    protected function logFailure(Webhook $webhook, int $attempt, string $error): void
    {
        $webhook->recordFailure();

        WebhookLog::create([
            'webhook_id' => $webhook->id,
            'event' => $this->event,
            'payload' => [
                'event' => $this->event,
                'timestamp' => now()->toIso8601String(),
                'data' => $this->data,
            ],
            'status_code' => null,
            'response_body' => null,
            'error_message' => $error,
            'attempt' => $attempt,
            'success' => false,
            'triggered_at' => now(),
        ]);
    }

    /**
     * Handle a job failure after all retries
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('TriggerWebhookJob: Job failed after all retries', [
            'webhook_id' => $this->webhookId,
            'event' => $this->event,
            'error' => $exception->getMessage(),
        ]);

        $webhook = Webhook::find($this->webhookId);
        if ($webhook) {
            // Check if webhook should be disabled after too many failures
            if ($webhook->consecutive_failures >= 10) {
                $webhook->update(['is_active' => false]);
                Log::warning('TriggerWebhookJob: Webhook disabled due to too many failures', [
                    'webhook_id' => $webhook->id,
                    'consecutive_failures' => $webhook->consecutive_failures,
                ]);
            }
        }
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'webhook',
            'webhook:' . $this->webhookId,
            'event:' . $this->event,
        ];
    }

    /**
     * Determine the time at which the job should timeout
     */
    public function retryUntil(): \DateTime
    {
        return now()->addHours(1);
    }
}
