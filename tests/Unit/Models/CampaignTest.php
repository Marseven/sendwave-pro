<?php

namespace Tests\Unit\Models;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignSchedule;
use App\Models\CampaignVariant;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_be_created(): void
    {
        $user = User::factory()->create();

        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT->value,
            'message_content' => 'Hello {nom}!',
        ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);
    }

    public function test_campaign_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $campaign->user);
        $this->assertEquals($user->id, $campaign->user->id);
    }

    public function test_campaign_has_many_messages(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        Message::factory()->count(5)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
        ]);

        $this->assertCount(5, $campaign->messages);
    }

    public function test_campaign_has_one_schedule(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        CampaignSchedule::create([
            'campaign_id' => $campaign->id,
            'frequency' => 'weekly',
            'time' => '09:00',
            'day_of_week' => 1,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(CampaignSchedule::class, $campaign->schedule);
    }

    public function test_campaign_has_many_variants(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        CampaignVariant::factory()->count(3)->create([
            'campaign_id' => $campaign->id,
        ]);

        $this->assertCount(3, $campaign->variants);
    }

    public function test_campaign_status_enum_values(): void
    {
        $this->assertEquals('draft', CampaignStatus::DRAFT->value);
        $this->assertEquals('scheduled', CampaignStatus::SCHEDULED->value);
        $this->assertEquals('sending', CampaignStatus::SENDING->value);
        $this->assertEquals('completed', CampaignStatus::COMPLETED->value);
        $this->assertEquals('failed', CampaignStatus::FAILED->value);
        $this->assertEquals('cancelled', CampaignStatus::CANCELLED->value);
    }

    public function test_campaign_can_be_replicated(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'name' => 'Original Campaign',
            'message_content' => 'Hello World',
        ]);

        $clone = $campaign->replicate();
        $clone->name = 'Cloned Campaign';
        $clone->status = CampaignStatus::DRAFT->value;
        $clone->save();

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Cloned Campaign',
            'message_content' => 'Hello World',
        ]);
        $this->assertNotEquals($campaign->id, $clone->id);
    }
}
