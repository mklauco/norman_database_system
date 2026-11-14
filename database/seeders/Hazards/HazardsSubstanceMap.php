<?php

declare(strict_types=1);

namespace Database\Seeders\Hazards;

use Illuminate\Support\Facades\DB;

/**
 * In-memory lookup from SUSDAT code / dtxid to susdat_substances.id, loaded once with a single
 * read-only query (active rows only). A code or dtxid shared by more than one substance is
 * ambiguous and resolves to no substance.
 *
 * Helper class of the hazards seeders; not a seeder, nothing to run.
 */
final class HazardsSubstanceMap
{
    /** @var array<string, int|false> code => id, false when ambiguous */
    private array $byCode = [];

    /** @var array<string, int|false> upper-case dtxid => id, false when ambiguous */
    private array $byDtxid = [];

    public static function load(): self
    {
        $map = new self;

        $rows = DB::table('susdat_substances')
            ->select(['id', 'code', 'dtxid'])
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->cursor();

        foreach ($rows as $row) {
            $code = trim((string) $row->code);
            if ($code !== '') {
                $map->byCode[$code] = array_key_exists($code, $map->byCode) ? false : (int) $row->id;
            }

            $dtxid = strtoupper(trim((string) $row->dtxid));
            if ($dtxid !== '') {
                $map->byDtxid[$dtxid] = array_key_exists($dtxid, $map->byDtxid) ? false : (int) $row->id;
            }
        }

        return $map;
    }

    /**
     * Resolves a NORMAN_ID such as 'NS00000123' through its code '00000123'.
     *
     * @return array{0: int|null, 1: string} id (null when none) and status: matched, unmatched or ambiguous
     */
    public function resolveNormanId(string $normanId): array
    {
        $normanId = strtoupper(trim($normanId));
        if (! str_starts_with($normanId, 'NS')) {
            return [null, 'unmatched'];
        }

        return $this->status($this->byCode[substr($normanId, 2)] ?? null);
    }

    /**
     * @return array{0: int|null, 1: string}
     */
    public function resolveDtxid(string $dtxid): array
    {
        return $this->status($this->byDtxid[strtoupper(trim($dtxid))] ?? null);
    }

    /**
     * @return array{0: int|null, 1: string}
     */
    private function status(int|false|null $hit): array
    {
        return match (true) {
            $hit === null => [null, 'unmatched'],
            $hit === false => [null, 'ambiguous'],
            default => [$hit, 'matched'],
        };
    }
}
