<?php

declare(strict_types=1);

namespace App\Models\Susdat;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A node in the three-level SusDat use-category tree mirrored from the legacy
 * server: level 1 category, level 2 subcategory, level 3 subcategory2.
 */
class UseCategory extends Model
{
    use HasFactory;

    protected $table = 'susdat_use_categories';

    protected $fillable = [
        'parent_id',
        'level',
        'legacy_id',
        'name',
        'abbreviation',
        'path',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'legacy_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function substances(): BelongsToMany
    {
        return $this->belongsToMany(
            Substance::class,
            'susdat_substance_use_category',
            'use_category_id',
            'substance_id'
        );
    }

    /**
     * Top-level categories only, in display order.
     */
    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id')->orderBy('sort_order');
    }

    public function scopeLevel($query, int $level)
    {
        return $query->where('level', $level);
    }

    /**
     * Every node beneath this one, at any depth.
     */
    public function scopeDescendantsOf($query, self $category)
    {
        return $query->where('path', 'like', $category->path.'/%');
    }

    public function getNameAbbreviationAttribute(): string
    {
        return $this->abbreviation === null
            ? $this->name
            : $this->name.' ('.$this->abbreviation.')';
    }
}
