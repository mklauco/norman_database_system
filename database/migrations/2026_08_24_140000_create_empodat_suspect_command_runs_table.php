<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Run history for the allowlisted `empodat-suspect:*` refresh commands
     * triggered from the super-admin command center (see
     * config/empodat_suspect_commands.php and
     * App\Jobs\EmpodatSuspect\RunEmpodatSuspectCommandJob).
     */
    public function up(): void
    {
        Schema::create('empodat_suspect_command_runs', function (Blueprint $table) {
            $table->id();
            $table->string('command_key');
            $table->jsonb('arguments')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('queued_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->integer('exit_code')->nullable();
            $table->string('status')->default('queued');
            $table->text('output')->nullable();
            $table->timestamps();

            $table->index('command_key');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empodat_suspect_command_runs');
    }
};
