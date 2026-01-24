<?php

namespace Database\Factories;

use App\Enums\MessageStatus;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageFactory extends Factory
{
    protected $model = Message::class;

    public function definition(): array
    {
        $status = fake()->randomElement(MessageStatus::cases());

        return [
            'user_id' => User::factory(),
            'campaign_id' => null,
            'contact_id' => null,
            'recipient_name' => fake()->optional()->name(),
            'recipient_phone' => '+241' . fake()->randomElement(['77', '74', '66', '62']) . fake()->numerify('#######'),
            'content' => fake()->sentence(),
            'type' => 'sms',
            'status' => $status->value,
            'provider' => fake()->randomElement(['airtel', 'moov']),
            'cost' => fake()->numberBetween(20, 100),
            'error_message' => $status === MessageStatus::FAILED ? fake()->sentence() : null,
            'sent_at' => $status === MessageStatus::SENT ? now() : null,
            'delivered_at' => $status === MessageStatus::DELIVERED ? now() : null,
        ];
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::SENT->value,
            'sent_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => MessageStatus::FAILED->value,
            'error_message' => fake()->sentence(),
        ]);
    }

    public function airtel(): static
    {
        return $this->state(fn (array $attributes) => ['provider' => 'airtel']);
    }

    public function moov(): static
    {
        return $this->state(fn (array $attributes) => ['provider' => 'moov']);
    }
}
