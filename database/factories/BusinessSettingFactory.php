<?php

namespace Database\Factories;

use App\Models\BusinessSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessSetting>
 */
class BusinessSettingFactory extends Factory
{
    protected $model = BusinessSetting::class;

    public function definition(): array
    {
        return [
            'business_name' => 'Agency',
            'contact_email' => fake()->companyEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'contact_address' => fake()->address(),
            'default_locale' => 'en',
            'sms_provider' => 'log',
            'sms_sender' => 'Agency',
            'multi_branch_enabled' => false,
            'multi_branch_ready' => true,
        ];
    }
}
