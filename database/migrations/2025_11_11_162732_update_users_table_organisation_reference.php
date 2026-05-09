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
        Schema::table('users', function (Blueprint $table) {
            // Drop the old foreign key constraint on organisation_id
            $table->dropForeign(['organisation_id']);

            // Drop the text fields for organisation and country
            // $table->dropColumn(['organisation', 'organisation_other', 'country']);
        });

        Schema::table('users', function (Blueprint $table) {
            // Add new foreign key constraint referencing list_data_source_organisations
            $table->foreign('organisation_id')
                ->references('id')
                ->on('list_data_source_organisations')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Schema::table('users', function (Blueprint $table) {
        //     // Drop the new foreign key constraint
        //     $table->dropForeign(['organisation_id']);
        // });

        // Schema::table('users', function (Blueprint $table) {
        //     // Restore the old text fields
        //     $table->string('organisation', 255)->nullable()->default(null);
        //     $table->string('organisation_other', 255)->nullable()->default(null);
        //     $table->string('country', 50)->nullable();

        //     // Restore the old foreign key constraint to organisations table
        //     $table->foreign('organisation_id')
        //           ->references('id')
        //           ->on('organisations')
        //           ->onDelete('set null');
        // });
    }
};
