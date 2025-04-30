<?php

namespace App\Models\Prioritisation;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonitoringScarce extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'prioritisation_monitoring_scarce';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'pri_nr';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'pri_use_for_priority_list',
        'pri_substance',
        'pri_cas_no',
        'pri_no_sites_new',
        'pri_no_sites_where_mecsite_pnec_new',
        'pri_mec95_new',
        'pri_mecsite_max_new',
        'pri_loq_min',
        'pri_cat',
        'pri_lowest_pnec',
        'pri_pnec_type',
        'pri_reference_pnec',
        'pri_max_exceedance',
        'pri_extent_of_exceedence',
        'pri_score_eoe',
        'pri_score_foe',
        'pri_score_total',
        'pri_loq_exceedance',
        'pri_substance_new',
        'pri_no_of_sites_mecsite_pnec_new',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'pri_no_sites_new' => 'integer',
        'pri_no_sites_where_mecsite_pnec_new' => 'integer',
        'pri_mec95_new' => 'float',
        'pri_mecsite_max_new' => 'float',
        'pri_loq_min' => 'float',
        'pri_cat' => 'integer',
        'pri_lowest_pnec' => 'float',
        'pri_max_exceedance' => 'float',
        'pri_extent_of_exceedence' => 'float',
        'pri_score_eoe' => 'float',
        'pri_score_foe' => 'float',
        'pri_score_total' => 'float',
        'pri_loq_exceedance' => 'float',
        'pri_no_of_sites_mecsite_pnec_new' => 'integer',
    ];

    /**
     * Get formatted category name based on pri_cat
     * 
     * @return string
     */
    public function getCategoryNameAttribute()
    {
        $categories = [
            1 => 'Category 1',
            2 => 'Category 2',
            3 => 'Category 3',
            4 => 'Category 4',
        ];

        return $categories[$this->pri_cat] ?? 'Unknown';
    }
}