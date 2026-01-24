<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendScheduledReportJob implements ShouldQueue
{
    use Queueable, Dispatchable, InteractsWithQueue, SerializesModels;

    public int $tries = 3;
    public array $backoff = [60, 300, 900];

    protected string $frequency; // 'daily', 'weekly', 'monthly'

    public function __construct(string $frequency = 'weekly')
    {
        $this->frequency = $frequency;
    }

    public function handle(AnalyticsService $analyticsService): void
    {
        Log::info('SendScheduledReportJob: Starting', ['frequency' => $this->frequency]);

        // Get users who want this frequency of reports
        $users = User::where('weekly_reports', true)
            ->whereNotNull('email')
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($users as $user) {
            try {
                $this->sendReportToUser($user, $analyticsService);
                $processed++;
            } catch (\Exception $e) {
                $failed++;
                Log::error('SendScheduledReportJob: Failed to send report', [
                    'user_id' => $user->id,
                    'email' => $user->email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('SendScheduledReportJob: Completed', [
            'frequency' => $this->frequency,
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }

    protected function sendReportToUser(User $user, AnalyticsService $analyticsService): void
    {
        // Get period based on frequency
        $period = match ($this->frequency) {
            'daily' => 'yesterday',
            'weekly' => 'last_7_days',
            'monthly' => 'last_month',
            default => 'last_7_days',
        };

        $dates = $analyticsService->getPeriodDates($period);
        $report = $analyticsService->getComprehensiveReport($user->id, $dates);

        // Build email content
        $subject = $this->getEmailSubject();
        $content = $this->buildEmailContent($user, $report);

        // Send email
        Mail::raw($content, function ($message) use ($user, $subject) {
            $message->to($user->email, $user->name)
                ->subject($subject);
        });

        Log::info('SendScheduledReportJob: Report sent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'frequency' => $this->frequency,
        ]);
    }

    protected function getEmailSubject(): string
    {
        $periodLabel = match ($this->frequency) {
            'daily' => 'quotidien',
            'weekly' => 'hebdomadaire',
            'monthly' => 'mensuel',
            default => 'hebdomadaire',
        };

        return "SendWave Pro - Rapport {$periodLabel} du " . now()->format('d/m/Y');
    }

    protected function buildEmailContent(User $user, array $report): string
    {
        $summary = $report['summary'];
        $periodLabel = match ($this->frequency) {
            'daily' => 'hier',
            'weekly' => 'les 7 derniers jours',
            'monthly' => 'le mois dernier',
            default => 'les 7 derniers jours',
        };

        $content = "Bonjour {$user->name},\n\n";
        $content .= "Voici votre rapport d'activité pour {$periodLabel}:\n\n";
        $content .= "═══════════════════════════════════════\n";
        $content .= "RÉSUMÉ\n";
        $content .= "═══════════════════════════════════════\n\n";
        $content .= "📱 SMS envoyés: {$summary['sms_sent']}\n";
        $content .= "✅ SMS délivrés: {$summary['sms_delivered']}\n";
        $content .= "❌ SMS échoués: {$summary['sms_failed']}\n";
        $content .= "📊 Taux de succès: {$summary['success_rate']}%\n";
        $content .= "💰 Coût total: {$summary['total_cost']} FCFA\n";
        $content .= "📣 Campagnes exécutées: {$summary['campaigns_executed']}\n";
        $content .= "👥 Contacts ajoutés: {$summary['contacts_added']}\n\n";

        // Provider breakdown
        $providers = $report['provider_breakdown'];
        $content .= "═══════════════════════════════════════\n";
        $content .= "RÉPARTITION PAR OPÉRATEUR\n";
        $content .= "═══════════════════════════════════════\n\n";
        $content .= "Airtel: {$providers['airtel']['count']} ({$providers['airtel']['percentage']}%)\n";
        $content .= "Moov: {$providers['moov']['count']} ({$providers['moov']['percentage']}%)\n\n";

        // Top campaigns
        if (!empty($report['top_campaigns'])) {
            $content .= "═══════════════════════════════════════\n";
            $content .= "TOP CAMPAGNES\n";
            $content .= "═══════════════════════════════════════\n\n";
            foreach ($report['top_campaigns'] as $index => $campaign) {
                $rank = $index + 1;
                $content .= "{$rank}. {$campaign['name']} - {$campaign['messages_sent']} SMS\n";
            }
            $content .= "\n";
        }

        $content .= "───────────────────────────────────────\n";
        $content .= "Connectez-vous à votre tableau de bord pour plus de détails.\n\n";
        $content .= "Cordialement,\n";
        $content .= "L'équipe SendWave Pro\n";

        return $content;
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('SendScheduledReportJob: Job failed after all retries', [
            'frequency' => $this->frequency,
            'error' => $exception->getMessage(),
        ]);
    }

    public function tags(): array
    {
        return ['reports', 'email', 'frequency:' . $this->frequency];
    }
}
