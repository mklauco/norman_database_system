<?php

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GenesMain extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_genes_main';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'sample_matrix_id',
        'sample_matrix_other',
        'gene_name',
        'gene_description',
        'gene_family',
        'associated_phenotype',
        'monogenic_phenotype',
        'forward_primer',
        'reverse_primer',
        'dye_probe_based',
        'probe_sequence',
        'plasmid_genome_standards',
        'multi_drug_resistance_phenotype',
        'genetic_marker',
        'genetic_marker_specify',
        'common_bacterial_host',
        'concentration_data_id',
        'concentration_id',
        'concentration_abundance_per_ml',
        'concentration_abundance_per_ng',
        'concentration_abundance',
        'prevalence',
        'sampling_date_day',
        'sampling_date_month',
        'sampling_date_year',
        'sampling_date_hour',
        'sampling_date_minute',
        'method_id',
        'source_id',
        'coordinate_id',
        'remark',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'sample_matrix_id' => 'integer',
        'concentration_data_id' => 'integer',
        'concentration_id' => 'integer',
        'sampling_date_day' => 'integer',
        'sampling_date_month' => 'integer',
        'sampling_date_year' => 'integer',
        'method_id' => 'integer',
        'source_id' => 'integer',
        'coordinate_id' => 'integer',
    ];

    /**
     * Get the sample matrix record associated with the gene record.
     */
    public function sampleMatrix()
    {
        return $this->belongsTo(DataSampleMatrix::class, 'sample_matrix_id', 'sample_matrix_id');
    }

    /**
     * Get the concentration data record associated with the gene record.
     */
    public function concentrationData()
    {
        return $this->belongsTo(DataConcentrationData::class, 'concentration_data_id', 'concentration_data_id');
    }

    /**
     * Get the coordinate record associated with the gene record.
     */
    public function coordinate()
    {
        return $this->belongsTo(DataCoordinate::class, 'coordinate_id', 'coordinate_id');
    }

    /**
     * Get the method record associated with the gene record.
     */
    public function method()
    {
        return $this->belongsTo(DataMethod::class, 'method_id', 'method_id');
    }

    /**
     * Get the source record associated with the gene record.
     */
    public function source()
    {
        return $this->belongsTo(DataSource::class, 'source_id', 'source_id');
    }

    /**
     * Get formatted sampling date
     * 
     * @return string|null
     */
    public function getSamplingDateAttribute()
    {
        if (!$this->sampling_date_year || !$this->sampling_date_month || !$this->sampling_date_day) {
            return null;
        }
        
        return $this->sampling_date_year . '-' . 
               str_pad($this->sampling_date_month, 2, '0', STR_PAD_LEFT) . '-' . 
               str_pad($this->sampling_date_day, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted sampling time
     * 
     * @return string|null
     */
    public function getSamplingTimeAttribute()
    {
        if (!$this->sampling_date_hour && !$this->sampling_date_minute) {
            return null;
        }
        
        return ($this->sampling_date_hour ?? '00') . ':' . ($this->sampling_date_minute ?? '00');
    }

    /**
     * Get full gene name with description
     * 
     * @return string
     */
    public function getFullGeneNameAttribute()
    {
        if (!$this->gene_description) {
            return $this->gene_name;
        }
        
        return $this->gene_name . ' - ' . $this->gene_description;
    }
}