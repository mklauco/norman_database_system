<?php

namespace App\Models\Ecotox;

use Illuminate\Database\Eloquent\Model;

class EcotoxComparativeTableInputValues extends Model
{
    protected $table = 'ecotox_comparative_table_input_values';

    protected $fillable = ['val_id', 'column_id', 'column_name', 'input_value'];
}
