<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\ARBG\BacteriaMain;
use App\Models\ARBG\GeneMain;
use App\Models\Bioassay\FieldStudy;
use App\Models\DatabaseEntity;
use App\Models\Ecotox\EcotoxFinal;
use App\Models\Ecotox\LowestPNEC;
use App\Models\Ecotox\PNEC3;
use App\Models\Hazards\ComptoxSubstanceData;
use App\Models\Indoor\IndoorMain;
use App\Models\Literature\LiteratureTempMain;
use App\Models\Passive\PassiveSamplingMain;
use App\Models\Prioritisation\ModellingDanube;
use App\Models\Prioritisation\ModellingScarce;
use App\Models\Prioritisation\MonitoringDanube;
use App\Models\Prioritisation\MonitoringScarce;
use App\Models\Sars\SarsMain;
use App\Models\SLE\SuspectListExchangeSource;
use App\Models\Susdat\Substance;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recomputes database_entities.number_of_records and last_update for every
 * module whose count is cheap to obtain.
 *
 * EMPODAT is refreshed by GenerateEmpodatStatisticsAction and EMPODAT Suspect
 * is excluded on purpose because its real count needs a full scan of a
 * very large table; neither has a definition here.
 */
class RefreshDatabaseEntityCountsAction
{
    /**
     * Module codes handled by this action, in database directory order.
     *
     * @return array<int, string>
     */
    public function codes(): array
    {
        return array_keys($this->definitions());
    }

    /**
     * Refresh the given module codes, or all supported codes when null.
     *
     * Codes without a database_entities row or without a definition are skipped.
     * A failing module is logged and left out of the result.
     *
     * @param  array<int, string>|null  $codes
     * @return array<string, int> code => new number_of_records
     */
    public function handle(?array $codes = null): array
    {
        $definitions = $this->definitions();
        $codes ??= array_keys($definitions);
        $results = [];

        foreach ($codes as $code) {
            if (! isset($definitions[$code])) {
                continue;
            }

            try {
                $entity = DatabaseEntity::query()->where('code', $code)->first();

                if ($entity === null) {
                    continue;
                }

                [$count, $lastUpdate] = $definitions[$code]();

                $entity->update([
                    'number_of_records' => $count,
                    'last_update' => $lastUpdate,
                ]);

                $results[$code] = $count;
            } catch (Throwable $exception) {
                Log::error('Refreshing database entity count failed', [
                    'code' => $code,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * @return array<string, callable(): array{0: int, 1: mixed}>
     */
    private function definitions(): array
    {
        return [
            'susdat' => fn (): array => [Substance::count(), Substance::max('updated_at')],
            'ecotox' => fn (): array => [
                LowestPNEC::count() + EcotoxFinal::count(),
                LowestPNEC::max('updated_at'),
            ],
            'ecotox.ecotox' => fn (): array => [EcotoxFinal::count(), EcotoxFinal::max('updated_at')],
            'ecotox.pnec' => fn (): array => [LowestPNEC::count(), LowestPNEC::max('updated_at')],
            'ecotox.pnec3' => fn (): array => [PNEC3::count(), PNEC3::max('updated_at')],
            'sle' => fn (): array => [
                SuspectListExchangeSource::count(),
                SuspectListExchangeSource::max('updated_at'),
            ],
            'arbg' => fn (): array => [
                BacteriaMain::count() + GeneMain::count(),
                max(BacteriaMain::max('updated_at'), GeneMain::max('updated_at')),
            ],
            'arbg.bacteria' => fn (): array => [BacteriaMain::count(), BacteriaMain::max('updated_at')],
            'arbg.gene' => fn (): array => [GeneMain::count(), GeneMain::max('updated_at')],
            'indoor' => fn (): array => [IndoorMain::count(), IndoorMain::max('updated_at')],
            'passive' => fn (): array => [PassiveSamplingMain::count(), PassiveSamplingMain::max('updated_at')],
            'bioassay' => fn (): array => [FieldStudy::count(), FieldStudy::max('updated_at')],
            'literature' => fn (): array => [LiteratureTempMain::count(), LiteratureTempMain::max('updated_at')],
            'prioritisation' => fn (): array => [
                MonitoringDanube::count()
                    + MonitoringScarce::count()
                    + ModellingDanube::count()
                    + ModellingScarce::count(),
                max([
                    MonitoringDanube::max('updated_at') ?? '1970-01-01',
                    MonitoringScarce::max('updated_at') ?? '1970-01-01',
                    ModellingDanube::max('updated_at') ?? '1970-01-01',
                    ModellingScarce::max('updated_at') ?? '1970-01-01',
                ]),
            ],
            'hazards' => fn (): array => [
                ComptoxSubstanceData::query()->active()->count(),
                ComptoxSubstanceData::query()->active()->max('updated_at'),
            ],
            'sars' => fn (): array => [SarsMain::count(), SarsMain::max('updated_at')],
        ];
    }
}
