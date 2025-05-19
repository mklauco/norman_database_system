<?php

namespace App\Models\Backend;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Project extends Model
{
    use HasFactory;
    
    /**
    * The attributes that are mass assignable.
    *
    * @var array<int, string>
    */
    protected $fillable = [
        'name',
        'abbreviation',
        'description',
    ];
    
    /**
    * Get all users for the project.
    */
    public function users()
    {
        return $this->belongsToMany(User::class, 'project_user')
        ->withTimestamps();
    }
    
    public function files(): BelongsToMany
    {
        return $this->belongsToMany(File::class, 'file_project')
        ->withPivot('notes')
        ->withTimestamps();
    }
}