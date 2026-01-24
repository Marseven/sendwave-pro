<?php

namespace App\Services;

use App\Jobs\TriggerWebhookJob;
use App\Models\Webhook;
use App\Models\WebhookLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookService
{
    /**
     * Trigger webhooks for a specific event (synchronous)
     */
    public function trigger(string $event, int $userId, array $data): void
    {
        $webhooks = Webhook::byUser($userId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            $this->dispatch($webhook, $event, $data);
        }
    }

    /**
     * Trigger webhooks asynchronously via queue
     */
    public function triggerAsync(string $event, int $userId, array $data): void
    {
        $webhooks = Webhook::byUser($userId)
            ->active()
            ->forEvent($event)
            ->get();

        foreach ($webhooks as $webhook) {
            TriggerWebhookJob::dispatch($webhook->id, $event, $data);
        }
    }

    /**
     * Dispatch a single webhook
     */
    public function dispatch(Webhook $webhook, string $event, array $data): void
    {
        $payload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $data,
        ];

        $signature = $webhook->generateSignature($payload);

        $this->sendWithRetry($webhook, $event, $payload, $signature);
    }

    /**
     * Send webhook with retry logic
     */
    protected function sendWithRetry(Webhook $webhook, string $event, array $payload, string $signature, int $attempt = 1): void
    {
        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $payload);

            $success = $response->successful();

            // Log the delivery
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'error_message' => $success ? null : 'HTTP ' . $response->status(),
                'attempt' => $attempt,
                'success' => $success,
                'triggered_at' => now(),
            ]);

            if ($success) {
                $webhook->recordSuccess();
            } else {
                $this->handleFailure($webhook, $event, $payload, $signature, $attempt, 'HTTP ' . $response->status());
            }

        } catch (\Exception $e) {
            // Log the error
            WebhookLog::create([
                'webhook_id' => $webhook->id,
                'event' => $event,
                'payload' => $payload,
                'status_code' => null,
                'response_body' => null,
                'error_message' => $e->getMessage(),
                'attempt' => $attempt,
                'success' => false,
                'triggered_at' => now(),
            ]);

            $this->handleFailure($webhook, $event, $payload, $signature, $attempt, $e->getMessage());
        }
    }

    /**
     * Handle webhook delivery failure
     */
    protected function handleFailure(Webhook $webhook, string $event, array $payload, string $signature, int $attempt, string $error): void
    {
        $webhook->recordFailure();

        // Retry if under retry limit
        if ($attempt < $webhook->retry_limit) {
            // Exponential backoff: 1s, 2s, 4s, etc.
            sleep(pow(2, $attempt - 1));
            $this->sendWithRetry($webhook, $event, $payload, $signature, $attempt + 1);
        } else {
            Log::warning("Webhook delivery failed after {$attempt} attempts", [
                'webhook_id' => $webhook->id,
                'event' => $event,
                'error' => $error,
            ]);
        }
    }

    /**
     * Test webhook delivery
     */
    public function test(Webhook $webhook): array
    {
        $testPayload = [
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'data' => [
                'message' => 'This is a test webhook delivery',
                'webhook_id' => $webhook->id,
                'webhook_name' => $webhook->name,
            ],
        ];

        $signature = $webhook->generateSignature($testPayload);

        try {
            $response = Http::timeout($webhook->timeout)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'webhook.test',
                    'Content-Type' => 'application/json',
                ])
                ->post($webhook->url, $testPayload);

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'response_body' => $response->body(),
                'error' => $response->successful() ? null : 'HTTP ' . $response->status(),
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'status_code' => null,
                'response_body' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verify webhook signature
     */
    public static function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);
        return hash_equals($expectedSignature, $signature);
    }
}
