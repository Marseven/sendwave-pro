<?php

namespace App\Jobs;

use App\Jobs\SendSmsJob;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendBulkSmsJob implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    public $tries = 1; // Pas de retry pour le bulk, on dispatch les jobs individuels
    public $timeout = 300; // 5 minutes max

    protected int $userId;
    protected array $phoneNumbers;
    protected string $message;
    protected ?int $campaignId;

    /**
     * Create a new job instance.
     */
    public function __construct(
        int $userId,
        array $phoneNumbers,
        string $message,
        ?int $campaignId = null
    ) {
        $this->userId = $userId;
        $this->phoneNumbers = $phoneNumbers;
        $this->message = $message;
        $this->campaignId = $campaignId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        Log::info('SendBulkSmsJob - Start', [
            'user_id' => $this->userId,
            'count' => count($this->phoneNumbers),
            'campaign_id' => $this->campaignId
        ]);

        try {
            // Dispatcher un job individuel pour chaque numéro
            // Cela permet de gérer les retry individuellement
            foreach ($this->phoneNumbers as $phoneNumber) {
                SendSmsJob::dispatch(
                    $this->userId,
                    $phoneNumber,
                    $this->message,
                    $this->campaignId,
                    null // contact_id à définir si disponible
                )->onQueue('sms'); // Queue dédiée aux SMS
            }

            Log::info('SendBulkSmsJob - Dispatched', [
                'count' => count($this->phoneNumbers)
            ]);

        } catch (\Exception $e) {
            Log::error('SendBulkSmsJob - Exception', [
                'user_id' => $this->userId,
                'error' => $e->getMessage()
            ]);

            $this->fail($e);
        }
    }

    /**
     * Gérer l'échec du job
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendBulkSmsJob - Failed', [
            'user_id' => $this->userId,
            'count' => count($this->phoneNumbers),
            'error' => $exception->getMessage()
        ]);
    }
}
