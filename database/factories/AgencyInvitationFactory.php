<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\AgencyInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @extends Factory<AgencyInvitation>
 */
class AgencyInvitationFactory extends Factory
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
            'invited_by_id' => User::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'token' => hash('sha256', Str::random(64)),
            'expires_at' => Carbon::now()->addDays(7),
            'accepted_at' => null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(fn (): array => [
            'accepted_at' => Carbon::now(),
            'token' => null,
        ]);
    }
}
