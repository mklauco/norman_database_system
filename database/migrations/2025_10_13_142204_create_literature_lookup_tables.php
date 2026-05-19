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
        // List of life stages (e.g., adult, juvenile, hatchling, imago, larvae)
        if (! Schema::hasTable('list_life_stages')) {
            Schema::create('list_life_stages', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of habitat types (EUNIS Habitat type)
        if (! Schema::hasTable('list_habitat_types')) {
            Schema::create('list_habitat_types', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of concentration units
        if (! Schema::hasTable('list_concentration_units')) {
            Schema::create('list_concentration_units', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of biota sexes (e.g., male, female, unknown)
        if (! Schema::hasTable('list_biota_sexs')) {
            Schema::create('list_biota_sexs', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of tissues (e.g., liver, muscle, blood, brain)
        if (! Schema::hasTable('list_tissues')) {
            Schema::create('list_tissues', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of use categories (chemical use categories)
        if (! Schema::hasTable('list_use_categories')) {
            Schema::create('list_use_categories', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of species with phylogenetic information
        if (! Schema::hasTable('list_species')) {
            Schema::create('list_species', function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable()->default(null);
                $table->string('name_latin')->nullable()->default(null);
                $table->string('kingdom')->nullable()->default(null);
                $table->string('phylum')->nullable()->default(null);
                $table->string('order')->nullable()->default(null);
                $table->string('class')->nullable()->default(null);
                $table->string('genus')->nullable()->default(null);
                $table->timestamps();
            });
        }

        // List of common names for species
        if (! Schema::hasTable('list_common_names')) {
            Schema::create('list_common_names', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }

        // List of type of numeric quantities (e.g., Arithmetic mean, Average, Mean, Median, etc.)
        if (! Schema::hasTable('list_type_of_numeric_quantities')) {
            Schema::create('list_type_of_numeric_quantities', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('list_type_of_numeric_quantities');
        Schema::dropIfExists('list_use_categories');
        Schema::dropIfExists('list_tissues');
        Schema::dropIfExists('list_biota_sexs');
        Schema::dropIfExists('list_common_names');
        Schema::dropIfExists('list_species');
        Schema::dropIfExists('list_concentration_units');
        Schema::dropIfExists('list_habitat_types');
        Schema::dropIfExists('list_life_stages');
    }
};
