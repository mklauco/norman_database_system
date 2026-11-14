<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\Susdat\Substance;
use App\Models\User;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsSetupSeeder;

/**
 * The NORMAN SusDat ID is 'NS' + susdat_substances.code. The internal id differs from the code
 * for almost every substance, so a page that formats the id as 'NS…' shows another
 * substance's SusDat ID.
 */
class HazardsSusdatIdDisplayTest extends HazardsTestCase
{
    private Substance $substance;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(HazardsSetupSeeder::class);

        $this->useTempStorage();
        $this->makeSubstance('00000001');
        $this->makeSubstance('00000002');
        $this->substance = $this->makeSubstance('00000262', 'DTXSID9020112');
        $this->writeJanus([$this->janusRow('NS00000262')]);
        $this->seed(HazardsDataSeeder::class);

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('hazards');
    }

    public function test_pages_show_the_code_based_susdat_id_and_never_the_internal_id(): void
    {
        $this->assertNotSame('262', (string) $this->substance->id);
        $idBased = 'NS'.str_pad((string) $this->substance->id, 8, '0', STR_PAD_LEFT);

        $pages = [
            route('hazards.derivation.index', $this->substance->id),
            route('hazards.classification.index', $this->substance->id),
            route('hazards.data.search.search', ['substances' => [$this->substance->id], 'searchSubstance' => 1, 'displayLayout' => 'detailed']),
        ];

        foreach ($pages as $page) {
            $this->actingAs($this->reviewer)->get($page)
                ->assertOk()
                ->assertSee('NS00000262')
                ->assertDontSee($idBased);
        }
    }
}
