<?php

namespace Tests\Unit\Services;

use App\Models\DailyAnalytic;
use App\Models\User;
use App\Models\Message;
use App\Models\Campaign;
use App\Models\Contact;
use App\Services\AnalyticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AnalyticsService $analyticsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->analyticsService = new AnalyticsService();
    }

    public function test_get_period_dates_today(): void
    {
        $dates = $this->analyticsService->getPeriodDates('today');

        $this->assertArrayHasKey('start', $dates);
        $this->assertArrayHasKey('end', $dates);
        $this->assertEquals(now()->startOfDay()->format('Y-m-d'), $dates['start']->format('Y-m-d'));
        $this->assertEquals(now()->endOfDay()->format('Y-m-d'), $dates['end']->format('Y-m-d'));
    }

    public function test_get_period_dates_week(): void
    {
        $dates = $this->analyticsService->getPeriodDates('week');

        $this->assertEquals(now()->startOfWeek()->format('Y-m-d'), $dates['start']->format('Y-m-d'));
        $this->assertEquals(now()->endOfWeek()->format('Y-m-d'), $dates['end']->format('Y-m-d'));
    }

    public function test_get_period_dates_month(): void
    {
        $dates = $this->analyticsService->getPeriodDates('month');

        $this->assertEquals(now()->startOfMonth()->format('Y-m-d'), $dates['start']->format('Y-m-d'));
        $this->assertEquals(now()->endOfMonth()->format('Y-m-d'), $dates['end']->format('Y-m-d'));
    }

    public function test_get_period_dates_last_7_days(): void
    {
        $dates = $this->analyticsService->getPeriodDates('last_7_days');

        $this->assertEquals(now()->subDays(6)->startOfDay()->format('Y-m-d'), $dates['start']->format('Y-m-d'));
        $this->assertEquals(now()->endOfDay()->format('Y-m-d'), $dates['end']->format('Y-m-d'));
    }

    public function test_update_daily_analytics_creates_record(): void
    {
        $user = User::factory()->create();

        // Create some messages for today
        Message::factory()->count(5)->create([
            'user_id' => $user->id,
            'status' => 'sent',
            'provider' => 'airtel',
            'cost' => 25,
            'created_at' => now(),
        ]);

        $this->analyticsService->updateDailyAnalytics($user->id, now());

        $this->assertDatabaseHas('daily_analytics', [
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'sms_sent' => 5,
        ]);
    }

    public function test_get_dashboard_widgets_returns_expected_structure(): void
    {
        $user = User::factory()->create();

        // Create analytics data
        DailyAnalytic::create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'sms_sent' => 100,
            'sms_delivered' => 95,
            'sms_failed' => 5,
            'airtel_count' => 60,
            'moov_count' => 40,
            'total_cost' => 2500,
            'campaigns_sent' => 2,
            'contacts_added' => 10,
        ]);

        $widgets = $this->analyticsService->getDashboardWidgets($user->id, 'today');

        $this->assertArrayHasKey('overview', $widgets);
        $this->assertArrayHasKey('trends', $widgets);
        $this->assertArrayHasKey('providers', $widgets);
        $this->assertArrayHasKey('campaigns', $widgets);
        $this->assertArrayHasKey('cost_analysis', $widgets);
        $this->assertArrayHasKey('hourly_distribution', $widgets);
    }

    public function test_get_comprehensive_report_returns_expected_structure(): void
    {
        $user = User::factory()->create();
        $dates = $this->analyticsService->getPeriodDates('today');

        $report = $this->analyticsService->getComprehensiveReport($user->id, $dates);

        $this->assertArrayHasKey('summary', $report);
        $this->assertArrayHasKey('trends', $report);
        $this->assertArrayHasKey('provider_breakdown', $report);
        $this->assertArrayHasKey('top_campaigns', $report);
        $this->assertArrayHasKey('cost_analysis', $report);
        $this->assertArrayHasKey('daily_breakdown', $report);
        $this->assertArrayHasKey('hourly_distribution', $report);
        $this->assertArrayHasKey('period', $report);
    }

    public function test_success_rate_calculation(): void
    {
        $user = User::factory()->create();

        DailyAnalytic::create([
            'user_id' => $user->id,
            'date' => now()->format('Y-m-d'),
            'sms_sent' => 100,
            'sms_delivered' => 80,
            'sms_failed' => 20,
            'airtel_count' => 50,
            'moov_count' => 50,
            'total_cost' => 2500,
            'campaigns_sent' => 1,
            'contacts_added' => 0,
        ]);

        $widgets = $this->analyticsService->getDashboardWidgets($user->id, 'today');

        $this->assertEquals(80, $widgets['overview']['success_rate']);
    }
}
