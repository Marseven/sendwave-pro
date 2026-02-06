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
     * @OA\Get(
     *     path="/api/sms-analytics/overview",
     *     tags={"SMS Analytics"},
     *     summary="Get analytics overview",
     *     description="Vue d'ensemble des analytics pour une période donnée (par défaut la période en cours). Inclut les statistiques globales, par opérateur, par type et par sous-compte.",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         description="Période au format YYYY-MM",
     *         @OA\Schema(type="string", example="2026-01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Vue d'ensemble des analytics",
     *         @OA\JsonContent(
     *             @OA\Property(property="period", type="string", example="2026-01"),
     *             @OA\Property(property="overview", type="object",
     *                 @OA\Property(property="total_sms", type="integer", example=500),
     *                 @OA\Property(property="total_cost", type="number", example=10000),
     *                 @OA\Property(property="sent", type="integer", example=480),
     *                 @OA\Property(property="failed", type="integer", example=15),
     *                 @OA\Property(property="pending", type="integer", example=5),
     *                 @OA\Property(property="success_rate", type="number", example=96.0)
     *             ),
     *             @OA\Property(property="by_operator", type="object"),
     *             @OA\Property(property="by_type", type="object"),
     *             @OA\Property(property="by_sub_account", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="daily_trend", type="array", @OA\Items(type="object",
     *                 @OA\Property(property="date", type="string", format="date", example="2026-01-15"),
     *                 @OA\Property(property="count", type="integer", example=25),
     *                 @OA\Property(property="cost", type="number", example=500)
     *             ))
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     * @OA\Get(
     *     path="/api/sms-analytics",
     *     tags={"SMS Analytics"},
     *     summary="List detailed analytics",
     *     description="Liste paginée des analytics SMS détaillés avec filtres optionnels",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="per_page",
     *         in="query",
     *         required=false,
     *         description="Nombre d'éléments par page",
     *         @OA\Schema(type="integer", default=50, example=50)
     *     ),
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         description="Filtrer par période (YYYY-MM)",
     *         @OA\Schema(type="string", example="2026-01")
     *     ),
     *     @OA\Parameter(
     *         name="operator",
     *         in="query",
     *         required=false,
     *         description="Filtrer par opérateur",
     *         @OA\Schema(type="string", enum={"airtel", "moov"}, example="airtel")
     *     ),
     *     @OA\Parameter(
     *         name="status",
     *         in="query",
     *         required=false,
     *         description="Filtrer par statut",
     *         @OA\Schema(type="string", enum={"sent", "failed", "pending"}, example="sent")
     *     ),
     *     @OA\Parameter(
     *         name="sub_account_id",
     *         in="query",
     *         required=false,
     *         description="Filtrer par sous-compte",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="message_type",
     *         in="query",
     *         required=false,
     *         description="Filtrer par type de message",
     *         @OA\Schema(type="string", example="transactional")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste paginée des analytics",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *             @OA\Property(property="current_page", type="integer", example=1),
     *             @OA\Property(property="last_page", type="integer", example=5),
     *             @OA\Property(property="per_page", type="integer", example=50),
     *             @OA\Property(property="total", type="integer", example=250)
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min((int) $request->input('per_page', 50), 100);

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
     * @OA\Get(
     *     path="/api/sms-analytics/periods",
     *     tags={"SMS Analytics"},
     *     summary="List available periods",
     *     description="Liste des périodes disponibles avec statistiques agrégées pour chaque période",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des périodes",
     *         @OA\JsonContent(
     *             @OA\Property(property="periods", type="array",
     *                 @OA\Items(type="object",
     *                     @OA\Property(property="period_key", type="string", example="2026-01"),
     *                     @OA\Property(property="formatted", type="string", example="janvier 2026"),
     *                     @OA\Property(property="total_sms", type="integer", example=500),
     *                     @OA\Property(property="total_cost", type="number", example=10000),
     *                     @OA\Property(property="is_closed", type="boolean", example=false)
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     * @OA\Get(
     *     path="/api/sms-analytics/closures",
     *     tags={"SMS Analytics"},
     *     summary="List period closures",
     *     description="Liste des clôtures de période mensuelles",
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Liste des clôtures",
     *         @OA\JsonContent(
     *             @OA\Property(property="closures", type="array",
     *                 @OA\Items(type="object")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
     * @OA\Get(
     *     path="/api/sms-analytics/closures/{periodKey}",
     *     tags={"SMS Analytics"},
     *     summary="Get closure detail",
     *     description="Détail d'une clôture de période spécifique",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="periodKey",
     *         in="path",
     *         required=true,
     *         description="Clé de la période au format YYYY-MM",
     *         @OA\Schema(type="string", example="2026-01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détail de la clôture",
     *         @OA\JsonContent(
     *             @OA\Property(property="closure", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=404, description="Clôture non trouvée")
     * )
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
     * @OA\Post(
     *     path="/api/sms-analytics/report",
     *     tags={"SMS Analytics"},
     *     summary="Generate period report",
     *     description="Générer un rapport détaillé pour une période spécifique",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"period"},
     *             @OA\Property(property="period", type="string", example="2026-01", description="Période au format YYYY-MM")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Rapport généré",
     *         @OA\JsonContent(
     *             @OA\Property(property="report", type="object")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
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
     * @OA\Get(
     *     path="/api/sms-analytics/export",
     *     tags={"SMS Analytics"},
     *     summary="Export analytics as CSV",
     *     description="Exporter les analytics en fichier CSV pour une période donnée",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="period",
     *         in="query",
     *         required=false,
     *         description="Période au format YYYY-MM (par défaut la période en cours)",
     *         @OA\Schema(type="string", example="2026-01")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Fichier CSV téléchargé",
     *         @OA\MediaType(
     *             mediaType="text/csv",
     *             @OA\Schema(type="string", format="binary")
     *         )
     *     ),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
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
