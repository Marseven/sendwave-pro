<?php

namespace Database\Factories;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'status' => CampaignStatus::DRAFT->value,
            'message_content' => fake()->sentence(),
            'messages_sent' => 0,
            'recipients_count' => 0,
            'sms_count' => 0,
            'delivery_rate' => 0,
            'ctr' => 0,
            'cost' => 0,
            'sms_provider' => fake()->randomElement(['airtel', 'moov']),
            'scheduled_at' => null,
            'sent_at' => null,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => ['status' => CampaignStatus::DRAFT->value]);
    }

    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::SCHEDULED->value,
            'scheduled_at' => now()->addDay(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CampaignStatus::COMPLETED->value,
            'messages_sent' => fake()->numberBetween(10, 1000),
            'sent_at' => now(),
        ]);
    }
}
