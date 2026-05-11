<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_cases', function (Blueprint $table) {
            $table->foreignId('visa_case_group_id')->nullable()->after('branch_id')->constrained('visa_case_groups')->nullOnDelete();
            $table->boolean('is_group_primary')->default(false)->after('visa_case_group_id');
        });
    }

    public function down(): void
    {
        Schema::table('visa_cases', function (Blueprint $table) {
            $table->dropConstrainedForeignId('visa_case_group_id');
            $table->dropColumn('is_group_primary');
        });
    }
};
