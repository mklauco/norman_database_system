<?php

namespace App\Models\MariaDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DCTAnalysis extends Model
{
    use HasFactory;

    protected $connection = 'norman-mariadb';

    protected $table = 'dct_analysis';
}
