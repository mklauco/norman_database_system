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

        Schema::create('susdat_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->default(null);
            $table->timestamps();
        });

        
        Schema::create('susdat_substances', function (Blueprint $table) {
            $table->id();
            $table->string('code')->nullable()->default(null);
            $table->text('name')->nullable()->default(null);
            $table->text('name_dashboard')->nullable()->default(null);
            $table->text('name_chemspider')->nullable()->default(null);
            $table->text('name_iupac')->nullable()->default(null);
            $table->text('cas_number')->nullable()->default(null);
            $table->text('smiles')->nullable()->default(null);
            $table->text('smiles_dashboard')->nullable()->default(null);
            $table->text('stdinchi')->nullable()->default(null);
            $table->text('stdinchikey')->nullable()->default(null);
            $table->text('pubchem_cid')->nullable()->default(null);
            $table->text('chemspider_id')->nullable()->default(null);
            $table->text('dtxid')->nullable()->default(null);
            $table->text('molecular_formula')->nullable()->default(null);
            $table->float('mass_iso')->nullable()->default(null);
            $table->json('metadata_synonyms')->nullable()->default(null);
            $table->json('metadata_cas')->nullable()->default(null);
            $table->json('metadata_ms_ready')->nullable()->default(null);
            $table->json('metadata_general')->nullable()->default(null);
            $table->foreignId('added_by')->constrained()->nullable()->default(null)->references('id')->on('users');
            $table->timestamps();
        });

        Schema::create('susdat_category_substance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('substance_id')->constrained()->nullable()->default(null)->references('id')->on('susdat_substances');
            $table->foreignId('category_id')->constrained()->nullable()->default(null)->references('id')->on('susdat_categories');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('susdat_substances');
    }
};
