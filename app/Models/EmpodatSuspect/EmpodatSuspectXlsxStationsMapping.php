<?php

namespace App\Models\EmpodatSuspect;

use App\Models\Empodat\EmpodatStation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpodatSuspectXlsxStationsMapping extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'empodat_suspect_xlsx_stations_mapping';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'station_id',
        'xlsx_name',
        'count',
        'ids',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'station_id' => 'integer',
        'count' => 'integer',
    ];

    /**
     * Get the station associated with this mapping.
     */
    public function station()
    {
        return $this->belongsTo(EmpodatStation::class, 'station_id');
    }

    /**
     * Get the empodat_suspect_main records associated with this mapping.
     */
    public function suspectRecords()
    {
        return $this->hasMany(EmpodatSuspectMain::class, 'xlsx_station_mapping_id');
    }
}
