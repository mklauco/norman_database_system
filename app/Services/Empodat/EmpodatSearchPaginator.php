<?php

declare(strict_types=1);

namespace App\Services\Empodat;

use Illuminate\Contracts\Pagination\Paginator as PaginatorContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Pages EMPODAT search results ordered by empodat_main.id.
 *
 * empodat_main holds ~100 M rows. For a narrow filter PostgreSQL prefers to
 * walk the primary key in id order and test every row until it has enough
 * matches, which can take tens of seconds. A cheap probe query (no ORDER BY)
 * lets the planner use the filter indexes; when the matches are few enough
 * they are sorted and sliced in PHP. Broad searches fall back to the
 * primary-key walk, which is fast when most rows match.
 */
class EmpodatSearchPaginator
{
    public const PROBE_CAP = 50000;

    public const PER_PAGE = 200;

    /**
     * @param  Builder<\App\Models\Empodat\EmpodatMain>  $query  Fully filtered query, without ORDER BY.
     */
    public function paginate(Builder $query, bool $simple): PaginatorContract
    {
        $ids = $this->probeIds($query);

        if (count($ids) > self::PROBE_CAP) {
            return $this->paginateByPrimaryKey($query, $simple);
        }

        return $this->paginateKnownIds($query, array_values(array_unique($ids)), $simple);
    }

    /**
     * Up to PROBE_CAP + 1 matching ids, possibly repeated by one-to-many joins. No DISTINCT in SQL:
     * it would make PostgreSQL read every match of a broad search before the LIMIT applies.
     *
     * @param  Builder<\App\Models\Empodat\EmpodatMain>  $query
     * @return list<int>
     */
    private function probeIds(Builder $query): array
    {
        $probe = (clone $query)->toBase();
        $probe->columns = null;
        $probe->orders = null;

        return $probe
            ->select('empodat_main.id')
            ->limit(self::PROBE_CAP + 1)
            ->pluck('empodat_main.id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    /**
     * @param  Builder<\App\Models\Empodat\EmpodatMain>  $query
     */
    private function paginateByPrimaryKey(Builder $query, bool $simple): PaginatorContract
    {
        $ordered = $query->orderBy('empodat_main.id', 'asc');

        return $simple
            ? $ordered->simplePaginate(self::PER_PAGE)->withQueryString()
            : $ordered->paginate(self::PER_PAGE)->withQueryString();
    }

    /**
     * @param  Builder<\App\Models\Empodat\EmpodatMain>  $query
     * @param  list<int>  $ids
     */
    private function paginateKnownIds(Builder $query, array $ids, bool $simple): PaginatorContract
    {
        sort($ids);

        $page = Paginator::resolveCurrentPage();
        $offset = ($page - 1) * self::PER_PAGE;
        $sliceIds = array_slice($ids, $offset, self::PER_PAGE + ($simple ? 1 : 0));

        $rows = $sliceIds === []
            ? $query->getModel()->newCollection()
            : (clone $query)
                ->whereIn('empodat_main.id', $sliceIds)
                ->orderBy('empodat_main.id', 'asc')
                ->get();

        $options = ['path' => Paginator::resolveCurrentPath(), 'pageName' => 'page'];

        $paginator = $simple
            ? new Paginator($rows, self::PER_PAGE, $page, $options)
            : new LengthAwarePaginator($rows, count($ids), self::PER_PAGE, $page, $options);

        return $paginator->withQueryString();
    }
}
