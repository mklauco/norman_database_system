<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('susdat_substances', function (Blueprint $table) {
            // Add canonical reference system columns
            $table->foreignId('canonical_id')
                ->nullable()
                ->references('id')
                ->on('susdat_substances')
                ->onUpdate('cascade')
                ->onDelete('restrict');

            // Status tracking
            $table->enum('status', ['active', 'deprecated', 'merged'])
                ->default('active');

            // Merge tracking fields
            $table->timestamp('merged_at')->nullable();
            $table->foreignId('merged_by')
                ->nullable()
                ->references('id')
                ->on('users')
                ->onUpdate('cascade')
                ->onDelete('set null');

            $table->text('merge_reason')->nullable();

            // Add indexes for performance
            $table->index('canonical_id', 'idx_susdat_substances_canonical_id');
            $table->index('status', 'idx_susdat_substances_status');
            $table->index(['status', 'canonical_id'], 'idx_susdat_substances_status_canonical');
        });

        // Add database constraints for data integrity
        $this->addDatabaseConstraints();

        // Set all existing records as active (they are canonical by default)
        DB::table('susdat_substances')
            ->whereNull('deleted_at')
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('susdat_substances', function (Blueprint $table) {
            // Drop foreign key constraints first
            $table->dropForeign(['canonical_id']);
            $table->dropForeign(['merged_by']);

            // Drop indexes
            $table->dropIndex('idx_susdat_substances_canonical_id');
            $table->dropIndex('idx_susdat_substances_status');
            $table->dropIndex('idx_susdat_substances_status_canonical');

            // Drop columns
            $table->dropColumn([
                'canonical_id',
                'status',
                'merged_at',
                'merged_by',
                'merge_reason',
            ]);
        });

        // Drop custom constraints
        $this->dropDatabaseConstraints();
    }

    /**
     * Add custom database constraints for canonical system integrity
     * Note: PostgreSQL doesn't support subqueries in CHECK constraints,
     * so we use simpler constraints and rely on application logic for complex validation
     */
    private function addDatabaseConstraints(): void
    {
        // Constraint: active substances cannot have canonical_id (they ARE canonical)
        DB::statement('
            ALTER TABLE susdat_substances 
            ADD CONSTRAINT chk_active_no_canonical 
            CHECK (
                (status = \'active\' AND canonical_id IS NULL) OR 
                (status != \'active\' AND canonical_id IS NOT NULL)
            )
        ');

        // Constraint: merged substances must have merge tracking data
        DB::statement('
            ALTER TABLE susdat_substances 
            ADD CONSTRAINT chk_merged_tracking 
            CHECK (
                (status != \'merged\') OR 
                (status = \'merged\' AND merged_at IS NOT NULL AND canonical_id IS NOT NULL)
            )
        ');

        // NOTE: Unique constraint for active codes will be added later
        // after duplicate resolution is complete via separate migration
        // This allows existing duplicates to remain during transition period

        // NOTE: Validation that canonical_id references active substances
        // will be handled at application level in the Substance model
    }

    /**
     * Drop custom database constraints
     */
    private function dropDatabaseConstraints(): void
    {
        DB::statement('ALTER TABLE susdat_substances DROP CONSTRAINT IF EXISTS chk_canonical_references_active');
        DB::statement('ALTER TABLE susdat_substances DROP CONSTRAINT IF EXISTS chk_active_no_canonical');
        DB::statement('ALTER TABLE susdat_substances DROP CONSTRAINT IF EXISTS chk_merged_tracking');
        // NOTE: Unique constraint dropped in separate migration
        // DB::statement('DROP INDEX IF EXISTS idx_susdat_substances_unique_active_code');
    }
};
