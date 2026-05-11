<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseDocumentVersion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VisaCaseDocumentVersion>
 */
class VisaCaseDocumentVersionFactory extends Factory
{
    protected $model = VisaCaseDocumentVersion::class;

    public function definition(): array
    {
        return [
            'visa_case_document_id' => VisaCaseDocument::factory(),
            'uploaded_by_user_id' => User::factory(),
            'disk' => 'local',
            'path' => 'visa-cases/demo/sample.pdf',
            'original_name' => 'sample.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'version_number' => 1,
        ];
    }
}
