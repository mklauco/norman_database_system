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
            $table->string('username', 32)->unique()->nullable()->default(null);
            $table->string('salutation', 32)->nullable()->default(null);
            $table->string('organisation', 255)->nullable()->default(null);

            $table->foreignId('organisation_id')->nullable()->default(null)->references('id')->on('organisations');

            $table->string('organisation_other', 255)->nullable()->default(null);

            $table->string('country', 50)->nullable();
            $table->foreignId('country_id')->nullable()->default(null)->references('id')->on('list_countries');

            $table->boolean('active')->default(1);
        });
    }

    

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
            // Drop foreign key constraints
            $table->dropForeign(['organisation_id']);
            $table->dropForeign(['country_id']);
            // Drop columns
            $table->dropColumn('username');
            $table->dropColumn('salutation');
            $table->dropColumn('organisation');
            $table->dropColumn('organisation_id');
            $table->dropColumn('organisation_other');
            $table->dropColumn('country');
            $table->dropColumn('country_id');
            $table->dropColumn('active');
        });
    }
};
