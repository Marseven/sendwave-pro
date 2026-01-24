<?php

namespace Database\Factories;

use App\Models\ContactGroup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ContactGroupFactory extends Factory
{
    protected $model = ContactGroup::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
        ];
    }
}
