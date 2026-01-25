<?php

namespace App\Console\Commands;

use App\Services\PeriodClosureService;
use Illuminate\Console\Command;

class ClosePeriodCommand extends Command
{
    protected $signature = 'sms:close-period
                            {--period= : Période à clôturer (YYYY-MM). Par défaut: mois précédent}
                            {--user= : ID utilisateur spécifique}
                            {--dry-run : Simuler sans effectuer la clôture}';

    protected $description = 'Clôture la période mensuelle pour les analytics SMS';

    public function handle(PeriodClosureService $service): int
    {
        $period = $this->option('period') ?? now()->subMonth()->format('Y-m');
        $userId = $this->option('user');
        $dryRun = $this->option('dry-run');

        $this->info("=== Clôture de la période: {$period} ===");

        if ($dryRun) {
            $this->warn('Mode simulation activé - Aucune modification ne sera effectuée');
        }

        if ($userId) {
            // Clôturer pour un utilisateur spécifique
            $user = \App\Models\User::find($userId);

            if (!$user) {
                $this->error("Utilisateur #{$userId} introuvable");
                return Command::FAILURE;
            }

            $this->line("Clôture pour: {$user->name} ({$user->email})");

            if (!$dryRun) {
                $closure = $service->closePeriod($user, $period);
                $this->info("✓ {$closure->total_sms} SMS, {$closure->total_cost} FCFA");
            }
        } else {
            // Clôturer pour tous les utilisateurs
            $this->line('Clôture pour tous les utilisateurs...');
            $this->newLine();

            if (!$dryRun) {
                $results = $service->closeAllUsers($period);

                $table = [];
                $totalSms = 0;
                $totalCost = 0;
                $errors = 0;

                foreach ($results as $result) {
                    if ($result['status'] === 'success') {
                        $table[] = [
                            $result['user_id'],
                            $result['user_name'],
                            $result['total_sms'],
                            number_format($result['total_cost'], 0, ',', ' ') . ' FCFA',
                            '✓',
                        ];
                        $totalSms += $result['total_sms'];
                        $totalCost += $result['total_cost'];
                    } else {
                        $table[] = [
                            $result['user_id'],
                            $result['user_name'],
                            '-',
                            '-',
                            '✗ ' . substr($result['error'], 0, 30),
                        ];
                        $errors++;
                    }
                }

                $this->table(
                    ['ID', 'Utilisateur', 'SMS', 'Coût', 'Statut'],
                    $table
                );

                $this->newLine();
                $this->info("=== Résumé ===");
                $this->line("Total SMS: {$totalSms}");
                $this->line("Total Coût: " . number_format($totalCost, 0, ',', ' ') . " FCFA");
                $this->line("Utilisateurs traités: " . count($results));

                if ($errors > 0) {
                    $this->warn("Erreurs: {$errors}");
                }
            }
        }

        $this->newLine();
        $this->info('Clôture terminée!');

        return Command::SUCCESS;
    }
}
