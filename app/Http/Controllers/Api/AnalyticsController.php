<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Get dashboard widgets
     * GET /api/analytics/dashboard
     *
     * @OA\Get(
     *     path="/api/analytics/dashboard",
     *     tags={"Analytics"},
     *     summary="Get dashboard analytics widgets",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"today","week","month","year"}), description="Analytics period"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function dashboard(Request $request)
    {
        $period = $request->input('period', 'today');

        $data = $this->analyticsService->getDashboardWidgets(
            $request->user()->id,
            $period
        );

        return response()->json([
            'data' => $data,
            'period' => $period,
        ]);
    }

    /**
     * Get daily chart data
     * GET /api/analytics/chart
     *
     * @OA\Get(
     *     path="/api/analytics/chart",
     *     tags={"Analytics"},
     *     summary="Get daily chart data for analytics",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"week","month","year"}), description="Chart period"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(type="object")),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function chart(Request $request)
    {
        $period = $request->input('period', 'week');

        $data = $this->analyticsService->getDailyChartData(
            $request->user()->id,
            $period
        );

        return response()->json($data);
    }

    /**
     * Get comprehensive report
     * GET /api/analytics/report
     *
     * @OA\Get(
     *     path="/api/analytics/report",
     *     tags={"Analytics"},
     *     summary="Get a comprehensive analytics report",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report start date"),
     *     @OA\Parameter(name="end_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report end date"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function report(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dates = [
            'start' => Carbon::parse($request->start_date)->startOfDay(),
            'end' => Carbon::parse($request->end_date)->endOfDay(),
        ];

        $data = $this->analyticsService->getComprehensiveReport(
            $request->user()->id,
            $dates
        );

        return response()->json([
            'data' => $data,
        ]);
    }

    /**
     * Export report to PDF
     * GET /api/analytics/export/pdf
     *
     * @OA\Get(
     *     path="/api/analytics/export/pdf",
     *     tags={"Analytics"},
     *     summary="Export analytics report as PDF",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report start date"),
     *     @OA\Parameter(name="end_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report end date"),
     *     @OA\Response(response=200, description="PDF file download", @OA\MediaType(mediaType="application/pdf")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function exportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dates = [
            'start' => Carbon::parse($request->start_date)->startOfDay(),
            'end' => Carbon::parse($request->end_date)->endOfDay(),
        ];

        $data = $this->analyticsService->getComprehensiveReport(
            $request->user()->id,
            $dates
        );

        $pdf = \PDF::loadView('reports.analytics', [
            'data' => $data,
            'user' => $request->user(),
        ]);

        $filename = 'analytics_report_' . $request->start_date . '_to_' . $request->end_date . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Export report to Excel
     * GET /api/analytics/export/excel
     *
     * @OA\Get(
     *     path="/api/analytics/export/excel",
     *     tags={"Analytics"},
     *     summary="Export analytics report as Excel",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report start date"),
     *     @OA\Parameter(name="end_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report end date"),
     *     @OA\Response(response=200, description="Excel file download", @OA\MediaType(mediaType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function exportExcel(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dates = [
            'start' => Carbon::parse($request->start_date)->startOfDay(),
            'end' => Carbon::parse($request->end_date)->endOfDay(),
        ];

        $data = $this->analyticsService->getComprehensiveReport(
            $request->user()->id,
            $dates
        );

        return \Excel::download(
            new \App\Exports\AnalyticsExport($data, $request->user()),
            'analytics_report_' . $request->start_date . '_to_' . $request->end_date . '.xlsx'
        );
    }

    /**
     * Export report to CSV
     * GET /api/analytics/export/csv
     *
     * @OA\Get(
     *     path="/api/analytics/export/csv",
     *     tags={"Analytics"},
     *     summary="Export analytics report as CSV",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="start_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report start date"),
     *     @OA\Parameter(name="end_date", in="query", required=true, @OA\Schema(type="string", format="date"), description="Report end date"),
     *     @OA\Response(response=200, description="CSV file download", @OA\MediaType(mediaType="text/csv")),
     *     @OA\Response(response=401, description="Unauthenticated"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function exportCsv(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $dates = [
            'start' => Carbon::parse($request->start_date)->startOfDay(),
            'end' => Carbon::parse($request->end_date)->endOfDay(),
        ];

        $data = $this->analyticsService->getComprehensiveReport(
            $request->user()->id,
            $dates
        );

        $filename = 'analytics_report_' . $request->start_date . '_to_' . $request->end_date . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');

            // Headers
            fputcsv($file, ['Date', 'SMS Envoyés', 'SMS Délivrés', 'SMS Échoués', 'Taux Succès %', 'Airtel', 'Moov', 'Coût Total', 'Campagnes']);

            // Data rows
            foreach ($data['daily_breakdown'] as $day) {
                fputcsv($file, [
                    $day['date'],
                    $day['sms_sent'],
                    $day['sms_delivered'],
                    $day['sms_failed'],
                    $day['success_rate'],
                    $day['airtel_count'],
                    $day['moov_count'],
                    $day['total_cost'],
                    $day['campaigns_sent'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Get provider statistics
     * GET /api/analytics/providers
     *
     * @OA\Get(
     *     path="/api/analytics/providers",
     *     tags={"Analytics"},
     *     summary="Get SMS provider distribution statistics",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"week","month","year"}), description="Statistics period"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="object"),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function providers(Request $request)
    {
        $period = $request->input('period', 'month');

        $dates = $this->analyticsService->getPeriodDates($period);

        $data = $this->analyticsService->getProviderDistribution(
            $request->user()->id,
            $dates
        );

        return response()->json([
            'data' => $data,
            'period' => $period,
        ]);
    }

    /**
     * Get top campaigns
     * GET /api/analytics/top-campaigns
     *
     * @OA\Get(
     *     path="/api/analytics/top-campaigns",
     *     tags={"Analytics"},
     *     summary="Get top performing campaigns",
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(name="period", in="query", required=false, @OA\Schema(type="string", enum={"week","month","year"}), description="Statistics period"),
     *     @OA\Parameter(name="limit", in="query", required=false, @OA\Schema(type="integer", default=5), description="Number of campaigns to return"),
     *     @OA\Response(response=200, description="Success", @OA\JsonContent(
     *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
     *         @OA\Property(property="period", type="string")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function topCampaigns(Request $request)
    {
        $period = $request->input('period', 'month');
        $limit = $request->input('limit', 5);

        $dates = $this->analyticsService->getPeriodDates($period);

        $data = $this->analyticsService->getTopCampaigns(
            $request->user()->id,
            $dates
        );

        return response()->json([
            'data' => $data->take($limit),
            'period' => $period,
        ]);
    }

    /**
     * Manually update analytics for today
     * POST /api/analytics/update
     *
     * @OA\Post(
     *     path="/api/analytics/update",
     *     tags={"Analytics"},
     *     summary="Manually trigger analytics update for a given date",
     *     security={{"sanctum":{}}},
     *     @OA\RequestBody(required=false, @OA\JsonContent(
     *         @OA\Property(property="date", type="string", format="date", description="Date to update analytics for (defaults to today)")
     *     )),
     *     @OA\Response(response=200, description="Analytics updated", @OA\JsonContent(
     *         @OA\Property(property="message", type="string"),
     *         @OA\Property(property="date", type="string", format="date")
     *     )),
     *     @OA\Response(response=401, description="Unauthenticated")
     * )
     */
    public function update(Request $request)
    {
        $date = $request->input('date') ? Carbon::parse($request->input('date')) : now();

        $this->analyticsService->updateDailyAnalytics(
            $request->user()->id,
            $date
        );

        return response()->json([
            'message' => 'Analytics mis à jour avec succès',
            'date' => $date->format('Y-m-d'),
        ]);
    }
}
