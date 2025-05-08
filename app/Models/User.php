<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Backend\Project;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;
    use HasRoles;
    use HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'username',
        'salutation',
        'organisation',
        'organisation_id',
        'organisation_other',
        'country',
        'country_id',
        'active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'organisation_id' => 'integer',
            'country_id' => 'integer',
        ];
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's formatted name with salutation if available.
     *
     * @return string
     */
    public function getFormattedNameAttribute(): string
    {
        if ($this->salutation) {
            return "{$this->salutation} {$this->first_name} {$this->last_name}";
        }
        
        return $this->getFullNameAttribute();
    }

    /**
     * Get projects that belong to the user via the ProjectUser pivot table.
     */
    public function projects()
    {
        return $this->belongsToMany(Project::class);
    }

    /**
     * Get the organisation that belongs to the user.
     */
    public function organisation()
    {
        // Assuming you have an Organisation model
        return $this->belongsTo(Organisation::class);
    }

    /**
     * Get the country that belongs to the user.
     */
    public function country()
    {
        // Assuming you have a Country model
        return $this->belongsTo(Country::class);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}