<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the sampling-date month and day columns dropped by the v1→v2
     * import (#22).
     *
     * Legacy `dct_analysis` stores the sampling date twice: as the datetime
     * `sampling_date`, and split into `sampling_date_y` / `_m` / `_d`. The
     * import kept the datetime and routed the year to
     * `empodat_main.sampling_date_year` (the fast-search column), but had
     * nowhere to put the month and day — see the "Dropped legacy cols" note in
     * `ImportEmpodatMinorStep`.
     *
     * That is harmless for records where legacy also filled the datetime, but
     * above `empodat_minor.id` ≈ 20 000 000 legacy left `sampling_date` empty
     * and stored the date only in the parts. For those rows the day and month
     * were lost: record 65080319 reads "15.01.2018" in the legacy record view
     * and "2018" here.
     *
     * These columns are created EMPTY on purpose. Backfilling them needs the
     * legacy MariaDB for ~80 million rows and is handled by a migrator run
     * outside this project; this migration only provides the destination.
     *
     * Both are `varchar` to match the columns already on the table
     * (`sampling_date1_y` / `_m` / `_d` are varchar too, carrying the legacy
     * zero-filled strings such as '01'), so the external migrator can copy
     * legacy values verbatim without a type conversion.
     */
    public function up(): void
    {
        Schema::table('empodat_minor', function (Blueprint $table) {
            if (! Schema::hasColumn('empodat_minor', 'sampling_date_m')) {
                $table->string('sampling_date_m')->nullable()->after('sampling_date_t');
            }

            if (! Schema::hasColumn('empodat_minor', 'sampling_date_d')) {
                $table->string('sampling_date_d')->nullable()->after('sampling_date_m');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empodat_minor', function (Blueprint $table) {
            $table->dropColumn(['sampling_date_m', 'sampling_date_d']);
        });
    }
};
