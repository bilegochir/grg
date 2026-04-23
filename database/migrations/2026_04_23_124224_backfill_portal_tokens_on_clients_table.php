<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('clients')
            ->select('id')
            ->whereNull('portal_token')
            ->orderBy('id')
            ->get()
            ->each(function (object $client): void {
                DB::table('clients')
                    ->where('id', $client->id)
                    ->update(['portal_token' => (string) Str::uuid()]);
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('clients')->update(['portal_token' => null]);
    }
};
