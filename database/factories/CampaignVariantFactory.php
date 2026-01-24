<?php

namespace Database\Factories;

use App\Models\CampaignVariant;
use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignVariantFactory extends Factory
{
    protected $model = CampaignVariant::class;

    public function definition(): array
    {
        return [
            'campaign_id' => Campaign::factory(),
            'variant_name' => fake()->randomElement(['A', 'B', 'C', 'D']),
            'message' => fake()->sentence(),
            'percentage' => 50,
            'messages_sent' => 0,
            'delivered' => 0,
            'failed' => 0,
        ];
    }
}
