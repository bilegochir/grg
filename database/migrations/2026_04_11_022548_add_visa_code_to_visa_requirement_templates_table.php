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
            $table->string('visa_code', 50)->nullable()->after('visa_type')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_requirement_templates', function (Blueprint $table) {
            $table->dropIndex(['visa_code']);
            $table->dropColumn('visa_code');
        });
    }
};
