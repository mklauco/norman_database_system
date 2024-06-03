<?php

namespace App\Models\MariaDB;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Susdat extends Model
{
    use HasFactory;

    protected $connection = 'norman-mariadb';

    protected $table = 'susdat';
}
