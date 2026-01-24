<?php

namespace Database\Factories;

use App\Models\MessageTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MessageTemplateFactory extends Factory
{
    protected $model = MessageTemplate::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(3, true),
            'content' => 'Hello {nom}, ' . fake()->sentence(),
            'category' => fake()->randomElement(array_keys(MessageTemplate::CATEGORIES)),
            'variables' => ['{nom}'],
            'is_public' => false,
            'usage_count' => 0,
        ];
    }

    public function public(): static
    {
        return $this->state(fn (array $attributes) => ['is_public' => true]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => ['is_public' => false]);
    }

    public function marketing(): static
    {
        return $this->state(fn (array $attributes) => ['category' => 'marketing']);
    }
}
