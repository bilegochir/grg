<?php

namespace Database\Factories;

use App\Models\CommunicationTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CommunicationTemplate>
 */
class CommunicationTemplateFactory extends Factory
{
    protected $model = CommunicationTemplate::class;

    public function definition(): array
    {
        $name = fake()->randomElement([
            'Lead follow-up',
            'Document reminder',
            'Decision update',
        ]);

        return [
            'name' => $name,
            'key' => Str::slug($name),
            'channel' => fake()->randomElement(['email', 'sms']),
            'locale' => 'en',
            'subject' => fake()->sentence(),
            'body' => fake()->paragraph(),
            'is_active' => true,
        ];
    }
}
