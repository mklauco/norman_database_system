<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empodat_contacts', function (Blueprint $table): void {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('family_name')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('email');
            $table->index('user_id');
        });

        Schema::create('empodat_data_source_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('data_source_id')->constrained('empodat_data_sources')->cascadeOnDelete();
            $table->smallInteger('position');
            $table->foreignId('organisation_id')->nullable()->constrained('list_data_source_organisations')->restrictOnDelete();
            $table->foreignId('laboratory1_id')->nullable()->constrained('list_data_source_laboratories')->restrictOnDelete();
            $table->foreignId('laboratory2_id')->nullable()->constrained('list_data_source_laboratories')->restrictOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('empodat_contacts')->nullOnDelete();
            $table->timestamps();

            $table->unique(['data_source_id', 'position']);
            $table->index('organisation_id');
            $table->index('laboratory1_id');
            $table->index('laboratory2_id');
            $table->index('contact_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empodat_data_source_groups');
        Schema::dropIfExists('empodat_contacts');
    }
};
