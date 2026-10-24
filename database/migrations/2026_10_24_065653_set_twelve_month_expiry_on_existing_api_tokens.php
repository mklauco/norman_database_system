<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tokens without an expiry get one 12 months after they were created
     * (or 12 months from now when the creation date is unknown).
     */
    public function up(): void
    {
        DB::table('personal_access_tokens')
            ->whereNull('expires_at')
            ->update(['expires_at' => DB::raw("COALESCE(created_at, NOW()) + INTERVAL '12 months'")]);
    }

    /**
     * Not reversible: tokens that always had an expiry can not be told apart from these.
     */
    public function down(): void
    {
        //
    }
};
