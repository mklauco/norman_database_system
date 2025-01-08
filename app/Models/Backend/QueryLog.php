<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;

class QueryLog extends Model
{
    //
    protected $fillable = ['content', 'query', 'user_id'];
    
    public function users()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // created_at in Europe/Berlin timezone
    public function getCreatedAtAttribute($value)
    {
        return \Carbon\Carbon::parse($value)->timezone('Europe/Berlin')->format('Y-m-d G:i:s');
    }
    
    public function getFormattedQueryAttribute()
    {
        
        $formattedQuery = Str::of($this->query)
        ->lower() // Convert the entire query to lowercase
        ->replace('select', 'SELECT<br>')
        ->replace('from', '<br>FROM<br>')
        ->replace('inner join', '<br>INNER JOIN<br>')
        ->replace('left join', '<br>LEFT JOIN<br>')
        ->replace(' where ', '<br>WHERE<br>')
        ->replace(' and ', 'AND<br>')
        ->replace(' or ', 'OR<br>')
        ->replace('order by', 'ORDER BY<br>')
        ->replace('group by', 'GROUP BY<br>')
        ->replace(' limit ', 'LIMIT<br>')
        ->replace('", ', '",<br>')
        ->replace('*, ', '*,<br>')
        ->replace(' as ', ' AS ')
        ->replace(' on ', ' ON ')
        ->replace(' in ', ' IN ')
        ->replace(' = ', '&nbsp;=&nbsp;')
        // apply tailwind classes
        ->replace('SELECT<br>', '<span class="text-purple-600">SELECT</span><br>')
        ->replace('FROM<br>', '<span class="text-purple-600">FROM</span><br>')
        ->replace('INNER JOIN<br>', '<span class="text-purple-600">INNER JOIN</span><br>')
        ->replace('LEFT JOIN<br>', '<span class="text-purple-600">LEFT JOIN</span><br>')
        ->replace('WHERE<br>', '<span class="text-purple-600">WHERE</span><br>')
        ->replace('AND<br>', '<span class="text-purple-600">AND</span><br>')
        ->replace('OR<br>', '<span class="text-purple-600">OR</span><br>')
        ->replace('ORDER BY<br>', '<span class="text-purple-600">ORDER BY</span><br>')
        ->replace('GROUP BY<br>', '<span class="text-purple-600">GROUP BY</span><br>')
        ->replace('LIMIT<br>', '<span class="text-purple-600">LIMIT</span><br>')
        ->replace(' IN ', '<span class="text-teal-600"> IN </span>')
        ->replace(' ON ', '<span class="text-teal-600"> ON </span>')
        ->replace('(', '<span class="text-orange-800">(</span>')
        ->replace(')', '<span class="text-orange-800">)</span>')
        ->toString();
        // $formattedQuery = $this->query;
        
        return $formattedQuery;
    }
}
