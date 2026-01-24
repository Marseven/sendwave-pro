<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UpdateDailyAnalytics implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    protected ?Carbon $date;

    public function __construct(?Carbon $date = null)
    {
        $this->date = $date;
    }

    public function handle(AnalyticsService $analyticsService): void
    {
        $date = $this->date ?? now()->subDay();

        Log::info('Starting daily analytics update', ['date' => $date->format('Y-m-d')]);

        $users = User::all();
        $processed = 0;

        foreach ($users as $user) {
            try {
                $analyticsService->updateDailyAnalytics($user->id, $date);
                $processed++;
            } catch (\Exception $e) {
                Log::error('Failed to update analytics for user', [
                    'user_id' => $user->id,
                    'date' => $date->format('Y-m-d'),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Daily analytics update completed', [
            'date' => $date->format('Y-m-d'),
            'users_processed' => $processed,
            'total_users' => $users->count(),
        ]);
    }
}
