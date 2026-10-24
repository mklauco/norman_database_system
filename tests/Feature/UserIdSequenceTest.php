<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserIdSequenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sequence_migration_moves_new_ids_past_imported_ones(): void
    {
        User::factory()->create(['id' => 5000]);

        $migration = require database_path('migrations/2026_10_24_065334_sync_users_id_sequence_with_existing_ids.php');
        $migration->up();

        $this->assertSame(5001, User::factory()->create()->id);
    }
}
