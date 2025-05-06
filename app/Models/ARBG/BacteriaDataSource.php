<?php

namespace App\Models\ARBG;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BacteriaDataSource extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'arbg_bacteria_data_source';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'source_id';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'type_of_data_source_id',
        'type_of_monitoring_id',
        'type_of_monitoring_other',
        'title_of_project',
        'organisation',
        'e_mail',
        'laboratory',
        'laboratory_id',
        'references_literature_1',
        'references_literature_2',
        'author',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'type_of_data_source_id' => 'integer',
        'type_of_monitoring_id' => 'integer',
    ];

    /**
     * Get the type of data source record associated with the data source.
     */
    public function typeOfDataSource()
    {
        return $this->belongsTo(DataTypeOfDataSource::class, 'type_of_data_source_id', 'id');
    }

    /**
     * Get the type of monitoring record associated with the data source.
     */
    public function typeOfMonitoring()
    {
        return $this->belongsTo(DataTypeOfMonitoring::class, 'type_of_monitoring_id', 'id');
    }

    /**
     * Get the bacteria records associated with this data source.
     */
    public function bacteriaRecords()
    {
        return $this->hasMany(BacteriaMain::class, 'source_id', 'source_id');
    }

    /**
     * Get a formatted reference list
     * 
     * @return string|null
     */
    public function getFormattedReferencesAttribute()
    {
        $references = [];
        
        if (!empty($this->references_literature_1)) {
            $references[] = $this->references_literature_1;
        }
        
        if (!empty($this->references_literature_2)) {
            $references[] = $this->references_literature_2;
        }
        
        return !empty($references) ? implode("\n", $references) : null;
    }

    /**
     * Get a formatted contact info
     * 
     * @return string|null
     */
    public function getContactInfoAttribute()
    {
        $parts = [];
        
        if (!empty($this->organisation)) {
            $parts[] = $this->organisation;
        }
        
        if (!empty($this->laboratory)) {
            $parts[] = $this->laboratory;
        }
        
        if (!empty($this->author)) {
            $parts[] = $this->author;
        }
        
        if (!empty($this->e_mail)) {
            $parts[] = $this->e_mail;
        }
        
        return !empty($parts) ? implode(", ", $parts) : null;
    }
}