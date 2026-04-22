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
        Schema::table('clients', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable()->after('phone');
            $table->string('passport_number')->nullable()->after('date_of_birth');
            $table->date('passport_expiry_date')->nullable()->after('passport_number');
            $table->string('marital_status')->nullable()->after('passport_expiry_date');
            $table->string('occupation')->nullable()->after('marital_status');
            $table->text('current_address')->nullable()->after('occupation');
            $table->json('family_members')->nullable()->after('current_address');
            $table->json('education_history')->nullable()->after('family_members');
            $table->json('work_experiences')->nullable()->after('education_history');

            $table->index(['agency_id', 'passport_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropIndex(['agency_id', 'passport_number']);
            $table->dropColumn([
                'date_of_birth',
                'passport_number',
                'passport_expiry_date',
                'marital_status',
                'occupation',
                'current_address',
                'family_members',
                'education_history',
                'work_experiences',
            ]);
        });
    }
};
