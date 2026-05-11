<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('visa_types', function (Blueprint $table) {
            $table->string('official_subclass', 50)->nullable()->after('code');
            $table->string('official_reference_url')->nullable()->after('notes');
            $table->text('official_summary')->nullable()->after('official_reference_url');
            $table->json('official_requirements')->nullable()->after('official_summary');
        });
    }

    public function down(): void
    {
        Schema::table('visa_types', function (Blueprint $table) {
            $table->dropColumn([
                'official_subclass',
                'official_reference_url',
                'official_summary',
                'official_requirements',
            ]);
        });
    }
};
