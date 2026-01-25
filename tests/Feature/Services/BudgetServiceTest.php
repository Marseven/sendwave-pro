<?php

namespace Tests\Feature\Services;

use App\Events\BudgetAlertEvent;
use App\Events\BudgetExceededEvent;
use App\Models\SubAccount;
use App\Models\SmsAnalytics;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * BudgetService integration tests
 * These tests require a MySQL database with all migrations run
 * Run with: php artisan test --group=integration
 *
 * @group integration
 * @group database
 */
class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BudgetService $service;
    protected User $user;
    protected SubAccount $subAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BudgetService();

        // Create test user
        $this->user = User::factory()->create();

        // Create test sub-account with budget
        $this->subAccount = SubAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Test SubAccount',
            'email' => 'test@subaccount.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'monthly_budget' => 100000, // 100,000 FCFA
            'budget_alert_threshold' => 80, // Alert at 80%
            'block_on_budget_exceeded' => true,
        ]);
    }

    public function test_allows_send_when_no_budget_defined(): void
    {
        $subAccountNoBudget = SubAccount::create([
            'user_id' => $this->user->id,
            'name' => 'No Budget Account',
            'email' => 'nobudget@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'monthly_budget' => null,
        ]);

        $result = $this->service->checkBudget($subAccountNoBudget, 1000);

        $this->assertTrue($result['allowed']);
        $this->assertFalse($result['has_budget']);
        $this->assertNull($result['remaining']);
    }

    public function test_allows_send_when_within_budget(): void
    {
        $result = $this->service->checkBudget($this->subAccount, 1000);

        $this->assertTrue($result['allowed']);
        $this->assertTrue($result['has_budget']);
        $this->assertEquals(100000, $result['remaining']);
        $this->assertEquals(0, $result['percent_used']);
    }

    public function test_blocks_send_when_budget_exceeded_and_blocking_enabled(): void
    {
        Event::fake();

        // Add analytics to consume budget
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 99500, // Almost all budget used
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        $result = $this->service->checkBudget($this->subAccount, 1000);

        $this->assertFalse($result['allowed']);
        $this->assertEquals('BUDGET_EXCEEDED', $result['error_code']);
        Event::assertDispatched(BudgetExceededEvent::class);
    }

    public function test_allows_send_when_budget_exceeded_but_blocking_disabled(): void
    {
        Event::fake();

        $this->subAccount->update(['block_on_budget_exceeded' => false]);

        // Add analytics to consume budget
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 99500,
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        $result = $this->service->checkBudget($this->subAccount, 1000);

        $this->assertTrue($result['allowed']);
        Event::assertDispatched(BudgetExceededEvent::class);
    }

    public function test_fires_alert_event_when_threshold_reached(): void
    {
        Event::fake();

        // Add analytics to reach 80% threshold
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 80000, // 80% of 100,000
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        $result = $this->service->checkBudget($this->subAccount, 100);

        $this->assertTrue($result['allowed']);
        $this->assertNotNull($result['warning']);
        Event::assertDispatched(BudgetAlertEvent::class);
    }

    public function test_get_budget_status_with_budget(): void
    {
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 30000,
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        $status = $this->service->getBudgetStatus($this->subAccount);

        $this->assertTrue($status['has_budget']);
        $this->assertEquals(100000, $status['budget']);
        $this->assertEquals(30000, $status['spent']);
        $this->assertEquals(70000, $status['remaining']);
        $this->assertEquals(30, $status['percent_used']);
        $this->assertFalse($status['is_warning']);
        $this->assertFalse($status['is_exceeded']);
        $this->assertEquals('FCFA', $status['currency']);
    }

    public function test_get_budget_status_without_budget(): void
    {
        $subAccountNoBudget = SubAccount::create([
            'user_id' => $this->user->id,
            'name' => 'No Budget Account',
            'email' => 'nobudget2@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'monthly_budget' => null,
        ]);

        $status = $this->service->getBudgetStatus($subAccountNoBudget);

        $this->assertFalse($status['has_budget']);
        $this->assertNull($status['budget']);
        $this->assertEquals(0, $status['spent']);
    }

    public function test_get_spent_amount(): void
    {
        $period = now()->format('Y-m');

        // Current period
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 5000,
            'status' => 'sent',
            'period_key' => $period,
        ]);

        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 2,
            'unit_cost' => 20,
            'sms_parts' => 2,
            'total_cost' => 3000,
            'status' => 'sent',
            'period_key' => $period,
        ]);

        // Previous period (should not be counted)
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 3,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 10000,
            'status' => 'sent',
            'period_key' => '2025-12',
        ]);

        $spent = $this->service->getSpentAmount($this->subAccount->id, $period);

        $this->assertEquals(8000, $spent);
    }

    public function test_update_budget(): void
    {
        $updated = $this->service->updateBudget($this->subAccount, [
            'monthly_budget' => 200000,
            'budget_alert_threshold' => 90,
            'block_on_budget_exceeded' => false,
        ]);

        $this->assertEquals(200000, $updated->monthly_budget);
        $this->assertEquals(90, $updated->budget_alert_threshold);
        $this->assertFalse($updated->block_on_budget_exceeded);
    }

    public function test_partial_budget_update(): void
    {
        $original = $this->subAccount->budget_alert_threshold;

        $updated = $this->service->updateBudget($this->subAccount, [
            'monthly_budget' => 150000,
        ]);

        $this->assertEquals(150000, $updated->monthly_budget);
        $this->assertEquals($original, $updated->budget_alert_threshold);
    }

    public function test_get_sub_accounts_near_threshold(): void
    {
        // Set usage near threshold (75% - within 10% of 80% threshold)
        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $this->subAccount->id,
            'message_id' => 1,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 75000, // 75% of budget
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        // Create another sub-account not near threshold
        $otherSubAccount = SubAccount::create([
            'user_id' => $this->user->id,
            'name' => 'Other Account',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'status' => 'active',
            'monthly_budget' => 100000,
            'budget_alert_threshold' => 80,
        ]);

        SmsAnalytics::create([
            'user_id' => $this->user->id,
            'sub_account_id' => $otherSubAccount->id,
            'message_id' => 2,
            'unit_cost' => 20,
            'sms_parts' => 1,
            'total_cost' => 10000, // Only 10%
            'status' => 'sent',
            'period_key' => now()->format('Y-m'),
        ]);

        $nearThreshold = $this->service->getSubAccountsNearThreshold($this->user->id);

        $this->assertCount(1, $nearThreshold);
        $this->assertEquals($this->subAccount->id, $nearThreshold[0]['id']);
    }
}
