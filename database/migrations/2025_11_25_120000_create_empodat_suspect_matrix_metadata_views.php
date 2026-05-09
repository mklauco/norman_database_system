<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates materialized views for each matrix type containing metadata
     * for empodat_suspect stations. These views pre-join the complex path:
     *
     * empodat_suspect_stations_helper.station_id
     *   → empodat_main.station_id
     *   → empodat_main.id = empodat_matrix_*.id
     *
     * This allows fast metadata lookup for detail views and CSV exports.
     */
    public function up(): void
    {
        // Ensure helper table exists (should be created by previous migration)
        $helperExists = DB::select("
            SELECT EXISTS (
                SELECT FROM information_schema.tables
                WHERE table_schema = 'public'
                AND table_name = 'empodat_suspect_stations_helper'
            ) as exists
        ");

        if (! ($helperExists[0]->exists ?? false)) {
            throw new \RuntimeException(
                'empodat_suspect_stations_helper table does not exist. '.
                'Run empodat-suspect:refresh-filters --create first.'
            );
        }

        $this->createBiotaView();
        $this->createSedimentsView();
        $this->createWaterSurfaceView();
        $this->createWaterGroundView();
        $this->createWaterWasteView();
        $this->createSuspendedMatterView();
        $this->createSoilView();
        $this->createAirView();
        $this->createSewageSludgeView();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_biota CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_sediments CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_surface CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_ground CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_waste CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_suspended_matter CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_soil CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_air CASCADE');
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_sewage_sludge CASCADE');
    }

    private function createBiotaView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_biota CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_biota AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mb.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_biota mb ON mb.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmb_station_id ON empodat_suspect_matrix_biota(station_id)');
        DB::statement('CREATE INDEX idx_esmb_empodat_main_id ON empodat_suspect_matrix_biota(empodat_main_id)');
    }

    private function createSedimentsView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_sediments CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_sediments AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                ms.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_sediments ms ON ms.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esms_station_id ON empodat_suspect_matrix_sediments(station_id)');
        DB::statement('CREATE INDEX idx_esms_empodat_main_id ON empodat_suspect_matrix_sediments(empodat_main_id)');
    }

    private function createWaterSurfaceView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_surface CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_water_surface AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mws.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_surface mws ON mws.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmws_station_id ON empodat_suspect_matrix_water_surface(station_id)');
        DB::statement('CREATE INDEX idx_esmws_empodat_main_id ON empodat_suspect_matrix_water_surface(empodat_main_id)');
    }

    private function createWaterGroundView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_ground CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_water_ground AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mwg.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_ground mwg ON mwg.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmwg_station_id ON empodat_suspect_matrix_water_ground(station_id)');
        DB::statement('CREATE INDEX idx_esmwg_empodat_main_id ON empodat_suspect_matrix_water_ground(empodat_main_id)');
    }

    private function createWaterWasteView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_water_waste CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_water_waste AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mww.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_water_waste mww ON mww.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmww_station_id ON empodat_suspect_matrix_water_waste(station_id)');
        DB::statement('CREATE INDEX idx_esmww_empodat_main_id ON empodat_suspect_matrix_water_waste(empodat_main_id)');
    }

    private function createSuspendedMatterView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_suspended_matter CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_suspended_matter AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                msm.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_suspended_matter msm ON msm.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmsm_station_id ON empodat_suspect_matrix_suspended_matter(station_id)');
        DB::statement('CREATE INDEX idx_esmsm_empodat_main_id ON empodat_suspect_matrix_suspended_matter(empodat_main_id)');
    }

    private function createSoilView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_soil CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_soil AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mso.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_soil mso ON mso.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmso_station_id ON empodat_suspect_matrix_soil(station_id)');
        DB::statement('CREATE INDEX idx_esmso_empodat_main_id ON empodat_suspect_matrix_soil(empodat_main_id)');
    }

    private function createAirView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_air CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_air AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                ma.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_air ma ON ma.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esma_station_id ON empodat_suspect_matrix_air(station_id)');
        DB::statement('CREATE INDEX idx_esma_empodat_main_id ON empodat_suspect_matrix_air(empodat_main_id)');
    }

    private function createSewageSludgeView(): void
    {
        DB::statement('DROP MATERIALIZED VIEW IF EXISTS empodat_suspect_matrix_sewage_sludge CASCADE');
        DB::statement('
            CREATE MATERIALIZED VIEW empodat_suspect_matrix_sewage_sludge AS
            SELECT DISTINCT
                ss.station_id,
                em.id as empodat_main_id,
                mss.*
            FROM empodat_suspect_stations_helper ss
            INNER JOIN empodat_main em ON em.station_id = ss.station_id
            INNER JOIN empodat_matrix_sewage_sludge mss ON mss.id = em.id
        ');
        DB::statement('CREATE INDEX idx_esmss_station_id ON empodat_suspect_matrix_sewage_sludge(station_id)');
        DB::statement('CREATE INDEX idx_esmss_empodat_main_id ON empodat_suspect_matrix_sewage_sludge(empodat_main_id)');
    }
};
