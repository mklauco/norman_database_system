<?php

declare(strict_types=1);

namespace Tests\Feature\ARBG;

use App\Models\ARBG\DataSampleMatrix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SampleMatrixFilterListTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The subset of arbg_data_sample_matrix exercised here, using the ids and
     * names shipped in the seed CSV.
     *
     * @var list<array{id: int, name: string}>
     */
    private const MATRICES = [
        ['id' => 1, 'name' => 'Surface water - River water'],
        ['id' => 7, 'name' => 'Surface water - Other'],
        ['id' => 8, 'name' => 'Groundwater'],
        ['id' => 9, 'name' => 'Wastewater - Urban'],
        ['id' => 12, 'name' => 'Wastewater - Other'],
        ['id' => 24, 'name' => 'Non-agricultural soils'],
        ['id' => 27, 'name' => 'Other'],
        ['id' => 32, 'name' => 'Sewage Sludge - Municipal sludge'],
        ['id' => 33, 'name' => 'Other'],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('arbg_data_sample_matrix')->insert(array_map(
            fn (array $matrix): array => $matrix + ['is_active' => true, 'ordering' => 0],
            self::MATRICES
        ));
    }

    public function test_matrices_are_grouped_by_main_matrix_with_other_last_in_each_group(): void
    {
        $list = DataSampleMatrix::filterList(array_column(self::MATRICES, 'id'));

        $this->assertSame([
            'Surface water - River water',
            'Surface water - Other',
            'Groundwater',
            'Wastewater - Urban',
            'Wastewater - Other',
            'Non-agricultural soils',
            'Soil - Other',
            'Sewage Sludge - Municipal sludge',
            'Sewage Sludge - Other',
        ], array_values($list));
    }

    public function test_list_is_keyed_by_matrix_id(): void
    {
        $list = DataSampleMatrix::filterList(array_column(self::MATRICES, 'id'));

        $this->assertSame(array_column(self::MATRICES, 'id'), array_keys($list));
    }

    public function test_the_generic_other_buckets_are_qualified_with_their_main_matrix(): void
    {
        $list = DataSampleMatrix::filterList([27, 33]);

        $this->assertSame([27 => 'Soil - Other', 33 => 'Sewage Sludge - Other'], $list);
    }

    public function test_an_other_bucket_is_qualified_even_when_it_is_the_only_one_listed(): void
    {
        $list = DataSampleMatrix::filterList([8, 27]);

        $this->assertSame([8 => 'Groundwater', 27 => 'Soil - Other'], $list);
    }

    public function test_names_that_already_carry_their_main_matrix_are_left_untouched(): void
    {
        $list = DataSampleMatrix::filterList([7, 12]);

        $this->assertSame([7 => 'Surface water - Other', 12 => 'Wastewater - Other'], $list);
    }

    public function test_only_the_requested_matrices_are_returned(): void
    {
        $list = DataSampleMatrix::filterList([9, 12]);

        $this->assertSame([9 => 'Wastewater - Urban', 12 => 'Wastewater - Other'], $list);
    }
}
