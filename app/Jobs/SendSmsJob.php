<?php

namespace App\Jobs;

use App\Services\SMS\SmsRouter;
use App\Models\Message;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendSmsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Nombre de tentatives maximum
     */
    public $tries = 3;

    /**
     * Timeout en secondes
     */
    public $timeout = 60;

    /**
     * Délai entre les tentatives (en secondes)
     */
    public $backoff = [10, 30, 60];

    protected int $userId;
    protected string $phoneNumber;
    protected string $message;
    protected ?int $campaignId;
    protected ?int $contactId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $userId,
        string $phoneNumber,
        string $message,
        ?int $campaignId = null,
        ?int $contactId = null
    ) {
        $this->userId = $userId;
        $this->phoneNumber = $phoneNumber;
        $this->message = $message;
        $this->campaignId = $campaignId;
        $this->contactId = $contactId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendSmsJob - Start', [
            'user_id' => $this->userId,
            'phone' => $this->phoneNumber,
            'attempt' => $this->attempts()
        ]);

        try {
            $smsRouter = new SmsRouter();
            $result = $smsRouter->sendSms($this->phoneNumber, $this->message);

            // Calculer le coût
            $smsCount = ceil(strlen($this->message) / 160);
            $operator = $result['provider'] ?? 'airtel';
            $costPerSms = config("sms.{$operator}.cost_per_sms", 20);
            $cost = $smsCount * $costPerSms;

            // Enregistrer dans l'historique
            Message::create([
                'user_id' => $this->userId,
                'campaign_id' => $this->campaignId,
                'contact_id' => $this->contactId,
                'recipient_phone' => $result['phone'] ?? $this->phoneNumber,
                'content' => $this->message,
                'type' => 'sms',
                'status' => $result['success'] ? 'sent' : 'failed',
                'provider' => $result['provider'] ?? 'unknown',
                'cost' => $cost,
                'error_message' => $result['success'] ? null : ($result['message'] ?? 'Erreur inconnue'),
                'sent_at' => $result['success'] ? now() : null,
                'provider_response' => json_encode($result),
            ]);

            if (!$result['success']) {
                Log::warning('SendSmsJob - Failed', [
                    'phone' => $this->phoneNumber,
                    'error' => $result['message'] ?? 'Unknown error'
                ]);

                // Si échec, on peut relancer
                if ($this->attempts() < $this->tries) {
                    $this->release(30); // Réessayer dans 30 secondes
                }
            } else {
                Log::info('SendSmsJob - Success', [
                    'phone' => $this->phoneNumber,
                    'provider' => $result['provider']
                ]);
            }

        } catch (\Exception $e) {
            Log::error('SendSmsJob - Exception', [
                'phone' => $this->phoneNumber,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Enregistrer l'échec
            Message::create([
                'user_id' => $this->userId,
                'campaign_id' => $this->campaignId,
                'contact_id' => $this->contactId,
                'recipient_phone' => $this->phoneNumber,
                'content' => $this->message,
                'type' => 'sms',
                'status' => 'failed',
                'provider' => 'unknown',
                'cost' => 0,
                'error_message' => $e->getMessage(),
                'sent_at' => null,
                'provider_response' => null,
            ]);

            // Relancer si pas dépassé le nombre de tentatives
            if ($this->attempts() < $this->tries) {
                $this->release(60); // Réessayer dans 60 secondes
            } else {
                $this->fail($e);
            }
        }
    }

    /**
     * Gérer l'échec du job après toutes les tentatives
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendSmsJob - Failed permanently', [
            'user_id' => $this->userId,
            'phone' => $this->phoneNumber,
            'error' => $exception->getMessage()
        ]);

        // On peut envoyer une notification à l'admin ici
    }
}
