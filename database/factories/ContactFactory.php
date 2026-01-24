<?php

namespace Database\Factories;

use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactFactory extends Factory
{
    protected $model = Contact::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => '+241' . fake()->randomElement(['77', '74', '66', '62']) . fake()->numerify('#######'),
            'status' => fake()->randomElement(['active', 'inactive']),
            'group' => fake()->optional()->word(),
            'custom_fields' => [],
            'last_connection' => fake()->optional()->dateTimeThisMonth(),
        ];
    }

    public function active(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'active']);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'inactive']);
    }
}
