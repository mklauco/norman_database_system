<?php

declare(strict_types=1);

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Model;

class DataSampleMatrix extends Model
{
    protected $table = 'arbg_data_sample_matrix';

    /**
     * Main matrix each block of ids belongs to, mirroring the grouping held in
     * the third column of
     * database/seeders/seeds/arbg_tables/data_tables/data_sample_matrix.csv.
     *
     * Only used to qualify the generic "Other" buckets; the grouping itself is
     * not persisted yet (issue #29).
     *
     * @var list<array{0: int, 1: int, 2: string}>
     */
    private const MAIN_MATRIX_ID_BLOCKS = [
        [1, 12, 'Water'],
        [21, 27, 'Soil'],
        [31, 33, 'Sewage Sludge'],
    ];

    /**
     * Build the [id => label] list backing the Matrix criteria of the Search
     * Bacteria and Search Genes filters.
     *
     * Ordering is by id, which groups the sub-matrices under their main matrix
     * and leaves each "Other" at the end of its group. Two matrices are named
     * plainly "Other" (Soil and Sewage Sludge), so those are qualified with
     * their main matrix to keep the options distinguishable.
     *
     * @param  iterable<int|string>  $ids
     * @return array<int, string>
     */
    public static function filterList(iterable $ids): array
    {
        return static::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->get(['id', 'name'])
            ->mapWithKeys(fn (self $matrix): array => [
                $matrix->id => static::label((int) $matrix->id, (string) $matrix->name),
            ])
            ->toArray();
    }

    protected static function label(int $id, string $name): string
    {
        if (strcasecmp($name, 'Other') !== 0) {
            return $name;
        }

        $mainMatrix = static::mainMatrixForId($id);

        return $mainMatrix === null ? $name : $mainMatrix.' - '.$name;
    }

    protected static function mainMatrixForId(int $id): ?string
    {
        foreach (self::MAIN_MATRIX_ID_BLOCKS as [$from, $to, $mainMatrix]) {
            if ($id >= $from && $id <= $to) {
                return $mainMatrix;
            }
        }

        return null;
    }
}
