<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applicants', function (Blueprint $table): void {
            $table->timestamp('portal_last_seen_at')->nullable()->after('notification_preferences');
        });
    }

    public function down(): void
    {
        Schema::table('applicants', function (Blueprint $table): void {
            $table->dropColumn('portal_last_seen_at');
        });
    }
};
