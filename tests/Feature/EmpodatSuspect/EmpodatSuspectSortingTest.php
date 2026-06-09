<?php

declare(strict_types=1);

namespace Tests\Feature\EmpodatSuspect;

use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmpodatSuspectSortingTest extends TestCase
{
    use RefreshDatabase;

    private function authUser(): User
    {
        DatabaseEntity::firstOrCreate(
            ['code' => 'empodat_suspect'],
            ['name' => 'Empodat Suspect (test)', 'is_public' => false, 'show_in_dashboard' => false]
        );
        Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole('super_admin');

        return $user;
    }

    /**
     * Three numeric rows; ip_max is intentionally out of id order so the two
     * orderings are distinguishable: id 1,2,3 -> ip_max 0.20, 0.90, 0.55.
     */
    private function seedRows(): void
    {
        // Referenced rows for the substance_id / station_id foreign keys.
        DB::table('susdat_substances')->insert(['id' => 1, 'name' => 'Test Substance']);
        DB::table('empodat_stations')->insert(['id' => 1, 'name' => 'Test Station', 'short_sample_code' => 'TS-1']);

        foreach ([[1, 0.20], [2, 0.90], [3, 0.55]] as [$id, $ipMax]) {
            DB::table('empodat_suspect_main')->insert([
                'id' => $id,
                'is_numeric_concentration' => true,
                'substance_id' => 1,
                'station_id' => 1,
                'concentration' => $id * 10,
                'ip_max' => $ipMax,
                'based_on_hrms_library' => false,
                'units' => 'ng/L',
            ]);
        }
    }

    private function search(User $user, array $params)
    {
        return $this->actingAs($user)->get(
            route('empodat_suspect.search.search', array_merge(['displayOption' => 1], $params))
        );
    }

    public function test_default_order_is_by_id_ascending(): void
    {
        $user = $this->authUser();
        $this->seedRows();

        $this->search($user, [])
            ->assertOk()
            ->assertSeeInOrder(['0.20', '0.90', '0.55']); // ip_max shown in id order 1,2,3
    }

    public function test_direct_column_sort_descending(): void
    {
        $user = $this->authUser();
        $this->seedRows();

        $this->search($user, ['sort' => 'ip_max', 'direction' => 'desc'])
            ->assertOk()
            ->assertSeeInOrder(['0.90', '0.55', '0.20']);
    }

    public function test_direct_column_sort_ascending(): void
    {
        $user = $this->authUser();
        $this->seedRows();

        $this->search($user, ['sort' => 'ip_max', 'direction' => 'asc'])
            ->assertOk()
            ->assertSeeInOrder(['0.20', '0.55', '0.90']);
    }

    public function test_invalid_sort_falls_back_to_id(): void
    {
        $user = $this->authUser();
        $this->seedRows();

        $this->search($user, ['sort' => 'bogus; DROP TABLE x', 'direction' => 'desc'])
            ->assertOk()
            ->assertSeeInOrder(['0.20', '0.90', '0.55']); // id order
    }

    public function test_related_column_sort_is_ignored_without_filters(): void
    {
        // A relationship-backed sort (substance) must NOT run on an unfiltered
        // search — it falls back to the default id order instead of attempting
        // a multi-million-row join sort.
        $user = $this->authUser();
        $this->seedRows();

        $this->search($user, ['sort' => 'substance', 'direction' => 'desc'])
            ->assertOk()
            ->assertSeeInOrder(['0.20', '0.90', '0.55']); // id order, not substance order
    }

    public function test_related_column_sort_runs_when_a_filter_is_applied(): void
    {
        // With a filter applied the join-backed sort is allowed: station name
        // asc must put "Alpha" (id 2) before "Bravo" (id 1), i.e. opposite to id
        // order — proving the leftJoin + ORDER BY path executes correctly.
        $user = $this->authUser();
        DB::table('susdat_substances')->insert(['id' => 1, 'name' => 'Test Substance']);
        DB::table('empodat_stations')->insert([
            ['id' => 1, 'name' => 'Bravo Station', 'short_sample_code' => 'B-1'],
            ['id' => 2, 'name' => 'Alpha Station', 'short_sample_code' => 'A-1'],
        ]);
        DB::table('empodat_suspect_main')->insert([
            ['id' => 1, 'is_numeric_concentration' => true, 'substance_id' => 1, 'station_id' => 1, 'concentration' => 10, 'ip_max' => 0.20, 'based_on_hrms_library' => false, 'units' => 'ng/L'],
            ['id' => 2, 'is_numeric_concentration' => true, 'substance_id' => 1, 'station_id' => 2, 'concentration' => 20, 'ip_max' => 0.90, 'based_on_hrms_library' => false, 'units' => 'ng/L'],
        ]);

        // analyticalMethodSearch flips hasFilters on without constraining rows.
        $this->actingAs($user)->get(route('empodat_suspect.search.search', [
            'displayOption' => 1,
            'analyticalMethodSearch' => [1],
            'sort' => 'station',
            'direction' => 'asc',
        ]))
            ->assertOk()
            ->assertSeeInOrder(['Alpha Station', 'Bravo Station']);
    }
}
