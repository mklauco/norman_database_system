<?php

declare(strict_types=1);

namespace App\Models\Empodat;

use App\Models\List\DataSourceLaboratory;
use App\Models\List\DataSourceOrganisation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmpodatDataSourceGroup extends Model
{
    protected $table = 'empodat_data_source_groups';

    protected $fillable = [
        'data_source_id',
        'position',
        'organisation_id',
        'laboratory1_id',
        'laboratory2_id',
        'contact_id',
    ];

    public function dataSource(): BelongsTo
    {
        return $this->belongsTo(DataSources::class, 'data_source_id');
    }

    public function organisation(): BelongsTo
    {
        return $this->belongsTo(DataSourceOrganisation::class, 'organisation_id');
    }

    public function laboratory1(): BelongsTo
    {
        return $this->belongsTo(DataSourceLaboratory::class, 'laboratory1_id');
    }

    public function laboratory2(): BelongsTo
    {
        return $this->belongsTo(DataSourceLaboratory::class, 'laboratory2_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(EmpodatContact::class, 'contact_id');
    }
}
