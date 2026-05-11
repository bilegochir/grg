<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visa_case_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visa_case_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sent_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sender_type')->default('staff');
            $table->string('direction')->default('outbound');
            $table->string('channel')->default('email');
            $table->string('notification_event')->nullable()->index();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->json('metadata')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visa_case_messages');
    }
};
