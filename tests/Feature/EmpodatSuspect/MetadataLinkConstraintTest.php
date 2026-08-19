<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Services\EmpodatSuspect\SeedRowLimiter;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exercises the DB-level constraints added by
 * `2026_08_19_115149_add_file_id_and_main_fk_to_empodat_suspect_metadata_table`:
 *
 * - `fk_esmd_main`: composite FK (id, is_numeric_concentration) ->
 *   empodat_suspect_main (id, is_numeric_concentration), ON DELETE NO ACTION.
 *   Being composite, it doubles as a partition guard: a metadata row must
 *   land in the same partition as its main row.
 * - `fk_esmd_file`: file_id -> files(id).
 *
 * Also covers `SeedRowLimiter`'s hard production guard, since a leaked
 * `EMPODAT_SUSPECT_SEED_ROW_LIMIT` truncating a production import would be
 * exactly the kind of silent data-integrity failure this test class exists
 * to catch.
 */
class MetadataLinkConstraintTest extends TestCase
{
    use RefreshDatabase;

    private function insertFile(int $id, string $name = 'Test File'): void
    {
        DB::table('files')->insert(['id' => $id, 'name' => $name]);
    }

    /**
     * Both FK columns default to null: `empodat_suspect_main` has no
     * required parents when left unset, matching production reality where
     * ~48% of main rows carry no metadata and nothing here needs a valid
     * substance/station to exercise the metadata link constraints.
     */
    private function insertMain(int $id, bool $isNumericConcentration = true, ?int $fileId = null): void
    {
        DB::table('empodat_suspect_main')->insert([
            'id' => $id,
            'is_numeric_concentration' => $isNumericConcentration,
            'file_id' => $fileId,
        ]);
    }

    private function insertMetadata(int $id, bool $isNumericConcentration = true, ?int $fileId = null): void
    {
        DB::table('empodat_suspect_metadata')->insert([
            'id' => $id,
            'is_numeric_concentration' => $isNumericConcentration,
            'file_id' => $fileId,
        ]);
    }

    public function test_orphan_metadata_row_is_rejected(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');

        // No matching empodat_suspect_main row exists for id 1 at all.
        $this->insertMetadata(1, true);
    }

    public function test_metadata_row_with_mismatched_partition_is_rejected(): void
    {
        $this->insertMain(1, true);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');

        // Same id, but the wrong partition flag: the composite FK must
        // reject this even though a main row with id 1 does exist.
        $this->insertMetadata(1, false);
    }

    public function test_deleting_main_row_with_metadata_is_blocked(): void
    {
        $this->insertMain(1, true);
        $this->insertMetadata(1, true);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');

        // ON DELETE NO ACTION must raise, not cascade.
        DB::table('empodat_suspect_main')
            ->where('id', 1)
            ->where('is_numeric_concentration', true)
            ->delete();
    }

    public function test_deleting_metadata_before_main_succeeds(): void
    {
        $this->insertMain(1, true);
        $this->insertMetadata(1, true);

        DB::table('empodat_suspect_metadata')
            ->where('id', 1)
            ->where('is_numeric_concentration', true)
            ->delete();

        DB::table('empodat_suspect_main')
            ->where('id', 1)
            ->where('is_numeric_concentration', true)
            ->delete();

        $this->assertSame(0, DB::table('empodat_suspect_metadata')->where('id', 1)->count());
        $this->assertSame(0, DB::table('empodat_suspect_main')->where('id', 1)->count());
    }

    public function test_main_row_without_metadata_is_legal(): void
    {
        // The FK is child (metadata) -> parent (main): a main row is never
        // required to have metadata. Roughly 48% of production main rows
        // have none, and 8 of the 15 source files produce none at all.
        $this->insertMain(1, true);

        $mainExists = DB::table('empodat_suspect_main')
            ->where('id', 1)
            ->where('is_numeric_concentration', true)
            ->exists();

        $this->assertTrue($mainExists);
        $this->assertSame(0, DB::table('empodat_suspect_metadata')->where('id', 1)->count());
    }

    public function test_valid_paired_insert_succeeds(): void
    {
        $this->insertFile(1);
        $this->insertMain(1, true, 1);
        $this->insertMetadata(1, true, 1);

        $metadata = DB::table('empodat_suspect_metadata')
            ->where('id', 1)
            ->where('is_numeric_concentration', true)
            ->first();

        $this->assertNotNull($metadata);
        $this->assertSame(1, (int) $metadata->file_id);
    }

    public function test_metadata_file_foreign_key_rejects_nonexistent_file_id(): void
    {
        $this->insertMain(1, true);

        $this->expectException(QueryException::class);
        $this->expectExceptionCode('23503');

        // No files row 999999 exists in this freshly refreshed test database.
        $this->insertMetadata(1, true, 999999);
    }

    public function test_seed_row_limiter_is_inert_in_production(): void
    {
        config(['empodat_suspect.seed_row_limit' => 10000]);
        app()->detectEnvironment(fn (): string => 'production');

        $limiter = new SeedRowLimiter;

        $this->assertFalse($limiter->isActive());
        $this->assertNull($limiter->limit());
        $this->assertFalse($limiter->reached(999999));
    }

    public function test_seed_row_limiter_is_active_outside_production(): void
    {
        config(['empodat_suspect.seed_row_limit' => 10000]);
        app()->detectEnvironment(fn (): string => 'testing');

        $limiter = new SeedRowLimiter;

        $this->assertTrue($limiter->isActive());
        $this->assertSame(10000, $limiter->limit());
        $this->assertTrue($limiter->reached(999999));
        $this->assertFalse($limiter->reached(1));
    }
}
