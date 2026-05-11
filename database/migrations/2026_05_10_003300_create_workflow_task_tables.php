<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_task_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visa_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('visa_workflow_stage_id')->nullable()->constrained('visa_workflow_stages')->nullOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('position')->default(1);
            $table->unsignedInteger('due_days')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_client_visible')->default(false);
            $table->timestamps();
            $table->unique(['visa_type_id', 'slug']);
        });

        Schema::create('visa_case_tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visa_case_id')->constrained('visa_cases')->cascadeOnDelete();
            $table->foreignId('visa_task_template_id')->nullable()->constrained('visa_task_templates')->nullOnDelete();
            $table->foreignId('visa_workflow_stage_id')->nullable()->constrained('visa_workflow_stages')->nullOnDelete();
            $table->foreignId('assigned_to_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->unsignedInteger('position')->default(1);
            $table->date('due_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_client_visible')->default(false);
            $table->timestamps();
            $table->index(['visa_case_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_tasks');
        Schema::dropIfExists('visa_task_templates');
    }
};
