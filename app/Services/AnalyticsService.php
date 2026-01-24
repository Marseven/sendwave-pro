<?php

namespace App\Services;

use App\Models\DailyAnalytic;
use App\Models\Message;
use App\Models\Campaign;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Cache TTL in seconds (5 minutes for dashboard, longer for reports)
     */
    protected const CACHE_TTL_DASHBOARD = 300; // 5 minutes
    protected const CACHE_TTL_REPORT = 900; // 15 minutes

    /**
     * Get dashboard widgets data with caching
     */
    public function getDashboardWidgets(int $userId, string $period = 'today')
    {
        $cacheKey = "analytics:dashboard:{$userId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL_DASHBOARD, function () use ($userId, $period) {
            $dates = $this->getPeriodDates($period);

            return [
                'overview' => $this->getOverviewStats($userId, $dates),
                'trends' => $this->getTrendStats($userId, $dates),
                'providers' => $this->getProviderDistribution($userId, $dates),
                'campaigns' => $this->getTopCampaigns($userId, $dates),
                'cost_analysis' => $this->getCostAnalysis($userId, $dates),
                'hourly_distribution' => $this->getHourlyDistribution($userId, $dates),
            ];
        });
    }

    /**
     * Invalidate dashboard cache for a user
     */
    public function invalidateDashboardCache(int $userId): void
    {
        $periods = ['today', 'yesterday', 'week', 'month', 'last_month', 'year', 'last_7_days', 'last_30_days'];

        foreach ($periods as $period) {
            Cache::forget("analytics:dashboard:{$userId}:{$period}");
            Cache::forget("analytics:chart:{$userId}:{$period}");
        }

        // Also invalidate report caches (pattern-based)
        Cache::forget("analytics:report:{$userId}");
    }

    /**
     * Invalidate all analytics caches for all users
     */
    public function invalidateAllCaches(): void
    {
        // This requires cache tagging or a list of users
        // For file/redis cache, we can use cache tags if available
        Cache::flush(); // Use with caution in production
    }

    /**
     * Get overview statistics
     */
    protected function getOverviewStats(int $userId, array $dates)
    {
        $analytics = DailyAnalytic::byUser($userId)
            ->dateRange($dates['start'], $dates['end'])
            ->get();

        $totalSent = $analytics->sum('sms_sent');
        $totalDelivered = $analytics->sum('sms_delivered');
        $totalFailed = $analytics->sum('sms_failed');
        $totalCost = $analytics->sum('total_cost');

        return [
            'sms_sent' => $totalSent,
            'sms_delivered' => $totalDelivered,
            'sms_failed' => $totalFailed,
            'success_rate' => $totalSent > 0 ? round(($totalDelivered / $totalSent) * 100, 2) : 0,
            'total_cost' => round($totalCost, 2),
            'average_cost_per_sms' => $totalSent > 0 ? round($totalCost / $totalSent, 2) : 0,
            'campaigns_executed' => $analytics->sum('campaigns_sent'),
            'contacts_added' => $analytics->sum('contacts_added'),
        ];
    }

    /**
     * Get trend statistics (comparison with previous period)
     */
    protected function getTrendStats(int $userId, array $dates)
    {
        $currentPeriod = DailyAnalytic::byUser($userId)
            ->dateRange($dates['start'], $dates['end'])
            ->get();

        $previousDates = $this->getPreviousPeriodDates($dates);
        $previousPeriod = DailyAnalytic::byUser($userId)
            ->dateRange($previousDates['start'], $previousDates['end'])
            ->get();

        $currentTotal = $currentPeriod->sum('sms_sent');
        $previousTotal = $previousPeriod->sum('sms_sent');

        return [
            'sms_sent_change' => $this->calculatePercentageChange($previousTotal, $currentTotal),
            'success_rate_change' => $this->calculateRateChange(
                $previousPeriod->sum('sms_delivered'),
                $previousPeriod->sum('sms_sent'),
                $currentPeriod->sum('sms_delivered'),
                $currentPeriod->sum('sms_sent')
            ),
            'cost_change' => $this->calculatePercentageChange(
                $previousPeriod->sum('total_cost'),
                $currentPeriod->sum('total_cost')
            ),
            'campaigns_change' => $this->calculatePercentageChange(
                $previousPeriod->sum('campaigns_sent'),
                $currentPeriod->sum('campaigns_sent')
            ),
        ];
    }

    /**
     * Get provider distribution (Airtel vs Moov)
     */
    protected function getProviderDistribution(int $userId, array $dates)
    {
        $analytics = DailyAnalytic::byUser($userId)
            ->dateRange($dates['start'], $dates['end'])
            ->get();

        $airtelCount = $analytics->sum('airtel_count');
        $moovCount = $analytics->sum('moov_count');
        $total = $airtelCount + $moovCount;

        return [
            'airtel' => [
                'count' => $airtelCount,
                'percentage' => $total > 0 ? round(($airtelCount / $total) * 100, 2) : 0,
            ],
            'moov' => [
                'count' => $moovCount,
                'percentage' => $total > 0 ? round(($moovCount / $total) * 100, 2) : 0,
            ],
        ];
    }

    /**
     * Get top 5 campaigns by message count
     */
    protected function getTopCampaigns(int $userId, array $dates)
    {
        return Campaign::where('user_id', $userId)
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->withCount(['messages' => function ($query) {
                $query->where('status', 'sent');
            }])
            ->orderBy('messages_count', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'status', 'created_at'])
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'name' => $campaign->name,
                    'status' => $campaign->status,
                    'messages_sent' => $campaign->messages_count,
                    'created_at' => $campaign->created_at->format('Y-m-d H:i'),
                ];
            });
    }

    /**
     * Get cost analysis breakdown
     */
    protected function getCostAnalysis(int $userId, array $dates)
    {
        $analytics = DailyAnalytic::byUser($userId)
            ->dateRange($dates['start'], $dates['end'])
            ->get();

        $airtelCount = $analytics->sum('airtel_count');
        $moovCount = $analytics->sum('moov_count');

        // Assuming costs (can be configured)
        $airtelCost = $airtelCount * 25; // 25 FCFA per SMS
        $moovCost = $moovCount * 25;

        return [
            'total_cost' => round($analytics->sum('total_cost'), 2),
            'airtel_cost' => round($airtelCost, 2),
            'moov_cost' => round($moovCost, 2),
            'average_daily_cost' => round($analytics->avg('total_cost'), 2),
            'highest_daily_cost' => round($analytics->max('total_cost'), 2),
            'lowest_daily_cost' => round($analytics->where('total_cost', '>', 0)->min('total_cost'), 2),
        ];
    }

    /**
     * Get hourly distribution of messages
     */
    protected function getHourlyDistribution(int $userId, array $dates)
    {
        $messages = Message::where('user_id', $userId)
            ->whereBetween('created_at', [$dates['start'], $dates['end']])
            ->get();

        $hourlyData = [];
        for ($i = 0; $i < 24; $i++) {
            $hourlyData[$i] = 0;
        }

        foreach ($messages as $message) {
            $hour = (int) $message->created_at->format('H');
            $hourlyData[$hour]++;
        }

        return array_map(function ($hour, $count) {
            return [
                'hour' => sprintf('%02d:00', $hour),
                'count' => $count,
            ];
        }, array_keys($hourlyData), $hourlyData);
    }

    /**
     * Get daily chart data with caching
     */
    public function getDailyChartData(int $userId, string $period = 'week')
    {
        $cacheKey = "analytics:chart:{$userId}:{$period}";

        return Cache::remember($cacheKey, self::CACHE_TTL_DASHBOARD, function () use ($userId, $period) {
            $dates = $this->getPeriodDates($period);

            $analytics = DailyAnalytic::byUser($userId)
                ->dateRange($dates['start'], $dates['end'])
                ->orderBy('date')
                ->get();

            return [
                'labels' => $analytics->pluck('date')->map(fn($date) => $date->format('Y-m-d'))->toArray(),
                'datasets' => [
                    [
                        'label' => 'SMS Envoyés',
                        'data' => $analytics->pluck('sms_sent')->toArray(),
                        'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                        'borderColor' => 'rgb(59, 130, 246)',
                    ],
                    [
                        'label' => 'SMS Délivrés',
                        'data' => $analytics->pluck('sms_delivered')->toArray(),
                        'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                        'borderColor' => 'rgb(34, 197, 94)',
                    ],
                    [
                        'label' => 'SMS Échoués',
                        'data' => $analytics->pluck('sms_failed')->toArray(),
                        'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                        'borderColor' => 'rgb(239, 68, 68)',
                    ],
                ],
            ];
        });
    }

    /**
     * Get comprehensive report data with caching
     */
    public function getComprehensiveReport(int $userId, array $dates)
    {
        $startStr = $dates['start']->format('Y-m-d');
        $endStr = $dates['end']->format('Y-m-d');
        $cacheKey = "analytics:report:{$userId}:{$startStr}:{$endStr}";

        return Cache::remember($cacheKey, self::CACHE_TTL_REPORT, function () use ($userId, $dates) {
            return [
                'summary' => $this->getOverviewStats($userId, $dates),
                'trends' => $this->getTrendStats($userId, $dates),
                'provider_breakdown' => $this->getProviderDistribution($userId, $dates),
                'top_campaigns' => $this->getTopCampaigns($userId, $dates),
                'cost_analysis' => $this->getCostAnalysis($userId, $dates),
                'daily_breakdown' => $this->getDailyBreakdown($userId, $dates),
                'hourly_distribution' => $this->getHourlyDistribution($userId, $dates),
                'period' => [
                    'start' => $dates['start']->format('Y-m-d'),
                    'end' => $dates['end']->format('Y-m-d'),
                    'days' => $dates['start']->diffInDays($dates['end']) + 1,
                ],
            ];
        });
    }

    /**
     * Get daily breakdown
     */
    protected function getDailyBreakdown(int $userId, array $dates)
    {
        return DailyAnalytic::byUser($userId)
            ->dateRange($dates['start'], $dates['end'])
            ->orderBy('date', 'desc')
            ->get()
            ->map(function ($analytic) {
                return [
                    'date' => $analytic->date->format('Y-m-d'),
                    'sms_sent' => $analytic->sms_sent,
                    'sms_delivered' => $analytic->sms_delivered,
                    'sms_failed' => $analytic->sms_failed,
                    'success_rate' => $analytic->success_rate,
                    'airtel_count' => $analytic->airtel_count,
                    'moov_count' => $analytic->moov_count,
                    'total_cost' => round($analytic->total_cost, 2),
                    'average_cost_per_sms' => $analytic->average_cost_per_sms,
                    'campaigns_sent' => $analytic->campaigns_sent,
                ];
            });
    }

    /**
     * Update daily analytics (called by scheduler or after SMS sent)
     */
    public function updateDailyAnalytics(int $userId, Carbon $date = null)
    {
        $date = $date ?? now();
        $dateString = $date->format('Y-m-d');

        $messages = Message::where('user_id', $userId)
            ->whereDate('created_at', $dateString)
            ->get();

        $campaigns = Campaign::where('user_id', $userId)
            ->whereDate('created_at', $dateString)
            ->count();

        $contacts = Contact::where('user_id', $userId)
            ->whereDate('created_at', $dateString)
            ->count();

        $smsSent = $messages->count();
        $smsDelivered = $messages->where('status', 'sent')->count();
        $smsFailed = $messages->where('status', 'failed')->count();
        $airtelCount = $messages->where('provider', 'airtel')->count();
        $moovCount = $messages->where('provider', 'moov')->count();
        $totalCost = $messages->sum('cost');

        DailyAnalytic::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $dateString,
            ],
            [
                'sms_sent' => $smsSent,
                'sms_delivered' => $smsDelivered,
                'sms_failed' => $smsFailed,
                'airtel_count' => $airtelCount,
                'moov_count' => $moovCount,
                'total_cost' => $totalCost,
                'campaigns_sent' => $campaigns,
                'contacts_added' => $contacts,
            ]
        );

        // Invalidate cache after updating analytics
        $this->invalidateDashboardCache($userId);
    }

    /**
     * Get period dates
     */
    public function getPeriodDates(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'yesterday' => [
                'start' => now()->subDay()->startOfDay(),
                'end' => now()->subDay()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            'last_7_days' => [
                'start' => now()->subDays(6)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            'last_30_days' => [
                'start' => now()->subDays(29)->startOfDay(),
                'end' => now()->endOfDay(),
            ],
            default => [
                'start' => now()->startOfDay(),
                'end' => now()->endOfDay(),
            ],
        };
    }

    /**
     * Get previous period dates
     */
    protected function getPreviousPeriodDates(array $currentDates): array
    {
        $days = $currentDates['start']->diffInDays($currentDates['end']) + 1;

        return [
            'start' => $currentDates['start']->copy()->subDays($days),
            'end' => $currentDates['end']->copy()->subDays($days),
        ];
    }

    /**
     * Calculate percentage change
     */
    protected function calculatePercentageChange($oldValue, $newValue): float
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }

    /**
     * Calculate rate change
     */
    protected function calculateRateChange($oldSuccess, $oldTotal, $newSuccess, $newTotal): float
    {
        $oldRate = $oldTotal > 0 ? ($oldSuccess / $oldTotal) * 100 : 0;
        $newRate = $newTotal > 0 ? ($newSuccess / $newTotal) * 100 : 0;

        return round($newRate - $oldRate, 2);
    }
}
