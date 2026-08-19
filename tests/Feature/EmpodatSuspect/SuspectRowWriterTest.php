<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Services\EmpodatSuspect\SuspectRowWriter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

/**
 * Guards the correctness claim that `SuspectRowWriter` exists to make:
 * a metadata row always lands on the main row it was built from.
 *
 * The old seeder code inserted main rows with `RETURNING id` and zipped the
 * returned ids onto a metadata batch by array position. That is correct only
 * while PostgreSQL returns rows in input order — not a documented guarantee.
 * A foreign key cannot catch a violation of it: every metadata id would still
 * reference a real main row, just the wrong one, so the corruption would be
 * silent and total.
 *
 * The pairing assertions below are therefore the point of this file. Each
 * main row carries `concentration = $i` and its metadata row carries
 * `mz_score = $i`, so a single off-by-one anywhere in the writer shows up as
 * a row where the two disagree.
 */
class SuspectRowWriterTest extends TestCase
{
    use RefreshDatabase;

    private const FILE_ID = 90001;

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('files')->insert([
            'id' => self::FILE_ID,
            'name' => 'SuspectRowWriterTest fixture',
        ]);
    }

    public function test_metadata_rows_land_on_their_own_main_rows(): void
    {
        $this->writeBatch(50);

        $mismatched = DB::table('empodat_suspect_metadata AS md')
            ->join('empodat_suspect_main AS m', function ($join): void {
                $join->on('m.id', '=', 'md.id')
                    ->on('m.is_numeric_concentration', '=', 'md.is_numeric_concentration');
            })
            ->whereRaw('m.concentration IS DISTINCT FROM md.mz_score')
            ->count();

        $this->assertSame(0, $mismatched, 'A metadata row is attached to the wrong main row.');
        $this->assertSame(50, DB::table('empodat_suspect_main')->count());
        $this->assertSame(50, DB::table('empodat_suspect_metadata')->count());
    }

    /**
     * The metadata insert is chunked to stay under PostgreSQL's 65535
     * bound-parameter cap. Pairing must survive that split, so this batch is
     * deliberately larger than one chunk (17 columns => ~3529 rows per
     * statement) and lands unevenly across the boundary.
     */
    public function test_pairing_survives_the_bind_parameter_chunk_boundary(): void
    {
        $rowCount = 3600;

        $this->writeBatch($rowCount);

        $mismatched = DB::table('empodat_suspect_metadata AS md')
            ->join('empodat_suspect_main AS m', function ($join): void {
                $join->on('m.id', '=', 'md.id')
                    ->on('m.is_numeric_concentration', '=', 'md.is_numeric_concentration');
            })
            ->whereRaw('m.concentration IS DISTINCT FROM md.mz_score')
            ->count();

        $this->assertSame(0, $mismatched, 'Pairing broke across the metadata chunk boundary.');
        $this->assertSame($rowCount, DB::table('empodat_suspect_metadata')->count());
    }

    public function test_null_metadata_entries_write_a_main_row_with_no_metadata(): void
    {
        $writer = app(SuspectRowWriter::class);

        $mainRows = [];
        $metadataRows = [];
        foreach (range(0, 9) as $i) {
            $mainRows[] = $this->mainRow($i, true);
            // Every third row deliberately has no metadata at all.
            $metadataRows[] = $i % 3 === 0 ? null : $this->metadataRow($i);
        }

        $written = $writer->write($mainRows, $metadataRows, self::FILE_ID);

        $this->assertSame(10, $written);
        $this->assertSame(10, DB::table('empodat_suspect_main')->count());
        $this->assertSame(6, DB::table('empodat_suspect_metadata')->count());

        $orphanedMain = DB::table('empodat_suspect_main AS m')
            ->leftJoin('empodat_suspect_metadata AS md', function ($join): void {
                $join->on('m.id', '=', 'md.id')
                    ->on('m.is_numeric_concentration', '=', 'md.is_numeric_concentration');
            })
            ->whereNull('md.id')
            ->count();

        $this->assertSame(4, $orphanedMain, 'Main rows without metadata are legal and must persist.');
    }

    public function test_metadata_partition_flag_is_taken_from_the_main_row(): void
    {
        $writer = app(SuspectRowWriter::class);

        // Alternating partitions; the metadata payload never carries the flag,
        // so the writer must derive it from the paired main row.
        $mainRows = [];
        $metadataRows = [];
        foreach (range(0, 9) as $i) {
            $mainRows[] = $this->mainRow($i, $i % 2 === 0);
            $metadataRows[] = $this->metadataRow($i);
        }

        $writer->write($mainRows, $metadataRows, self::FILE_ID);

        $this->assertSame(5, DB::table('empodat_suspect_metadata')->where('is_numeric_concentration', true)->count());
        $this->assertSame(5, DB::table('empodat_suspect_metadata')->where('is_numeric_concentration', false)->count());
    }

    public function test_file_id_is_stamped_on_every_metadata_row(): void
    {
        $this->writeBatch(20);

        $this->assertSame(0, DB::table('empodat_suspect_metadata')->whereNull('file_id')->count());
        $this->assertSame(20, DB::table('empodat_suspect_metadata')->where('file_id', self::FILE_ID)->count());
    }

    public function test_allocated_ids_are_unique_and_do_not_collide_across_calls(): void
    {
        $this->writeBatch(30);
        $this->writeBatch(30);

        $this->assertSame(60, DB::table('empodat_suspect_main')->count());
        $this->assertSame(60, DB::table('empodat_suspect_main')->distinct()->count('id'));
    }

    public function test_mismatched_batch_lengths_throw(): void
    {
        $this->expectException(LogicException::class);

        app(SuspectRowWriter::class)->write(
            [$this->mainRow(0, true)],
            [$this->metadataRow(0), $this->metadataRow(1)],
            self::FILE_ID
        );
    }

    public function test_empty_batch_is_a_no_op(): void
    {
        $this->assertSame(0, app(SuspectRowWriter::class)->write([], [], self::FILE_ID));
        $this->assertSame(0, DB::table('empodat_suspect_main')->count());
    }

    /**
     * Write `$count` paired rows where main.concentration and
     * metadata.mz_score both equal the row index, so pairing is verifiable.
     */
    private function writeBatch(int $count): void
    {
        $mainRows = [];
        $metadataRows = [];

        foreach (range(0, $count - 1) as $i) {
            $mainRows[] = $this->mainRow($i, true);
            $metadataRows[] = $this->metadataRow($i);
        }

        app(SuspectRowWriter::class)->write($mainRows, $metadataRows, self::FILE_ID);
    }

    /**
     * @return array<string, mixed>
     */
    private function mainRow(int $i, bool $isNumeric): array
    {
        return [
            'file_id' => self::FILE_ID,
            'is_numeric_concentration' => $isNumeric,
            'substance_id' => null,
            'xlsx_station_mapping_id' => null,
            'station_id' => null,
            'concentration' => (float) $i,
            'ip' => 'ip-'.$i,
            'ip_max' => null,
            'based_on_hrms_library' => null,
            'units' => 'ng/L',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function metadataRow(int $i): array
    {
        return [
            'method' => 'method-'.$i,
            'mz_score' => (float) $i,
            'isotopicfit_score' => null,
            'numoffragments_score' => null,
            'ddamsms_score' => null,
            'molecularfitfragments_score' => null,
            'rti_score' => null,
            'spectral_similarity' => null,
            'rt_avg' => null,
            'fragments' => null,
            'based_on_similarity' => null,
            'based_on_compound' => null,
            'identification_proofs' => null,
            'num_fragments' => null,
        ];
    }
}
