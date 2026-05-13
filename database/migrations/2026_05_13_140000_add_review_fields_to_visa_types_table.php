<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_types', function (Blueprint $table) {
            $table->date('official_last_reviewed_at')->nullable()->after('official_requirements');
            $table->date('policy_effective_date')->nullable()->after('official_last_reviewed_at');
            $table->text('official_change_notes')->nullable()->after('policy_effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('visa_types', function (Blueprint $table) {
            $table->dropColumn([
                'official_last_reviewed_at',
                'policy_effective_date',
                'official_change_notes',
            ]);
        });
    }
};
