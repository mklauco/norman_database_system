<?php

namespace App\Models\MariaDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DCTList extends Model
{
    use HasFactory;

    protected $connection = 'norman-mariadb';

    protected $table = 'dct_list';
}
