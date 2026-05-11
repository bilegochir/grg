<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_settings', function (Blueprint $table) {
            $table->id();
            $table->string('business_name')->default('Agency');
            $table->string('logo_path')->nullable();
            $table->string('contact_email')->nullable();
            $table->string('contact_phone')->nullable();
            $table->string('contact_address')->nullable();
            $table->string('default_locale')->default('en');
            $table->string('sms_provider')->default('log');
            $table->string('sms_sender')->nullable();
            $table->boolean('multi_branch_enabled')->default(false);
            $table->boolean('multi_branch_ready')->default(true);
            $table->timestamps();
        });

        Schema::create('communication_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('key');
            $table->string('channel');
            $table->string('locale')->default('en');
            $table->string('subject')->nullable();
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['key', 'channel', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('communication_templates');
        Schema::dropIfExists('business_settings');
    }
};
