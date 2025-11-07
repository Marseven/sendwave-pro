<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapport d'Analytics - SendWave Pro</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            color: #3b82f6;
            margin-bottom: 10px;
        }

        .header .subtitle {
            font-size: 14px;
            color: #666;
        }

        .meta-info {
            background-color: #f3f4f6;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        .meta-info table {
            width: 100%;
        }

        .meta-info td {
            padding: 5px;
        }

        .meta-info td:first-child {
            font-weight: bold;
            width: 30%;
        }

        .section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 2px solid #e5e7eb;
        }

        .stats-grid {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .stat-row {
            display: table-row;
        }

        .stat-cell {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }

        .stat-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            height: 100%;
        }

        .stat-box .label {
            font-size: 11px;
            color: #6b7280;
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .stat-box .value {
            font-size: 24px;
            font-weight: bold;
            color: #1f2937;
        }

        .stat-box.success .value {
            color: #22c55e;
        }

        .stat-box.danger .value {
            color: #ef4444;
        }

        .stat-box.warning .value {
            color: #f59e0b;
        }

        .stat-box.primary .value {
            color: #3b82f6;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table.data-table th {
            background-color: #3b82f6;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
        }

        table.data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        table.data-table tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .provider-chart {
            margin-top: 15px;
        }

        .provider-bar {
            height: 30px;
            background-color: #e5e7eb;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .provider-bar-fill {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 11px;
        }

        .provider-bar-fill.airtel {
            background-color: #ef4444;
        }

        .provider-bar-fill.moov {
            background-color: #3b82f6;
        }

        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
        }

        .trend {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            margin-left: 10px;
        }

        .trend.up {
            background-color: #d1fae5;
            color: #065f46;
        }

        .trend.down {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Rapport d'Analytics</h1>
        <div class="subtitle">SendWave Pro - Plateforme de Gestion de Campagnes SMS</div>
    </div>

    <!-- Meta Information -->
    <div class="meta-info">
        <table>
            <tr>
                <td>Utilisateur:</td>
                <td>{{ $user->name }} ({{ $user->email }})</td>
            </tr>
            <tr>
                <td>Période:</td>
                <td>{{ $data['period']['start'] }} au {{ $data['period']['end'] }} ({{ $data['period']['days'] }} jours)</td>
            </tr>
            <tr>
                <td>Date de génération:</td>
                <td>{{ now()->format('d/m/Y H:i:s') }}</td>
            </tr>
        </table>
    </div>

    <!-- Summary Section -->
    <div class="section">
        <div class="section-title">Résumé Général</div>

        <div class="stats-grid">
            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-box primary">
                        <div class="label">SMS Envoyés</div>
                        <div class="value">{{ number_format($data['summary']['sms_sent']) }}</div>
                    </div>
                </div>
                <div class="stat-cell">
                    <div class="stat-box success">
                        <div class="label">SMS Délivrés</div>
                        <div class="value">{{ number_format($data['summary']['sms_delivered']) }}</div>
                    </div>
                </div>
            </div>

            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-box danger">
                        <div class="label">SMS Échoués</div>
                        <div class="value">{{ number_format($data['summary']['sms_failed']) }}</div>
                    </div>
                </div>
                <div class="stat-cell">
                    <div class="stat-box success">
                        <div class="label">Taux de Succès</div>
                        <div class="value">{{ number_format($data['summary']['success_rate'], 2) }}%</div>
                    </div>
                </div>
            </div>

            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-box warning">
                        <div class="label">Coût Total</div>
                        <div class="value">{{ number_format($data['summary']['total_cost'], 2) }} FCFA</div>
                    </div>
                </div>
                <div class="stat-cell">
                    <div class="stat-box">
                        <div class="label">Coût Moyen / SMS</div>
                        <div class="value">{{ number_format($data['summary']['average_cost_per_sms'], 2) }} FCFA</div>
                    </div>
                </div>
            </div>

            <div class="stat-row">
                <div class="stat-cell">
                    <div class="stat-box primary">
                        <div class="label">Campagnes Exécutées</div>
                        <div class="value">{{ number_format($data['summary']['campaigns_executed']) }}</div>
                    </div>
                </div>
                <div class="stat-cell">
                    <div class="stat-box">
                        <div class="label">Contacts Ajoutés</div>
                        <div class="value">{{ number_format($data['summary']['contacts_added']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Provider Distribution -->
    <div class="section">
        <div class="section-title">Répartition par Opérateur</div>

        <div class="provider-chart">
            <div class="provider-bar">
                <div class="provider-bar-fill airtel" style="width: {{ $data['provider_breakdown']['airtel']['percentage'] }}%">
                    Airtel: {{ number_format($data['provider_breakdown']['airtel']['count']) }} ({{ number_format($data['provider_breakdown']['airtel']['percentage'], 1) }}%)
                </div>
            </div>

            <div class="provider-bar">
                <div class="provider-bar-fill moov" style="width: {{ $data['provider_breakdown']['moov']['percentage'] }}%">
                    Moov: {{ number_format($data['provider_breakdown']['moov']['count']) }} ({{ number_format($data['provider_breakdown']['moov']['percentage'], 1) }}%)
                </div>
            </div>
        </div>
    </div>

    <!-- Cost Analysis -->
    <div class="section">
        <div class="section-title">Analyse des Coûts</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Métrique</th>
                    <th style="text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Coût Total</td>
                    <td style="text-align: right;">{{ number_format($data['cost_analysis']['total_cost'], 2) }} FCFA</td>
                </tr>
                <tr>
                    <td>Coût Airtel</td>
                    <td style="text-align: right;">{{ number_format($data['cost_analysis']['airtel_cost'], 2) }} FCFA</td>
                </tr>
                <tr>
                    <td>Coût Moov</td>
                    <td style="text-align: right;">{{ number_format($data['cost_analysis']['moov_cost'], 2) }} FCFA</td>
                </tr>
                <tr>
                    <td>Coût Quotidien Moyen</td>
                    <td style="text-align: right;">{{ number_format($data['cost_analysis']['average_daily_cost'], 2) }} FCFA</td>
                </tr>
                <tr>
                    <td>Coût Quotidien Maximum</td>
                    <td style="text-align: right;">{{ number_format($data['cost_analysis']['highest_daily_cost'], 2) }} FCFA</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Top Campaigns -->
    @if(count($data['top_campaigns']) > 0)
    <div class="section">
        <div class="section-title">Top 5 Campagnes</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Nom</th>
                    <th>Messages Envoyés</th>
                    <th>Statut</th>
                    <th>Date de Création</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['top_campaigns'] as $campaign)
                <tr>
                    <td>{{ $campaign['name'] }}</td>
                    <td>{{ number_format($campaign['messages_sent']) }}</td>
                    <td>
                        @if($campaign['status'] === 'completed')
                            <span style="color: #22c55e;">Terminée</span>
                        @elseif($campaign['status'] === 'active')
                            <span style="color: #3b82f6;">Active</span>
                        @else
                            <span style="color: #6b7280;">{{ ucfirst($campaign['status']) }}</span>
                        @endif
                    </td>
                    <td>{{ $campaign['created_at'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <!-- Page Break -->
    <div class="page-break"></div>

    <!-- Daily Breakdown -->
    <div class="section">
        <div class="section-title">Détail Quotidien</div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Envoyés</th>
                    <th>Délivrés</th>
                    <th>Échoués</th>
                    <th>Succès %</th>
                    <th>Airtel</th>
                    <th>Moov</th>
                    <th>Coût</th>
                    <th>Campagnes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_breakdown'] as $day)
                <tr>
                    <td>{{ $day['date'] }}</td>
                    <td>{{ number_format($day['sms_sent']) }}</td>
                    <td>{{ number_format($day['sms_delivered']) }}</td>
                    <td>{{ number_format($day['sms_failed']) }}</td>
                    <td>{{ number_format($day['success_rate'], 1) }}%</td>
                    <td>{{ number_format($day['airtel_count']) }}</td>
                    <td>{{ number_format($day['moov_count']) }}</td>
                    <td>{{ number_format($day['total_cost'], 2) }}</td>
                    <td>{{ $day['campaigns_sent'] }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p><strong>SendWave Pro</strong> - Plateforme de Gestion de Campagnes SMS</p>
        <p>Ce rapport a été généré automatiquement le {{ now()->format('d/m/Y à H:i:s') }}</p>
        <p>&copy; {{ date('Y') }} SendWave Pro. Tous droits réservés.</p>
    </div>
</body>
</html>
