<?php

declare(strict_types=1);

namespace Tests\Feature\Hazards;

use App\Models\User;
use App\Services\Hazards\HazardsReseedService;
use Database\Seeders\Hazards\HazardsDataSeeder;
use Database\Seeders\Hazards\HazardsPikmeLoader;
use Database\Seeders\Hazards\HazardsSubstanceMap;
use Illuminate\Support\Facades\DB;

/**
 * Edge cases of the hazards rebuild: empty Janus parameters, CompTox rows keyed by their
 * natural id, user deletion next to expert conclusions, and rows whose source moves to
 * another substance.
 */
class HazardsRebuildEdgeCasesTest extends HazardsTestCase
{
    public function test_janus_parameter_without_values_is_not_stored_and_disappears_when_values_go(): void
    {
        $this->useTempStorage();
        $this->makeSubstance('11111111');
        $emptyCarcinogenicity = ['C assessment' => null, 'C reliability' => null, 'C score' => null];

        $this->writeJanus([$this->janusRow('NS11111111', $emptyCarcinogenicity)]);
        $this->seed(HazardsDataSeeder::class);

        $this->assertFalse($this->derivedRowExists('janus:NS11111111:carcinogenicity'));
        $this->assertTrue($this->derivedRowExists('janus:NS11111111:persistency'));

        $this->writeJanus([$this->janusRow('NS11111111')]);
        $this->seed(HazardsDataSeeder::class);

        $this->assertTrue($this->derivedRowExists('janus:NS11111111:carcinogenicity'));

        $this->writeJanus([$this->janusRow('NS11111111', $emptyCarcinogenicity)]);
        $this->seed(HazardsDataSeeder::class);

        $this->assertFalse($this->derivedRowExists('janus:NS11111111:carcinogenicity'));
    }

    public function test_comptox_fate_rows_keep_their_identity_when_the_record_is_reinserted(): void
    {
        $substance = $this->makeSubstance('22222222', 'DTXSID2222');
        $this->insertFateRecord($substance->id, 'DTXSID2222', 'FATE-7');

        app(HazardsReseedService::class)->rebuild();

        $row = DB::table('hazards_comptox_substance_data')->where('source_key', 'fate:DTXSID2222:FATE-7')->first();
        $this->assertNotNull($row);

        DB::table('hazards_comptox_fate_records')->delete();
        $this->insertFateRecord($substance->id, 'DTXSID2222', 'FATE-7');

        $summary = app(HazardsReseedService::class)->rebuild();

        $this->assertSame(0, $summary['inserted']);
        $this->assertSame(
            $row->id,
            DB::table('hazards_comptox_substance_data')->where('source_key', 'fate:DTXSID2222:FATE-7')->value('id')
        );
        $this->assertNull(
            DB::table('hazards_comptox_substance_data')->where('id', $row->id)->value('withdrawn_at')
        );
    }

    public function test_users_with_expert_conclusions_on_the_same_substance_can_be_deleted(): void
    {
        $substance = $this->makeSubstance('33333333');
        $first = User::factory()->create();
        $second = User::factory()->create();

        foreach ([$first, $second] as $user) {
            DB::table('hazards_substance_classifications')->insert([
                'susdat_substance_id' => $substance->id,
                'editor_user_id' => $user->id,
                'P' => 'vP',
                'kind' => 'classification',
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $first->delete();
        $second->delete();

        $this->assertSame(
            2,
            DB::table('hazards_substance_classifications')
                ->where('susdat_substance_id', $substance->id)
                ->where('kind', 'classification')
                ->whereNull('editor_user_id')
                ->where('is_current', true)
                ->count()
        );
    }

    public function test_a_source_row_that_now_resolves_to_another_substance_is_reported(): void
    {
        $this->useTempStorage();
        $first = $this->makeSubstance('44444444', 'DTXSID4444');
        $second = $this->makeSubstance('55555555', 'DTXSID5555');

        $this->writePikme([$this->pikmeRow('DTXSID4444')]);
        $this->seed(HazardsDataSeeder::class);

        DB::table('susdat_substances')->where('id', $first->id)->update(['dtxid' => 'DTXSID9999']);
        DB::table('susdat_substances')->where('id', $second->id)->update(['dtxid' => 'DTXSID4444']);
        (new HazardsPikmeLoader(static function (string $level, string $message): void {}))->load(HazardsSubstanceMap::load());
        $summary = app(HazardsReseedService::class)->rebuild();

        $this->assertGreaterThan(0, $summary['moved_to_other_substance']);
        $this->assertSame(
            $second->id,
            (int) DB::table('hazards_comptox_substance_data')->where('source_key', 'pikme:DTXSID4444:koc')->value('susdat_substance_id')
        );
    }

    private function derivedRowExists(string $sourceKey): bool
    {
        return DB::table('hazards_comptox_substance_data')
            ->where('source_key', $sourceKey)
            ->whereNull('withdrawn_at')
            ->exists();
    }

    private function insertFateRecord(int $substanceId, string $dtxid, string $fateId): void
    {
        DB::table('hazards_comptox_fate_records')->insert([
            'susdat_substance_id' => $substanceId,
            'fate_id' => $fateId,
            'dtxid' => $dtxid,
            'endpoint_name' => 'Biodeg. Half-Life',
            'result_value' => '12.5',
            'model_source' => 'OPERA',
            'unit' => 'days',
            'value_type' => 'predicted',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
