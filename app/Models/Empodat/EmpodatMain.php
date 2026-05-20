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
     * Scope to filter by data source properties (optimized with JOIN)
     */
    public function scopeByDataSourceFilters($query, array $typeIds = [], array $labIds = [], array $orgIds = [])
    {
        $hasFilters = ! empty($typeIds) || ! empty($labIds) || ! empty($orgIds);

        if (! $hasFilters) {
            return $query;
        }

        $query = $query->join('empodat_data_sources', 'empodat_main.data_source_id', '=', 'empodat_data_sources.id');

        if (! empty($typeIds)) {
            $query->whereIn('empodat_data_sources.type_data_source_id', $typeIds);
        }

        if (! empty($labIds)) {
            $query->whereIn('empodat_data_sources.laboratory1_id', $labIds);
        }

        if (! empty($orgIds)) {
            $query->whereIn('empodat_data_sources.organisation_id', $orgIds);
        }

        return $query->select('empodat_main.*')->distinct();
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
     * Protection rules:
     * 1. If files.is_deleted = true, only super_admin can see the data
     * 2. If files.is_protected = true, only logged-in admins (admin, super_admin, empodat roles)
     *    OR users assigned to the file's project can see the data
     * 3. Unprotected, non-deleted files are visible to everyone
     */
    public function scopeByUserPermissions($query, $user = null)
    {
        if (is_null($user)) {
            $user = auth()->user();
        }

        // Super_admin sees everything (including rows with NULL file_id).
        // No filter, no JOIN — leave the query untouched so the planner can
        // pick the best index for whatever other filters are applied.
        if ($user && $user->hasRole('super_admin')) {
            return $query;
        }

        // For every other role, resolve the set of permitted file ids in a
        // small sub-query against `files` (≈ hundreds of rows) and constrain
        // `empodat_main.file_id` to that set. This avoids joining 100M+ rows
        // of `empodat_main` to `files` just to apply permission filters.
        //
        // Side effect: rows with `file_id IS NULL` are excluded, matching the
        // original INNER JOIN behaviour for non-super_admin users.
        return $query->whereIn(
            'empodat_main.file_id',
            $this->permittedFileIdsSubquery($user),
        );
    }

    /**
     * Returns a sub-query Builder yielding the `files.id` values the given
     * user is allowed to see. Used by scopeByUserPermissions.
     */
    private function permittedFileIdsSubquery($user)
    {
        $sub = \Illuminate\Support\Facades\DB::table('files')->select('id');

        // Anonymous: only unprotected, non-deleted files.
        if (is_null($user)) {
            return $sub
                ->where('is_protected', false)
                ->where(function ($q) {
                    $q->where('is_deleted', false)->orWhereNull('is_deleted');
                });
        }

        // Admin / empodat role: every non-deleted file (protected or not).
        if ($user->hasRole('admin') || $user->hasRole('empodat')) {
            return $sub->where(function ($q) {
                $q->where('is_deleted', false)->orWhereNull('is_deleted');
            });
        }

        // Regular authenticated user: non-deleted files that are either
        // unprotected OR assigned to a project the user belongs to.
        $userId = $user->id;

        return $sub
            ->where(function ($q) {
                $q->where('is_deleted', false)->orWhereNull('is_deleted');
            })
            ->where(function ($q) use ($userId) {
                $q->where('is_protected', false)
                    ->orWhereExists(function ($exists) use ($userId) {
                        $exists->select(\Illuminate\Support\Facades\DB::raw(1))
                            ->from('project_user')
                            ->whereColumn('project_user.project_id', 'files.project_id')
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
     * @return string
     */
    public function getFormattedSamplingDateAttribute()
    {
        // First try to get the full date from minor relationship
        if ($this->minor && $this->minor->sampling_date) {
            return Carbon::parse($this->minor->sampling_date)->format('Y-m-d');
        }

        // Fallback to the year if no minor date available
        if ($this->sampling_date_year) {
            return (string) $this->sampling_date_year;
        }

        // Final fallback
        return 'N/A';
    }
}
