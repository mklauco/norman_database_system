<?php

namespace App\Models\Empodat;

use App\Models\Backend\File;
use App\Models\Empodat\AnalyticalMethod as EmpodatAnalyticalMethod;
use App\Models\List\ConcentrationIndicator;
use App\Models\List\Country;
use App\Models\List\Matrix;
use App\Models\Susdat\Substance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EmpodatMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empodat_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'country_id',
        'file_id',
        'station_id',
        'matrix_id',
        'substance_id',
        'sampling_date_year',
        'concentration_indicator_id',
        'concentration_value',
        'method_id',
        'data_source_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'country_id' => 'integer',
        'file_id' => 'integer',
        'station_id' => 'integer',
        'matrix_id' => 'integer',
        'substance_id' => 'integer',
        'sampling_date_year' => 'integer',
        'concentration_indicator_id' => 'integer',
        'concentration_value' => 'float',
        'method_id' => 'integer',
        'data_source_id' => 'integer',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'formatted_sampling_date',
    ];

    /**
     * Get the country associated with this record.
     */
    public function country()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }

    /**
     * Get the file associated with this record.
     */
    public function file()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Get the concentration indicator associated with this record.
     */
    public function concentrationIndicator()
    {
        return $this->belongsTo(ConcentrationIndicator::class, 'concentration_indicator_id');
    }

    /**
     * Get the station associated with this record.
     */
    public function station()
    {
        return $this->belongsTo(EmpodatStation::class, 'station_id');
    }

    /**
     * Get the substance associated with this record.
     */
    public function substance()
    {
        return $this->belongsTo(Substance::class, 'substance_id');
    }

    /**
     * Get the matrix associated with this record.
     */
    public function matrix()
    {
        return $this->belongsTo(Matrix::class, 'matrix_id');
    }

    /**
     * Get the analytical method associated with this record.
     */
    public function analyticalMethod()
    {
        return $this->belongsTo(EmpodatAnalyticalMethod::class, 'method_id');
    }

    /**
     * Get the data source associated with this record.
     */
    public function dataSource()
    {
        return $this->belongsTo(DataSources::class, 'data_source_id');
    }

    /**
     * Get the minor details associated with this record.
     */
    public function minor()
    {
        return $this->hasOne(EmpodatMinor::class, 'id', 'id');
    }

    /**
     * Get the air matrix data associated with this record.
     */
    public function matrixAir()
    {
        return $this->hasOne(EmpodatMatrixAir::class, 'id', 'id');
    }

    /**
     * Get the biota matrix data associated with this record.
     */
    public function matrixBiota()
    {
        return $this->hasOne(EmpodatMatrixBiota::class, 'id', 'id');
    }

    /**
     * Get the sediments matrix data associated with this record.
     */
    public function matrixSediments()
    {
        return $this->hasOne(EmpodatMatrixSediments::class, 'id', 'id');
    }

    /**
     * Get the sewage sludge matrix data associated with this record.
     */
    public function matrixSewageSludge()
    {
        return $this->hasOne(EmpodatMatrixSewageSludge::class, 'id', 'id');
    }

    /**
     * Get the soil matrix data associated with this record.
     */
    public function matrixSoil()
    {
        return $this->hasOne(EmpodatMatrixSoil::class, 'id', 'id');
    }

    /**
     * Get the suspended matter matrix data associated with this record.
     */
    public function matrixSuspendedMatter()
    {
        return $this->hasOne(EmpodatMatrixSuspendedMatter::class, 'id', 'id');
    }

    /**
     * Get the water surface matrix data associated with this record.
     */
    public function matrixWaterSurface()
    {
        return $this->hasOne(EmpodatMatrixWaterSurface::class, 'id', 'id');
    }

    /**
     * Get the water ground matrix data associated with this record.
     */
    public function matrixWaterGround()
    {
        return $this->hasOne(EmpodatMatrixWaterGround::class, 'id', 'id');
    }

    /**
     * Get the water waste matrix data associated with this record.
     */
    public function matrixWaterWaste()
    {
        return $this->hasOne(EmpodatMatrixWaterWaste::class, 'id', 'id');
    }

    /**
     * The files that are associated with this empodat record.
     */
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'empodat_main_file', 'empodat_main_id', 'file_id');
    }

    /**
     * Scope to filter by countries through station relationship (optimized with JOIN)
     */
    public function scopeByCountries($query, array $countryIds)
    {
        if (empty($countryIds)) {
            return $query;
        }

        return $query->join('empodat_stations', 'empodat_main.station_id', '=', 'empodat_stations.id')
            ->whereIn('empodat_stations.country_id', $countryIds)
            ->select('empodat_main.*')
            ->distinct();
    }

    /**
     * Scope to filter by matrices
     */
    public function scopeByMatrices($query, array $matrixIds)
    {
        if (empty($matrixIds)) {
            return $query;
        }

        return $query->whereIn('matrix_id', $matrixIds);
    }

    /**
     * Scope to filter by substances
     */
    public function scopeBySubstances($query, array $substanceIds)
    {
        if (empty($substanceIds)) {
            return $query;
        }

        return $query->whereIn('substance_id', $substanceIds);
    }

    /**
     * Scope to filter by NORMAN relevant substances only (optimized with JOIN)
     */
    public function scopeNormanRelevant($query)
    {
        return $query->join('susdat_substances as subs_norman', 'empodat_main.substance_id', '=', 'subs_norman.id')
            ->where('subs_norman.relevant_to_norman', 1)
            ->select('empodat_main.*');
    }

    /**
     * Scope to filter by concentration indicators
     */
    public function scopeByConcentrationIndicators($query, array $indicatorIds)
    {
        if (empty($indicatorIds)) {
            return $query;
        }

        return $query->whereIn('concentration_indicator_id', $indicatorIds);
    }

    /**
     * Scope to filter by year range
     */
    public function scopeByYearRange($query, $yearFrom = null, $yearTo = null)
    {
        if (! is_null($yearFrom)) {
            $query->where('sampling_date_year', '>=', $yearFrom);
        }

        if (! is_null($yearTo)) {
            $query->where('sampling_date_year', '<=', $yearTo);
        }

        return $query;
    }

    /**
     * Scope to filter by substance categories (optimized with JOIN)
     */
    public function scopeByCategories($query, array $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->join('susdat_substances', 'empodat_main.substance_id', '=', 'susdat_substances.id')
            ->join('susdat_category_substance', 'susdat_substances.id', '=', 'susdat_category_substance.substance_id')
            ->whereIn('susdat_category_substance.category_id', $categoryIds)
            ->select('empodat_main.*')
            ->distinct();
    }

    /**
     * Scope to filter by SLE sources (optimized with JOIN)
     */
    public function scopeBySources($query, array $sourceIds)
    {
        if (empty($sourceIds)) {
            return $query;
        }

        return $query->join('susdat_substances as subs_source', 'empodat_main.substance_id', '=', 'subs_source.id')
            ->join('susdat_source_substance', 'subs_source.id', '=', 'susdat_source_substance.substance_id')
            ->whereIn('susdat_source_substance.source_id', $sourceIds)
            ->select('empodat_main.*')
            ->distinct();
    }

    /**
     * Scope to filter by data source properties. Matching source ids are
     * fetched first (columns on the source or any of its groups) and passed as
     * values, so the planner can use the data_source_id index for rare ones.
     */
    public function scopeByDataSourceFilters($query, array $typeIds = [], array $labIds = [], array $orgIds = [])
    {
        $hasFilters = ! empty($typeIds) || ! empty($labIds) || ! empty($orgIds);

        if (! $hasFilters) {
            return $query;
        }

        $sources = \Illuminate\Support\Facades\DB::table('empodat_data_sources')->select('empodat_data_sources.id');

        if (! empty($typeIds)) {
            $sources->whereIn('empodat_data_sources.type_data_source_id', $typeIds);
        }

        if (! empty($labIds)) {
            $sources->where(function ($inner) use ($labIds) {
                $inner->whereIn('empodat_data_sources.laboratory1_id', $labIds)
                    ->orWhereExists(function ($groups) use ($labIds) {
                        $groups->selectRaw('1')
                            ->from('empodat_data_source_groups')
                            ->whereColumn('empodat_data_source_groups.data_source_id', 'empodat_data_sources.id')
                            ->whereIn('empodat_data_source_groups.laboratory1_id', $labIds);
                    });
            });
        }

        if (! empty($orgIds)) {
            $sources->where(function ($inner) use ($orgIds) {
                $inner->whereIn('empodat_data_sources.organisation_id', $orgIds)
                    ->orWhereExists(function ($groups) use ($orgIds) {
                        $groups->selectRaw('1')
                            ->from('empodat_data_source_groups')
                            ->whereColumn('empodat_data_source_groups.data_source_id', 'empodat_data_sources.id')
                            ->whereIn('empodat_data_source_groups.organisation_id', $orgIds);
                    });
            });
        }

        $sourceIds = $sources->pluck('id')->map(static fn ($id): int => (int) $id)->all();

        return $query->whereIn('empodat_main.data_source_id', $sourceIds);
    }

    /**
     * Scope to filter by analytical method (optimized with JOIN)
     */
    public function scopeByAnalyticalMethods($query, array $methodIds)
    {
        if (empty($methodIds)) {
            return $query;
        }

        return $query->join('empodat_analytical_methods', 'empodat_main.method_id', '=', 'empodat_analytical_methods.id')
            ->whereIn('empodat_analytical_methods.analytical_method_id', $methodIds)
            ->select('empodat_main.*')
            ->distinct();
    }

    /**
     * Scope to filter by quality ratings (optimized with JOIN)
     */
    public function scopeByQualityRatings($query, $ratings)
    {
        if (empty($ratings)) {
            return $query;
        }

        $query = $query->join('empodat_analytical_methods as eam_rating', 'empodat_main.method_id', '=', 'eam_rating.id');

        $query->where(function ($ratingQuery) use ($ratings) {
            foreach ($ratings as $rating) {
                $ratingQuery->orWhere(function ($individualRating) use ($rating) {
                    $individualRating->where('eam_rating.rating', '>=', $rating->min_rating)
                        ->where('eam_rating.rating', '<', $rating->max_rating);
                });
            }
        });

        return $query->select('empodat_main.*')->distinct();
    }

    public function scopeByFiles($query, $fileIds)
    {
        // Convert to array if not already
        if (! is_array($fileIds)) {
            $fileIds = [$fileIds];
        }

        if (empty($fileIds)) {
            return $query;
        }

        // Filter by direct relationship via empodat_main.file_id
        return $query->whereIn('empodat_main.file_id', $fileIds);
    }

    /**
     * Scope to filter records based on user permissions and file protection status
     *
     * Visibility rules (legacy NDS is the reference):
     * 1. Records without a file (file_id IS NULL) are visible to everyone.
     * 2. If files.is_deleted = true, only super_admin can see the data.
     * 3. If files.is_protected = true, only logged-in admins (admin, empodat roles)
     *    OR users assigned to the file's project can see the data.
     * 4. Unprotected, non-deleted files are visible to everyone.
     */
    public function scopeByUserPermissions($query, $user = null)
    {
        if (is_null($user)) {
            $user = auth()->user();
        }

        // Super_admin sees everything. No filter, no JOIN — leave the query
        // untouched so the planner can pick the best index.
        if ($user && $user->hasRole('super_admin')) {
            return $query;
        }

        // Anti-join against the small set of files hidden from this user
        // (≈ 300 of ≈ 750 rows). NOT EXISTS keeps rows with file_id IS NULL
        // (no files row matches) and probes empodat_main_file_id_id_idx
        // instead of scanning empodat_main.
        return $query->whereNotExists(function ($exists) use ($user): void {
            $exists->select(\Illuminate\Support\Facades\DB::raw(1))
                ->from('files as hidden_files')
                ->whereColumn('hidden_files.id', 'empodat_main.file_id')
                ->where(function ($hidden) use ($user): void {
                    $this->applyHiddenFileConditions($hidden, $user);
                });
        });
    }

    /**
     * Adds the conditions that make a `files` row (alias hidden_files) hidden
     * from the given user. Used by scopeByUserPermissions.
     */
    private function applyHiddenFileConditions($hidden, $user): void
    {
        // Deleted files are hidden from every non-super_admin user.
        $hidden->where('hidden_files.is_deleted', true);

        // Admin / empodat role: protected files stay visible.
        if ($user && ($user->hasRole('admin') || $user->hasRole('empodat'))) {
            return;
        }

        // Anonymous: every protected file is hidden.
        if (is_null($user)) {
            $hidden->orWhere('hidden_files.is_protected', true);

            return;
        }

        // Regular authenticated user: protected files are hidden unless
        // assigned to a project the user belongs to.
        $userId = $user->id;
        $hidden->orWhere(function ($protected) use ($userId): void {
            $protected->where('hidden_files.is_protected', true)
                ->whereNotExists(function ($member) use ($userId): void {
                    $member->select(\Illuminate\Support\Facades\DB::raw(1))
                        ->from('project_user')
                        ->whereColumn('project_user.project_id', 'hidden_files.project_id')
                        ->where('project_user.user_id', $userId);
                });
        });
    }

    /**
     * Scope to eager load all search-related relationships
     */
    public function scopeWithSearchRelations($query)
    {
        return $query->with([
            'country',
            'file',
            'concentrationIndicator',
            'substance',
            'matrix',
            'station.country',
            'analyticalMethod',
            'dataSource',
            'files',
            'minor',
            'matrixAir',
            'matrixBiota',
            'matrixSediments',
            'matrixSewageSludge',
            'matrixSoil',
            'matrixSuspendedMatter',
            'matrixWater',
        ]);
    }

    /**
     * Get the formatted concentration value with indicator.
     *
     * @return string|null
     */
    public function getFormattedConcentrationAttribute()
    {
        if ($this->concentration_value === null) {
            return null;
        }

        $indicator = $this->concentrationIndicator ? $this->concentrationIndicator->symbol : '';

        return $indicator.number_format($this->concentration_value, 4);
    }

    /**
     * Get the formatted sampling date.
     *
     * The legacy v1 dataset stores '0000-00-00 00:00:00' as a "no real date"
     * sentinel in `empodat_minor.sampling_date`. The model casts that column
     * to `datetime`, which makes Carbon emit '-000001-11-30...' for the
     * sentinel — visible to users as a junk date. Year 0 / sentinel values
     * are treated as "no date" here, falling back to the year-only field.
     *
     * @return string
     */
    public function getFormattedSamplingDateAttribute()
    {
        $raw = $this->minor?->sampling_date;
        if ($raw !== null && ! $this->isZeroSamplingDate($raw)) {
            return Carbon::parse($raw)->format('Y-m-d');
        }

        $assembled = $this->samplingDateFromParts();
        if ($assembled !== null) {
            return $assembled;
        }

        if ($this->sampling_date_year && (int) $this->sampling_date_year > 0) {
            return (string) $this->sampling_date_year;
        }

        return 'N/A';
    }

    /**
     * Assemble `Y-m-d` from the legacy date parts when the `sampling_date`
     * datetime is absent.
     *
     * Above `empodat_minor.id` ≈ 20 000 000 the legacy system left
     * `sampling_date` empty and kept the date only as year/month/day parts
     * (issue #22). `empodat_minor.sampling_date_m` / `_d` are the destination
     * for those parts; they are empty until the external migrator populates
     * them, and while they are empty this returns null and the caller falls
     * back to the year alone, exactly as before.
     */
    private function samplingDateFromParts(): ?string
    {
        $year = (int) ($this->sampling_date_year ?? 0);
        $month = (int) ($this->minor?->sampling_date_m ?? 0);
        $day = (int) ($this->minor?->sampling_date_d ?? 0);

        if ($year <= 0 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return null;
        }

        if (! checkdate($month, $day, $year)) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $year, $month, $day);
    }

    /**
     * True when the given sampling_date value represents the legacy
     * "no real date" sentinel — either '0000-00-00 00:00:00' (raw varchar)
     * or a Carbon instance whose year is <= 0.
     */
    private function isZeroSamplingDate(mixed $value): bool
    {
        if ($value instanceof \DateTimeInterface) {
            return (int) $value->format('Y') <= 0;
        }
        $str = trim((string) $value);

        return $str === '' || str_starts_with($str, '0000-00-00');
    }
}
