<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->string('pathway_interest')->nullable()->after('country_of_citizenship');
            $table->string('current_country')->nullable()->after('pathway_interest');
            $table->string('relationship_status')->nullable()->after('current_country');
            $table->string('english_test_status')->nullable()->after('relationship_status');
            $table->string('highest_education')->nullable()->after('english_test_status');
            $table->unsignedSmallInteger('years_of_experience')->nullable()->after('highest_education');
            $table->boolean('has_refusal_history')->default(false)->after('years_of_experience');
            $table->date('target_intake_date')->nullable()->after('has_refusal_history');
            $table->string('budget_range')->nullable()->after('target_intake_date');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->dropColumn([
                'pathway_interest',
                'current_country',
                'relationship_status',
                'english_test_status',
                'highest_education',
                'years_of_experience',
                'has_refusal_history',
                'target_intake_date',
                'budget_range',
            ]);
        });
    }
};
