<?php

namespace Database\Factories;

use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $label = fake()->words(2, true);

        return [
            'name' => Str::slug($label, '.'),
            'group_name' => 'admin',
            'label' => Str::title($label),
        ];
    }
}
