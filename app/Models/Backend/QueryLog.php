<?php

namespace App\Models\Backend;

use Illuminate\Database\Eloquent\Model;

class QueryLog extends Model
{
    //
    protected $fillable = ['content', 'query', 'user_id'];
}
