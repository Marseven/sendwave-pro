<?php

namespace App\Http\Controllers;

use App\Models\SmsAnalytics;
use App\Models\PeriodClosure;
use App\Services\PeriodClosureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SmsAnalyticsController extends Controller
{
    public function __construct(
        protected PeriodClosureService $periodClosureService
    ) {}

    /**
     * Vue d'ensemble des analytics pour la période en cours
     */
    public function overview(Request $request)
    {
        $user = $request->user();
        $period = $request->input('period', now()->format('Y-m'));

        // Statistiques de la période
        $stats = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->selectRaw('
                COUNT(*) as total_sms,
                SUM(total_cost) as total_cost,
                SUM(CASE WHEN status = "sent" THEN 1 ELSE 0 END) as sent_count,
                SUM(CASE WHEN status = "failed" THEN 1 ELSE 0 END) as failed_count,
                SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) as pending_count
            ')
            ->first();

        // Répartition par opérateur
        $byOperator = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->groupBy('operator')
            ->selectRaw('operator, COUNT(*) as count, SUM(total_cost) as cost')
            ->get()
            ->keyBy('operator');

        // Répartition par type de message
        $byType = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->groupBy('message_type')
            ->selectRaw('message_type, COUNT(*) as count, SUM(total_cost) as cost')
            ->get()
            ->keyBy('message_type');

        // Répartition par sous-compte
        $bySubAccount = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->with('subAccount:id,name')
            ->groupBy('sub_account_id')
            ->selectRaw('sub_account_id, COUNT(*) as count, SUM(total_cost) as cost')
            ->get();

        // Évolution journalière
        $dailyTrend = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->groupBy('date')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(total_cost) as cost')
            ->orderBy('date')
            ->get();

        return response()->json([
            'period' => $period,
            'overview' => [
                'total_sms' => $stats->total_sms ?? 0,
                'total_cost' => $stats->total_cost ?? 0,
                'sent' => $stats->sent_count ?? 0,
                'failed' => $stats->failed_count ?? 0,
                'pending' => $stats->pending_count ?? 0,
                'success_rate' => $stats->total_sms > 0
                    ? round(($stats->sent_count / $stats->total_sms) * 100, 2)
                    : 0,
            ],
            'by_operator' => $byOperator,
            'by_type' => $byType,
            'by_sub_account' => $bySubAccount,
            'daily_trend' => $dailyTrend,
        ]);
    }

    /**
     * Liste des analytics détaillés
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 50);

        $query = SmsAnalytics::where('user_id', $user->id)
            ->with(['subAccount:id,name', 'campaign:id,name', 'message:id,recipient_phone,content']);

        // Filtres
        if ($request->filled('period')) {
            $query->where('period_key', $request->input('period'));
        }

        if ($request->filled('operator')) {
            $query->where('operator', $request->input('operator'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('sub_account_id')) {
            $query->where('sub_account_id', $request->input('sub_account_id'));
        }

        if ($request->filled('message_type')) {
            $query->where('message_type', $request->input('message_type'));
        }

        $analytics = $query->orderByDesc('created_at')->paginate($perPage);

        return response()->json($analytics);
    }

    /**
     * Liste des périodes disponibles
     */
    public function periods(Request $request)
    {
        $user = $request->user();

        $periods = SmsAnalytics::where('user_id', $user->id)
            ->groupBy('period_key')
            ->selectRaw('
                period_key,
                COUNT(*) as total_sms,
                SUM(total_cost) as total_cost,
                MIN(is_closed) as is_closed
            ')
            ->orderByDesc('period_key')
            ->get()
            ->map(function ($period) {
                return [
                    'period_key' => $period->period_key,
                    'formatted' => \Carbon\Carbon::createFromFormat('Y-m', $period->period_key)->translatedFormat('F Y'),
                    'total_sms' => $period->total_sms,
                    'total_cost' => $period->total_cost,
                    'is_closed' => (bool) $period->is_closed,
                ];
            });

        return response()->json(['periods' => $periods]);
    }

    /**
     * Liste des clôtures de période
     */
    public function closures(Request $request)
    {
        $user = $request->user();

        $closures = PeriodClosure::where('user_id', $user->id)
            ->orderByDesc('period_key')
            ->get();

        return response()->json(['closures' => $closures]);
    }

    /**
     * Détail d'une clôture
     */
    public function closureDetail(Request $request, string $periodKey)
    {
        $user = $request->user();

        $closure = PeriodClosure::where('user_id', $user->id)
            ->where('period_key', $periodKey)
            ->firstOrFail();

        return response()->json(['closure' => $closure]);
    }

    /**
     * Générer un rapport pour une période
     */
    public function generateReport(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $user = $request->user();
        $report = $this->periodClosureService->generateReport($user, $validated['period']);

        return response()->json(['report' => $report]);
    }

    /**
     * Exporter les analytics en CSV
     */
    public function export(Request $request)
    {
        $user = $request->user();
        $period = $request->input('period', now()->format('Y-m'));

        $analytics = SmsAnalytics::where('user_id', $user->id)
            ->where('period_key', $period)
            ->with(['subAccount:id,name', 'message:id,recipient_phone'])
            ->orderByDesc('created_at')
            ->get();

        $filename = "analytics_{$period}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($analytics) {
            $file = fopen('php://output', 'w');

            // En-têtes CSV
            fputcsv($file, [
                'Date',
                'Destinataire',
                'Opérateur',
                'Type',
                'Passerelle',
                'Parties SMS',
                'Coût unitaire',
                'Coût total',
                'Statut',
                'Sous-compte',
            ]);

            foreach ($analytics as $row) {
                fputcsv($file, [
                    $row->created_at->format('Y-m-d H:i:s'),
                    $row->message?->recipient_phone ?? '-',
                    $row->operator ?? '-',
                    $row->message_type,
                    $row->gateway ?? '-',
                    $row->sms_parts,
                    $row->unit_cost,
                    $row->total_cost,
                    $row->status,
                    $row->subAccount?->name ?? 'Principal',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
