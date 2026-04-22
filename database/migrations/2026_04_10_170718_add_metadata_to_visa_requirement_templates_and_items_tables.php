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
        Schema::table('visa_requirement_templates', function (Blueprint $table) {
            $table->string('source_url')->nullable()->after('description');
            $table->date('source_checked_at')->nullable()->after('source_url');
            $table->string('processing_time_summary')->nullable()->after('source_checked_at');
            $table->string('fee_summary')->nullable()->after('processing_time_summary');
            $table->string('stay_summary')->nullable()->after('fee_summary');
        });

        Schema::table('visa_requirement_items', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('visa_requirement_template_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_requirement_items', function (Blueprint $table) {
            $table->dropColumn('category');
        });

        Schema::table('visa_requirement_templates', function (Blueprint $table) {
            $table->dropColumn([
                'source_url',
                'source_checked_at',
                'processing_time_summary',
                'fee_summary',
                'stay_summary',
            ]);
        });
    }
};
