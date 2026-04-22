<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
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
            'uploaded_by_id' => User::factory(),
            'disk' => 'local',
            'path' => 'attachments/test-file.pdf',
            'original_name' => 'test-file.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
        ];
    }
}
