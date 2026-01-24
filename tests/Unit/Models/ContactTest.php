<?php

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\ContactGroup;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_can_be_created(): void
    {
        $user = User::factory()->create();

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+24177000000',
            'status' => 'active',
        ]);

        $this->assertDatabaseHas('contacts', [
            'id' => $contact->id,
            'name' => 'John Doe',
            'phone' => '+24177000000',
        ]);
    }

    public function test_contact_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $contact->user);
        $this->assertEquals($user->id, $contact->user->id);
    }

    public function test_contact_has_many_messages(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);

        Message::factory()->count(3)->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertCount(3, $contact->messages);
    }

    public function test_contact_belongs_to_many_groups(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        $groups = ContactGroup::factory()->count(2)->create(['user_id' => $user->id]);

        $contact->groups()->attach($groups->pluck('id'));

        $this->assertCount(2, $contact->groups);
    }

    public function test_contact_scope_active(): void
    {
        $user = User::factory()->create();

        Contact::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => 'active',
        ]);
        Contact::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => 'inactive',
        ]);

        $activeContacts = Contact::where('user_id', $user->id)
            ->where('status', 'active')
            ->get();

        $this->assertCount(3, $activeContacts);
    }

    public function test_contact_custom_fields_are_cast_to_array(): void
    {
        $user = User::factory()->create();

        $contact = Contact::create([
            'user_id' => $user->id,
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+24166000000',
            'status' => 'active',
            'custom_fields' => ['company' => 'Acme Inc', 'role' => 'Manager'],
        ]);

        $this->assertIsArray($contact->custom_fields);
        $this->assertEquals('Acme Inc', $contact->custom_fields['company']);
    }
}
