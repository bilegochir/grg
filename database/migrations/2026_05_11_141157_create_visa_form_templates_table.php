<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_form_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_type_id')->nullable()->constrained('visa_types')->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('file_path');         // Stored PDF template
            $table->string('original_filename');
            // JSON map of PDF field names → CRM data paths
            // e.g. {"family_name": "applicant.last_name", "dob": "applicant.date_of_birth"}
            $table->json('field_mapping')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_form_templates');
    }
};
