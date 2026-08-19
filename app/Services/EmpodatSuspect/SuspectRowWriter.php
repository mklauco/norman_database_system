<?php

declare(strict_types=1);

namespace App\Services\EmpodatSuspect;

use Illuminate\Support\Facades\DB;

/**
 * Writes paired `empodat_suspect_main` + `empodat_suspect_metadata` rows.
 *
 * `empodat_suspect_metadata` carries a composite foreign key on
 * `(id, is_numeric_concentration)` back to `empodat_suspect_main`, so every
 * metadata row's id must belong to an already-written main row in the same
 * partition. The previous approach inserted main rows with `RETURNING id`
 * and zipped the returned rows onto a metadata batch by array index — correct
 * only because PostgreSQL happens to preserve input order in `RETURNING`,
 * which is not a documented guarantee. If it ever broke, every metadata row
 * would silently attach to the wrong main row (the FK would not catch it —
 * each id still exists, just under the wrong payload).
 *
 * This class removes that assumption entirely: ids are allocated from the
 * sequence FIRST, in PHP, and bound onto both a main row and its metadata
 * row in the very same loop, before any INSERT runs. The id <-> payload
 * relationship is therefore correct by construction — a plain array index —
 * rather than inferred from insert or return order.
 */
class SuspectRowWriter
{
    /**
     * Bound-parameter budget for a single multi-row INSERT.
     *
     * PostgreSQL hard-caps bound parameters at 65535 per statement. Rather
     * than hard-code a row count per table, {@see insertRows()} derives the
     * chunk size from this budget and the actual column count, so the limit
     * cannot be silently outgrown when a column is added.
     *
     * That matters here: the metadata insert binds 17 columns per row, so at
     * the seeders' batch size of 4000 it would need 17 * 4000 = 68 000
     * bindings — over the cap. Adding `file_id` is precisely what pushed it
     * there, and a previous version of this code carried a hand-tuned row
     * count that the extra column invalidated. `empodat_suspect_main` binds
     * 11 columns (44 000 at the same batch size) and fits comfortably, but it
     * is chunked by the same rule so raising BATCH_SIZE can never break it
     * either.
     *
     * 60000 rather than 65535 leaves headroom.
     */
    private const MAX_BIND_PARAMETERS = 60000;

    /**
     * Allocate ids and write paired main/metadata rows in a single transaction.
     *
     * `$mainRows[$i]` and `$metadataRows[$i]` are positionally paired by the
     * caller; neither carries an `id` — ids are allocated here. A `null`
     * entry in `$metadataRows` means "this main row has no metadata row",
     * which is a normal, expected case (a main row is never required to
     * have metadata).
     *
     * @param  list<array{
     *     file_id: int,
     *     is_numeric_concentration: bool,
     *     substance_id: int|null,
     *     xlsx_station_mapping_id: int|null,
     *     station_id: int|null,
     *     concentration: float|null,
     *     ip: string|null,
     *     ip_max: float|null,
     *     based_on_hrms_library: bool|null,
     *     units: string|null,
     * }>  $mainRows
     * @param  list<array{
     *     method: string|null,
     *     mz_score: float|null,
     *     isotopicfit_score: float|null,
     *     numoffragments_score: float|null,
     *     ddamsms_score: float|null,
     *     molecularfitfragments_score: float|null,
     *     rti_score: float|null,
     *     spectral_similarity: float|null,
     *     rt_avg: float|null,
     *     fragments: string|null,
     *     based_on_similarity: string|null,
     *     based_on_compound: string|null,
     *     identification_proofs: string|null,
     *     num_fragments: float|null,
     * }|null>  $metadataRows
     * @return int number of main rows written
     *
     * @throws \LogicException if `$mainRows` and `$metadataRows` differ in length
     */
    public function write(array $mainRows, array $metadataRows, int $fileId): int
    {
        $count = count($mainRows);
        if ($count !== count($metadataRows)) {
            throw new \LogicException(sprintf(
                'SuspectRowWriter::write(): main/metadata row count mismatch (%d main rows vs %d metadata rows).',
                $count,
                count($metadataRows)
            ));
        }

        if ($count === 0) {
            return 0;
        }

        $ids = $this->allocateIds($count);

        // Bind the allocated id onto both rows of each pair — plus, for
        // metadata, the file id and the paired main row's
        // is_numeric_concentration (the metadata FK is composite on
        // (id, is_numeric_concentration), so it must always match the main
        // row exactly) — before any write happens. This loop is the entire
        // correctness argument: id <-> payload is a plain array index, not
        // something inferred from insert or return order.
        $mainValues = [];
        $metadataValues = [];
        foreach ($mainRows as $i => $mainRow) {
            $id = $ids[$i];
            $mainValues[] = $this->buildMainRow($id, $mainRow);

            $metadataRow = $metadataRows[$i];
            if ($metadataRow !== null) {
                $metadataValues[] = $this->buildMetadataRow($id, $fileId, $mainRow, $metadataRow);
            }
        }

        DB::transaction(function () use ($mainValues, $metadataValues): void {
            $this->insertMain($mainValues);
            $this->insertMetadata($metadataValues);
        });

        return $count;
    }

    /**
     * Allocate `$n` fresh, sequential ids from `empodat_suspect_main`'s own
     * BIGSERIAL sequence — the same sequence a normal INSERT would draw
     * from, just called up front instead of relying on an
     * INSERT ... RETURNING to hand the ids back.
     *
     * @return list<int>
     */
    private function allocateIds(int $n): array
    {
        $rows = DB::select(
            "SELECT nextval('empodat_suspect_main_id_seq') AS id FROM generate_series(1, ?)",
            [$n]
        );

        if (count($rows) !== $n) {
            throw new \RuntimeException(sprintf(
                'SuspectRowWriter::allocateIds(): expected %d ids, got %d.',
                $n,
                count($rows)
            ));
        }

        return array_map(static fn (object $row): int => (int) $row->id, $rows);
    }

    /**
     * @param  array<string, mixed>  $mainRow
     * @return array<string, mixed>
     */
    private function buildMainRow(int $id, array $mainRow): array
    {
        return [
            'id' => $id,
            'file_id' => $mainRow['file_id'],
            'is_numeric_concentration' => $mainRow['is_numeric_concentration'],
            'substance_id' => $mainRow['substance_id'],
            'xlsx_station_mapping_id' => $mainRow['xlsx_station_mapping_id'],
            'station_id' => $mainRow['station_id'],
            'concentration' => $mainRow['concentration'],
            'ip' => $mainRow['ip'],
            'ip_max' => $mainRow['ip_max'],
            'based_on_hrms_library' => $mainRow['based_on_hrms_library'],
            'units' => $mainRow['units'],
        ];
    }

    /**
     * `is_numeric_concentration` is deliberately read from `$mainRow`, not
     * `$metadataRow` — see the class docblock. `file_id` is deliberately
     * the `write()` argument, not a caller-supplied field.
     *
     * @param  array<string, mixed>  $mainRow
     * @param  array<string, mixed>  $metadataRow
     * @return array<string, mixed>
     */
    private function buildMetadataRow(int $id, int $fileId, array $mainRow, array $metadataRow): array
    {
        return [
            'id' => $id,
            'is_numeric_concentration' => $mainRow['is_numeric_concentration'],
            'file_id' => $fileId,
            'method' => $metadataRow['method'],
            'mz_score' => $metadataRow['mz_score'],
            'isotopicfit_score' => $metadataRow['isotopicfit_score'],
            'numoffragments_score' => $metadataRow['numoffragments_score'],
            'ddamsms_score' => $metadataRow['ddamsms_score'],
            'molecularfitfragments_score' => $metadataRow['molecularfitfragments_score'],
            'rti_score' => $metadataRow['rti_score'],
            'spectral_similarity' => $metadataRow['spectral_similarity'],
            'rt_avg' => $metadataRow['rt_avg'],
            'fragments' => $metadataRow['fragments'],
            'based_on_similarity' => $metadataRow['based_on_similarity'],
            'based_on_compound' => $metadataRow['based_on_compound'],
            'identification_proofs' => $metadataRow['identification_proofs'],
            'num_fragments' => $metadataRow['num_fragments'],
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertMain(array $rows): void
    {
        $this->insertRows('empodat_suspect_main', $rows);
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertMetadata(array $rows): void
    {
        $this->insertRows('empodat_suspect_metadata', $rows);
    }

    /**
     * Build and execute multi-row INSERTs for `$rows`, split into as few
     * statements as the bound-parameter budget allows. Every row must share
     * the same keys, in the same order (guaranteed here since
     * `buildMainRow()` / `buildMetadataRow()` always return the same shape).
     *
     * @param  list<array<string, mixed>>  $rows
     */
    private function insertRows(string $table, array $rows): void
    {
        if ($rows === []) {
            return;
        }

        $columns = array_keys($rows[0]);
        $rowsPerStatement = max(1, intdiv(self::MAX_BIND_PARAMETERS, count($columns)));

        $placeholders = '('.implode(', ', array_fill(0, count($columns), '?')).')';
        $columnsSql = implode(', ', $columns);

        foreach (array_chunk($rows, $rowsPerStatement) as $chunk) {
            $valuesSql = implode(', ', array_fill(0, count($chunk), $placeholders));

            $bindings = [];
            foreach ($chunk as $row) {
                foreach ($columns as $column) {
                    $bindings[] = $row[$column];
                }
            }

            DB::insert("INSERT INTO {$table} ({$columnsSql}) VALUES {$valuesSql}", $bindings);
        }
    }
}
