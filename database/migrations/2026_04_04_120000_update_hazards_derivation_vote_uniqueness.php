<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('DROP INDEX IF EXISTS hazards_derivation_selections_unique_current_vote');

        DB::statement("
            CREATE UNIQUE INDEX hazards_derivation_selections_unique_current_vote_per_user
            ON hazards_derivation_selections (susdat_substance_id, bucket, user_id)
            WHERE is_current = TRUE AND kind = 'vote'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS hazards_derivation_selections_unique_current_vote_per_user');

        DB::statement("
            CREATE UNIQUE INDEX hazards_derivation_selections_unique_current_vote
            ON hazards_derivation_selections (susdat_substance_id, bucket)
            WHERE is_current = TRUE AND kind = 'vote'
        ");
    }
};
