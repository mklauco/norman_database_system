<?php

namespace App\Models;

use App\Models\Backend\Project;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens;
    use HasFactory, Notifiable;
    use HasRoles;

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
        'organisation_id',
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
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the user's formatted name with salutation if available.
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
        return $this->belongsTo(\App\Models\List\DataSourceOrganisation::class, 'organisation_id');
    }

    /**
     * Get the country that belongs to the user.
     */
    public function country()
    {
        return $this->belongsTo(\App\Models\List\Country::class, 'country_id');
    }

    /**
     * Get the export downloads for the user.
     */
    public function exportDownloads(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Backend\ExportDownload::class);
    }

    /**
     * Scope a query to only include active users.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
