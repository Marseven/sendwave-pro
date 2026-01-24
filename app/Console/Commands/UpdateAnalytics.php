<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\AnalyticsService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class UpdateAnalytics extends Command
{
    protected $signature = 'analytics:update {--user= : User ID (optional, updates all if not specified)} {--days=30 : Number of days to update}';

    protected $description = 'Update daily analytics for users based on message history';

    public function handle(AnalyticsService $analyticsService)
    {
        $userId = $this->option('user');
        $days = (int) $this->option('days');

        $users = $userId
            ? User::where('id', $userId)->get()
            : User::all();

        $this->info("Updating analytics for {$users->count()} user(s) over {$days} days...");

        $bar = $this->output->createProgressBar($users->count() * $days);
        $bar->start();

        foreach ($users as $user) {
            for ($i = 0; $i < $days; $i++) {
                $date = Carbon::now()->subDays($i);
                $analyticsService->updateDailyAnalytics($user->id, $date);
                $bar->advance();
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('Analytics updated successfully!');

        return Command::SUCCESS;
    }
}
