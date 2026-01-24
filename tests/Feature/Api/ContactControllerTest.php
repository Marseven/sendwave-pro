<?php

namespace Tests\Feature\Api;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs($this->user);
    }

    public function test_can_list_contacts(): void
    {
        Contact::factory()->count(5)->create(['user_id' => $this->user->id]);

        $response = $this->getJson('/api/contacts');

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(5, 'data');
    }

    public function test_can_create_contact(): void
    {
        $contactData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+24177000000',
            'status' => 'active',
        ];

        $response = $this->postJson('/api/contacts', $contactData);

        $response->assertCreated()
            ->assertJsonFragment(['name' => 'John Doe']);

        $this->assertDatabaseHas('contacts', [
            'name' => 'John Doe',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_can_show_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/contacts/{$contact->id}");

        $response->assertOk()
            ->assertJsonFragment(['id' => $contact->id]);
    }

    public function test_can_update_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        $response = $this->putJson("/api/contacts/{$contact->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Updated Name']);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_can_delete_contact(): void
    {
        $contact = Contact::factory()->create(['user_id' => $this->user->id]);

        $response = $this->deleteJson("/api/contacts/{$contact->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('contacts', ['id' => $contact->id]);
    }

    public function test_cannot_access_other_users_contacts(): void
    {
        $otherUser = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/contacts/{$contact->id}");

        $response->assertNotFound();
    }

    public function test_create_contact_validates_required_fields(): void
    {
        $response = $this->postJson('/api/contacts', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'phone']);
    }

    public function test_can_export_contacts_as_csv(): void
    {
        Contact::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->get('/api/contacts/export?format=csv');

        $response->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }
}
