<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('target_countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('visa_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('target_country_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->string('slug');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('submission_sla_days')->nullable();
            $table->unsignedInteger('decision_sla_days')->nullable();
            $table->unsignedInteger('validity_months')->nullable();
            $table->unsignedInteger('stay_duration_days')->nullable();
            $table->string('entry_type')->nullable();
            $table->string('service_scope')->nullable();
            $table->boolean('priority_support')->default(true);
            $table->boolean('dependants_allowed')->default(false);
            $table->boolean('biometrics_required')->default(false);
            $table->boolean('interview_required')->default(false);
            $table->boolean('medical_required')->default(false);
            $table->boolean('police_clearance_required')->default(false);
            $table->boolean('financial_proof_required')->default(false);
            $table->text('checklist_intro')->nullable();
            $table->text('portal_guidance')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['target_country_id', 'slug']);
        });

        Schema::create('visa_workflow_stages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->unsignedInteger('position');
            $table->string('color')->default('blue');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_closed')->default(false);
            $table->timestamps();
            $table->unique(['visa_type_id', 'slug']);
        });

        Schema::create('visa_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('target_country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('current_stage_id')->nullable()->constrained('visa_workflow_stages')->nullOnDelete();
            $table->string('priority')->default('normal');
            $table->string('reference_code')->unique();
            $table->date('expected_submission_at')->nullable();
            $table->date('expected_decision_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('visa_case_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->boolean('is_client_visible')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('visa_case_stage_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_stage_id')->nullable()->constrained('visa_workflow_stages')->nullOnDelete();
            $table->foreignId('to_stage_id')->constrained('visa_workflow_stages')->cascadeOnDelete();
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->index(['visa_case_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_stage_histories');
        Schema::dropIfExists('visa_case_notes');
        Schema::dropIfExists('visa_cases');
        Schema::dropIfExists('visa_workflow_stages');
        Schema::dropIfExists('visa_types');
        Schema::dropIfExists('target_countries');
    }
};
