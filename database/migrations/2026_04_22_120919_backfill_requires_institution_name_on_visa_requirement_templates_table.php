<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('visa_requirement_templates')
            ->whereRaw('lower(visa_type) like ?', ['%student%'])
            ->orWhereRaw('lower(visa_type) like ?', ['%study%'])
            ->update(['requires_institution_name' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('visa_requirement_templates')
            ->whereRaw('lower(visa_type) like ?', ['%student%'])
            ->orWhereRaw('lower(visa_type) like ?', ['%study%'])
            ->update(['requires_institution_name' => false]);
    }
};
