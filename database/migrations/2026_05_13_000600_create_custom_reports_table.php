<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('type'); // 'cases', 'finance', 'staff', 'leads', 'appointments'
            $table->json('filters')->nullable(); // date range, statuses, etc.
            $table->json('columns')->nullable(); // selected columns to display
            $table->json('group_by')->nullable(); // grouping dimensions
            $table->json('sort_by')->nullable(); // sorting configuration
            $table->boolean('is_favorite')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_reports');
    }
};
