<?php

namespace Tests\Feature\Api;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\CampaignVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CampaignControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_campaigns(): void
    {
        Campaign::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/campaigns');

        $response->assertOk()
            ->assertJsonCount(5);
    }

    public function test_can_create_campaign(): void
    {
        $campaignData = [
            'name' => 'Test Campaign',
            'status' => CampaignStatus::DRAFT->value,
            'message_content' => 'Hello {nom}!',
        ];

        $response = $this->postJson('/api/campaigns', $campaignData);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Test Campaign']);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Test Campaign',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_campaign(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $campaign->id]);
    }

    public function test_can_update_campaign(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/campaigns/{$campaign->id}", [
            'name' => 'Updated Campaign Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Campaign Name']);
    }

    public function test_can_delete_campaign(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
    }

    public function test_can_clone_campaign(): void
    {
        $campaign = Campaign::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Original Campaign',
            'message_content' => 'Test message',
        ]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/clone");

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Original Campaign (copie)']);

        $this->assertDatabaseCount('campaigns', 2);
    }

    public function test_clone_includes_variants(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);
        CampaignVariant::factory()->count(2)->create(['campaign_id' => $campaign->id]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/clone");

        $response->assertCreated();

        $clonedCampaign = Campaign::where('name', 'like', '%copie%')->first();
        $this->assertCount(2, $clonedCampaign->variants);
    }

    public function test_can_create_campaign_schedule(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/schedule", [
            'frequency' => 'weekly',
            'day_of_week' => 1,
            'time' => '09:00',
            'is_active' => true,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('campaign_schedules', [
            'campaign_id' => $campaign->id,
            'frequency' => 'weekly',
        ]);
    }

    public function test_can_create_campaign_variants(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/variants", [
            'variants' => [
                ['variant_name' => 'A', 'message' => 'Hello A', 'percentage' => 50],
                ['variant_name' => 'B', 'message' => 'Hello B', 'percentage' => 50],
            ],
        ]);

        $response->assertCreated();
        $this->assertDatabaseCount('campaign_variants', 2);
    }

    public function test_variants_must_sum_to_100_percent(): void
    {
        $campaign = Campaign::factory()->create(['user_id' => $this->user->id]);

        $response = $this->postJson("/api/campaigns/{$campaign->id}/variants", [
            'variants' => [
                ['variant_name' => 'A', 'message' => 'Hello A', 'percentage' => 30],
                ['variant_name' => 'B', 'message' => 'Hello B', 'percentage' => 30],
            ],
        ]);

        $response->assertUnprocessable();
    }

    public function test_cannot_access_other_users_campaigns(): void
    {
        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertNotFound();
    }
}
