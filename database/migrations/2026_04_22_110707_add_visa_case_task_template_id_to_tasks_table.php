<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('visa_case_task_template_id')
                ->nullable()
                ->after('visa_case_id')
                ->constrained('visa_case_task_templates')
                ->nullOnDelete();

            $table->unique(['visa_case_id', 'visa_case_task_template_id'], 'tasks_visa_case_template_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropUnique('tasks_visa_case_template_unique');
            $table->dropConstrainedForeignId('visa_case_task_template_id');
        });
    }
};
