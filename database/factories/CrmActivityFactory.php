<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Client;
use App\Models\CrmActivity;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CrmActivity>
 */
class CrmActivityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'agency_id' => Agency::factory(),
            'actor_id' => null,
            'notable_id' => Client::factory(),
            'notable_type' => Client::class,
            'event_type' => 'client_updated',
            'title' => 'Client updated',
            'description' => $this->faker->sentence(),
            'properties' => [
                'changes' => [
                    'Status from Lead to Active',
                ],
            ],
        ];
    }
}
