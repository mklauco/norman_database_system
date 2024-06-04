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
        Schema::create('suspect_list_exchanges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('source_id')->constrained()->nullable()->default(null)->references('id')->on('suspect_list_exchange_sources');
            $table->foreignId('substance_id')->nullable()->default(null)->constrained()->references('id')->on('susdat_substances');
            $table->text('name')->nullable()->default(null);
            $table->text('name_iupac')->nullable()->default(null);
            $table->text('cas_number')->nullable()->default(null);
            $table->text('smiles')->nullable()->default(null);
            $table->text('stdinchi')->nullable()->default(null);
            $table->text('stdinchikey')->nullable()->default(null);
            $table->text('pubchem_cid')->nullable()->default(null);
            $table->text('chemspider_id')->nullable()->default(null);
            $table->text('dtxid')->nullable()->default(null);        
            $table->text('molecular_formula')->nullable()->default(null);
            $table->float('mass_iso')->nullable()->default(null);
            $table->float('molecular_weight')->nullable()->default(null);
            $table->json('metadata_general')->nullable()->default(null);
            $table->foreignId('added_by')->constrained()->nullable()->default(null)->references('id')->on('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suspect_list_exchanges');
    }
};
