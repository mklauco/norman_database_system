<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('occurred_at');
            $table->string('channel', 8);
            $table->string('module', 64)->nullable();
            $table->string('action', 32);
            $table->string('route_name', 191)->nullable();
            $table->string('method', 8);
            $table->string('path', 500);
            $table->smallInteger('status')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->unsignedBigInteger('token_id')->nullable();
            $table->jsonb('meta')->nullable();

            $table->index(['user_id', 'occurred_at']);
            $table->index('occurred_at');
            $table->index(['module', 'occurred_at']);
            $table->index(['channel', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_activity_logs');
    }
};
