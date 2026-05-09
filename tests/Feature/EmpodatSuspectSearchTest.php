<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\DatabaseEntity;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class EmpodatSuspectSearchTest extends TestCase
{
    /**
     * Test that searching by country returns results when data exists.
     */
    public function test_search_by_country_returns_results(): void
    {
        // Ensure the empodat_suspect module exists
        $module = DatabaseEntity::where('code', 'empodat_suspect')->first();
        if (! $module) {
            $this->markTestSkipped('Empodat Suspect module not found in database.');
        }

        // Use a known country ID that has data (231 = UK based on earlier analysis)
        // This country has data in both empodat_suspect_main and station_filters
        $countryId = 231;

        // Verify the country has data
        $hasData = DB::table('empodat_suspect_station_filters')
            ->where('country_id', $countryId)
            ->exists();

        if (! $hasData) {
            $this->markTestSkipped('Test country does not have empodat_suspect data.');
        }

        // Ensure the role exists
        $role = Role::firstOrCreate(['name' => 'empodat_suspect', 'guard_name' => 'web']);

        // Create and authenticate a user with the required role
        $user = User::factory()->create();
        $user->assignRole($role);

        // Perform a search with country filter (as array format)
        $url = route('empodat_suspect.search.search').'?displayOption=1&countrySearch%5B%5D='.$countryId;
        $searchResponse = $this->actingAs($user)->get($url);

        $searchResponse->assertStatus(200);

        // Check that we have actual results (table rows with data)
        $searchResponse->assertSee('<tbody>', false);
        $searchResponse->assertDontSee('No results found');
    }
}
