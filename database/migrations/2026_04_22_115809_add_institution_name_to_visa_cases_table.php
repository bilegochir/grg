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
        Schema::table('visa_cases', function (Blueprint $table) {
            $table->string('institution_name')->nullable()->after('destination_country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_cases', function (Blueprint $table) {
            $table->dropColumn('institution_name');
        });
    }
};
