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
        Schema::table('visa_case_requirements', function (Blueprint $table) {
            $table->string('category', 100)->nullable()->after('visa_requirement_item_id');
            $table->string('status', 50)->default('pending')->after('is_required')->index();
            $table->date('due_at')->nullable()->after('status')->index();
            $table->timestamp('requested_at')->nullable()->after('due_at');
            $table->timestamp('received_at')->nullable()->after('requested_at');
            $table->timestamp('reviewed_at')->nullable()->after('received_at');
            $table->text('review_notes')->nullable()->after('reviewed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('visa_case_requirements', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['due_at']);
            $table->dropColumn([
                'category',
                'status',
                'due_at',
                'requested_at',
                'received_at',
                'reviewed_at',
                'review_notes',
            ]);
        });
    }
};
