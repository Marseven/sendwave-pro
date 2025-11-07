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
