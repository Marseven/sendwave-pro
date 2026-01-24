<?php

namespace Tests\Feature\Api;

use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TemplateControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_templates(): void
    {
        MessageTemplate::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/templates');

        $response->assertOk()
            ->assertJsonStructure(['data', 'categories']);
    }

    public function test_can_list_public_templates_from_other_users(): void
    {
        $otherUser = User::factory()->create();

        // Other user's public template
        MessageTemplate::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => true,
        ]);

        // Other user's private template
        MessageTemplate::factory()->create([
            'user_id' => $otherUser->id,
            'is_public' => false,
        ]);

        $response = $this->getJson('/api/templates');

        // Should see only the public template
        $response->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_can_create_template(): void
    {
        $response = $this->postJson('/api/templates', [
            'name' => 'Welcome Template',
            'content' => 'Hello {nom}, welcome to our service!',
            'category' => 'notifications',
            'is_public' => false,
        ]);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'Welcome Template']);

        $this->assertDatabaseHas('message_templates', [
            'name' => 'Welcome Template',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_update_template(): void
    {
        $template = MessageTemplate::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/templates/{$template->id}", [
            'name' => 'Updated Template Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Template Name']);
    }

    public function test_can_toggle_template_public_status(): void
    {
        $template = MessageTemplate::factory()->create([
            'user_id' => $this->user->id,
            'is_public' => false,
        ]);

        $response = $this->postJson("/api/templates/{$template->id}/toggle-public");

        $response->assertOk();
        $this->assertTrue($template->fresh()->is_public);

        // Toggle back
        $response = $this->postJson("/api/templates/{$template->id}/toggle-public");
        $this->assertFalse($template->fresh()->is_public);
    }

    public function test_can_delete_template(): void
    {
        $template = MessageTemplate::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/templates/{$template->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('message_templates', ['id' => $template->id]);
    }

    public function test_can_use_template(): void
    {
        $template = MessageTemplate::factory()->create([
            'user_id' => $this->user->id,
            'usage_count' => 0,
        ]);

        $response = $this->postJson("/api/templates/{$template->id}/use");

        $response->assertOk();
        $this->assertEquals(1, $template->fresh()->usage_count);
    }

    public function test_can_preview_template(): void
    {
        $template = MessageTemplate::factory()->create([
            'user_id' => $this->user->id,
            'content' => 'Hello {nom}, your code is {code}',
        ]);

        $response = $this->postJson("/api/templates/{$template->id}/preview", [
            'sample_data' => [
                'nom' => 'John',
                'code' => '12345',
            ],
        ]);

        $response->assertOk()
            ->assertJsonStructure(['original', 'preview', 'variables']);
    }

    public function test_cannot_update_other_users_template(): void
    {
        $otherUser = User::factory()->create();
        $template = MessageTemplate::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->putJson("/api/templates/{$template->id}", [
            'name' => 'Hacked Name',
        ]);

        $response->assertNotFound();
    }

    public function test_get_categories(): void
    {
        $response = $this->getJson('/api/templates/categories');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }
}
