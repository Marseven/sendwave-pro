<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnalyticsExport implements FromArray, WithHeadings, WithStyles, WithTitle
{
    protected $data;
    protected $user;

    public function __construct(array $data, $user)
    {
        $this->data = $data;
        $this->user = $user;
    }

    /**
     * Return array of data
     */
    public function array(): array
    {
        $rows = [];

        // Summary section
        $rows[] = ['RÉSUMÉ GÉNÉRAL'];
        $rows[] = ['SMS Envoyés', $this->data['summary']['sms_sent']];
        $rows[] = ['SMS Délivrés', $this->data['summary']['sms_delivered']];
        $rows[] = ['SMS Échoués', $this->data['summary']['sms_failed']];
        $rows[] = ['Taux de Succès', $this->data['summary']['success_rate'] . '%'];
        $rows[] = ['Coût Total', number_format($this->data['summary']['total_cost'], 2) . ' FCFA'];
        $rows[] = ['Coût Moyen par SMS', number_format($this->data['summary']['average_cost_per_sms'], 2) . ' FCFA'];
        $rows[] = ['Campagnes Exécutées', $this->data['summary']['campaigns_executed']];
        $rows[] = ['Contacts Ajoutés', $this->data['summary']['contacts_added']];
        $rows[] = [];

        // Provider breakdown
        $rows[] = ['RÉPARTITION PAR OPÉRATEUR'];
        $rows[] = ['Airtel', $this->data['provider_breakdown']['airtel']['count'], $this->data['provider_breakdown']['airtel']['percentage'] . '%'];
        $rows[] = ['Moov', $this->data['provider_breakdown']['moov']['count'], $this->data['provider_breakdown']['moov']['percentage'] . '%'];
        $rows[] = [];

        // Cost analysis
        $rows[] = ['ANALYSE DES COÛTS'];
        $rows[] = ['Coût Total', number_format($this->data['cost_analysis']['total_cost'], 2) . ' FCFA'];
        $rows[] = ['Coût Airtel', number_format($this->data['cost_analysis']['airtel_cost'], 2) . ' FCFA'];
        $rows[] = ['Coût Moov', number_format($this->data['cost_analysis']['moov_cost'], 2) . ' FCFA'];
        $rows[] = ['Coût Quotidien Moyen', number_format($this->data['cost_analysis']['average_daily_cost'], 2) . ' FCFA'];
        $rows[] = ['Coût Quotidien Max', number_format($this->data['cost_analysis']['highest_daily_cost'], 2) . ' FCFA'];
        $rows[] = [];

        // Daily breakdown
        $rows[] = ['DÉTAIL QUOTIDIEN'];
        $rows[] = []; // Empty row before headers

        // Add headers for daily data
        $rows[] = [
            'Date',
            'SMS Envoyés',
            'SMS Délivrés',
            'SMS Échoués',
            'Taux Succès %',
            'Airtel',
            'Moov',
            'Coût Total',
            'Campagnes'
        ];

        // Add daily data
        foreach ($this->data['daily_breakdown'] as $day) {
            $rows[] = [
                $day['date'],
                $day['sms_sent'],
                $day['sms_delivered'],
                $day['sms_failed'],
                $day['success_rate'],
                $day['airtel_count'],
                $day['moov_count'],
                number_format($day['total_cost'], 2),
                $day['campaigns_sent'],
            ];
        }

        return $rows;
    }

    /**
     * Define headings
     */
    public function headings(): array
    {
        return [
            'Rapport d\'Analytics SendWave Pro',
            'Généré le: ' . now()->format('d/m/Y H:i'),
            'Utilisateur: ' . $this->user->name,
            'Période: ' . $this->data['period']['start'] . ' au ' . $this->data['period']['end'],
        ];
    }

    /**
     * Apply styles
     */
    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16]],
            2 => ['font' => ['italic' => true]],
            3 => ['font' => ['italic' => true]],
            4 => ['font' => ['italic' => true]],
        ];
    }

    /**
     * Sheet title
     */
    public function title(): string
    {
        return 'Rapport Analytics';
    }
}
