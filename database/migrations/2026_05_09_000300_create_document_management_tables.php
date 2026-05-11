<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->text('client_instructions')->nullable();
            $table->text('agent_guidance')->nullable();
            $table->string('sample_hint')->nullable();
            $table->json('accepted_file_types')->nullable();
            $table->unsignedSmallInteger('max_files')->default(1);
            $table->unsignedSmallInteger('max_file_size_mb')->default(20);
            $table->unsignedSmallInteger('due_days')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->boolean('is_required')->default(true);
            $table->boolean('tracks_expiry')->default(false);
            $table->timestamps();
            $table->unique(['visa_type_id', 'slug']);
        });

        Schema::create('visa_case_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('category')->nullable();
            $table->text('client_instructions')->nullable();
            $table->text('agent_guidance')->nullable();
            $table->string('sample_hint')->nullable();
            $table->json('accepted_file_types')->nullable();
            $table->unsignedSmallInteger('max_files')->default(1);
            $table->unsignedSmallInteger('max_file_size_mb')->default(20);
            $table->unsignedSmallInteger('due_days')->nullable();
            $table->boolean('is_repeatable')->default(false);
            $table->unsignedInteger('position')->default(1);
            $table->string('status')->default('pending')->index();
            $table->boolean('is_required')->default(true);
            $table->boolean('tracks_expiry')->default(false);
            $table->date('expiry_date')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
        });

        Schema::create('visa_case_document_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_case_document_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('disk');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedInteger('version_number')->default(1);
            $table->timestamps();
            $table->unique(['visa_case_document_id', 'version_number']);
        });

        Schema::table('visa_case_documents', function (Blueprint $table) {
            $table->foreignId('latest_version_id')->nullable()->after('rejection_reason')
                ->constrained('visa_case_document_versions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('visa_case_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('latest_version_id');
        });

        Schema::dropIfExists('visa_case_document_versions');
        Schema::dropIfExists('visa_case_documents');
        Schema::dropIfExists('document_templates');
    }
};
