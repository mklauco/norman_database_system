<?php

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Model;
use App\Models\SLE\SuspectListExchangeSource;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Substance extends Model
{
    use HasFactory;

    protected $table = 'susdat_substances';

    protected $fillable = [
        'id',
        'code',
        'name',
        'cas_number',
        'smiles',
        'stdinchikey',
        'dtxid',
        'pubchem_cid',
        'chemspider_id',
        'molecular_formula',
        'mass_iso',
    ];

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'susdat_category_substance', 'substance_id', 'category_id');
    }

    public function sources()
    {
        return $this->belongsToMany(SuspectListExchangeSource::class, 'susdat_source_substance', 'substance_id', 'source_id');
    }

    protected $appends = ['prefixed_code'];

    public function getPrefixedCodeAttribute()
    {
        return 'NS' . $this->code;
    }

    public function storeCodeWithoutPrefix($code)
    {
        $this->code = str_replace('NS', '', $code);
        $this->save();
    }
}
