<?php

namespace Tests\Unit\Models;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\User;
use App\Models\Contact;
use App\Models\Campaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_can_be_created(): void
    {
        $user = User::factory()->create();

        $message = Message::create([
            'user_id' => $user->id,
            'recipient_phone' => '+24177000000',
            'content' => 'Test message content',
            'type' => 'sms',
            'status' => MessageStatus::PENDING->value,
            'provider' => 'airtel',
            'cost' => 25,
        ]);

        $this->assertDatabaseHas('messages', [
            'id' => $message->id,
            'recipient_phone' => '+24177000000',
            'status' => 'pending',
        ]);
    }

    public function test_message_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $message = Message::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $message->user);
        $this->assertEquals($user->id, $message->user->id);
    }

    public function test_message_belongs_to_contact(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create(['user_id' => $user->id]);
        $message = Message::factory()->create([
            'user_id' => $user->id,
            'contact_id' => $contact->id,
        ]);

        $this->assertInstanceOf(Contact::class, $message->contact);
        $this->assertEquals($contact->id, $message->contact->id);
    }

    public function test_message_belongs_to_campaign(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);
        $message = Message::factory()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
        ]);

        $this->assertInstanceOf(Campaign::class, $message->campaign);
        $this->assertEquals($campaign->id, $message->campaign->id);
    }

    public function test_message_status_enum_values(): void
    {
        $this->assertEquals('pending', MessageStatus::PENDING->value);
        $this->assertEquals('sent', MessageStatus::SENT->value);
        $this->assertEquals('delivered', MessageStatus::DELIVERED->value);
        $this->assertEquals('failed', MessageStatus::FAILED->value);
    }

    public function test_message_scope_by_user(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        Message::factory()->count(3)->create(['user_id' => $user1->id]);
        Message::factory()->count(2)->create(['user_id' => $user2->id]);

        $user1Messages = Message::byUser($user1->id)->get();
        $this->assertCount(3, $user1Messages);
    }

    public function test_message_scope_by_status(): void
    {
        $user = User::factory()->create();

        Message::factory()->count(2)->create([
            'user_id' => $user->id,
            'status' => MessageStatus::SENT->value,
        ]);
        Message::factory()->count(3)->create([
            'user_id' => $user->id,
            'status' => MessageStatus::FAILED->value,
        ]);

        $sentMessages = Message::byStatus('sent')->get();
        $this->assertCount(2, $sentMessages);
    }
}
