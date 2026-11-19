<?php

namespace Tests\Feature\Ecotox;

use App\Models\Ecotox\LowestPNEC;
use App\Models\Susdat\Substance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LowestPnecIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_page_renders_with_a_detail_link_template(): void
    {
        $response = $this->get(route('ecotox.lowestpnec.index'));

        $response->assertOk();
        $response->assertSee(route('ecotox.lowestpnec.show', ['sus_id' => '__SUS_ID__']), false);
    }

    public function test_index_page_has_susdat_id_name_and_cas_search_fields(): void
    {
        $response = $this->get(route('ecotox.lowestpnec.index'));

        $response->assertOk();
        $response->assertSee('id="searchSusId"', false);
        $response->assertSee('id="searchSusName"', false);
        $response->assertSee('id="searchSusCas"', false);
    }

    public function test_csv_export_form_sends_susdat_id_name_and_cas_for_logged_in_users(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('ecotox.lowestpnec.index'));

        $response->assertOk();
        $response->assertSee('name="sus_id"', false);
        $response->assertSee('name="substance_name"', false);
        $response->assertSee('name="cas_number"', false);
    }

    /**
     * @return array<string, array{0: string, 1: array<int, string>}>
     */
    public static function searchCases(): array
    {
        return [
            'SusDat ID with NS prefix' => ['sus_id=NS00000060', ['Benzenecarboximidamide']],
            'SusDat ID with lowercase ns' => ['sus_id=ns00000060', ['Benzenecarboximidamide']],
            'SusDat ID without NS prefix' => ['sus_id=00000060', ['Benzenecarboximidamide']],
            'SusDat ID without leading zeros' => ['sus_id=60', ['Benzenecarboximidamide']],
            'SusDat ID is exact, not partial' => ['sus_id=6', ['Sulfamoxole']],
            'SusDat ID that is not a number' => ['sus_id=NS%2060', []],
            'name partial and case-insensitive' => ['substance_name=BENZENECARBOX', ['Benzenecarboximidamide', 'Hydroxybenzenecarboximidamide']],
            'name percent sign is literal' => ['substance_name=100%25', []],
            'CAS exact' => ['cas_number=618-39-3', ['Benzenecarboximidamide']],
            'CAS with CAS_RN prefix' => ['cas_number=CAS_RN%3A%20618-39-3', ['Benzenecarboximidamide']],
            'CAS stored with a trailing space' => ['cas_number=97240-79-4', ['Topiramate']],
            'CAS is exact, not partial' => ['cas_number=618-39', []],
            'filters are combined with AND' => ['sus_id=60&substance_name=sulfa', []],
            'filters that agree' => ['sus_id=60&cas_number=618-39-3', ['Benzenecarboximidamide']],
        ];
    }

    /**
     * @param  array<int, string>  $expectedNames
     */
    #[DataProvider('searchCases')]
    public function test_data_endpoint_filters_by_susdat_id_name_and_cas(string $query, array $expectedNames): void
    {
        $this->seedLowestPnecs();

        $response = $this->getJson(route('ecotox.lowestpnec.data').'?'.$query);

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('substance.name')->sort()->values()->all();
        $this->assertSame($expectedNames, $names);
    }

    public function test_data_endpoint_finds_a_row_without_substance_by_susdat_id(): void
    {
        LowestPNEC::query()->create(['sus_id' => 2838, 'substance_id' => null, 'lowest_exp_pred' => 2]);

        $response = $this->getJson(route('ecotox.lowestpnec.data').'?sus_id=NS00002838');

        $response->assertOk();
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.sus_id', 2838);
    }

    public function test_csv_export_applies_the_same_filters(): void
    {
        Storage::fake();
        $this->seedLowestPnecs();

        $response = $this->actingAs(User::factory()->create())
            ->post(route('ecotox.lowestpnec.csv.export'), ['cas_number' => '618-39-3']);

        $response->assertRedirect();
        $files = Storage::files('exports/lowestpnec');
        $this->assertCount(1, $files);

        $rows = array_map('str_getcsv', array_filter(explode("\n", Storage::get($files[0]))));
        $this->assertCount(2, $rows);
        $this->assertSame('NS00000060', $rows[1][1]);
        $this->assertSame('Benzenecarboximidamide', $rows[1][2]);
    }

    private function seedLowestPnecs(): void
    {
        $substances = [
            ['00000006', 'Sulfamoxole', '729-99-7'],
            ['00000060', 'Benzenecarboximidamide', '618-39-3'],
            ['00015887', 'Hydroxybenzenecarboximidamide', null],
            ['00010259', 'Topiramate', '97240-79-4 '],
        ];

        foreach ($substances as [$code, $name, $cas]) {
            $substance = Substance::factory()->create(['code' => $code, 'name' => $name, 'cas_number' => $cas]);

            LowestPNEC::query()->create([
                'sus_id' => (int) $code,
                'substance_id' => $substance->id,
                'lowest_pnec_value_1' => 1.5,
                'lowest_exp_pred' => 1,
            ]);
        }
    }
}
