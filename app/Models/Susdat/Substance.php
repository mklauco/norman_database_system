<?php

namespace App\Models\Susdat;

use App\Models\SLE\SuspectListExchangeSource;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

class Substance extends Model implements Auditable
{
    use AuditableTrait;
    use HasFactory;
    use SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'susdat_substances';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'name_dashboard',
        'name_chemspider',
        'name_iupac',
        'cas_number',
        'smiles',
        'smiles_dashboard',
        'stdinchi',
        'stdinchikey',
        'pubchem_cid',
        'chemspider_id',
        'dtxid',
        'molecular_formula',
        'mass_iso',
        'average_mass',
        'metadata_synonyms',
        'metadata_cas',
        'metadata_ms_ready',
        'metadata_general',
        'added_by',
        'canonical_id', // ADD THIS
        'status', // ADD THIS
        'merged_at', // ADD THIS
        'merged_by', // ADD THIS
        'merge_reason', // ADD THIS
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'mass_iso' => 'float',
        'average_mass' => 'decimal:8',
        'metadata_synonyms' => 'array',
        'metadata_cas' => 'array',
        'metadata_ms_ready' => 'array',
        'metadata_general' => 'array',
        'added_by' => 'integer',
        'canonical_id' => 'integer', // ADD THIS
        'merged_at' => 'datetime', // ADD THIS
        'merged_by' => 'integer', // ADD THIS
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array
     */
    protected $appends = ['prefixed_code'];

    public static function boot()
    {
        parent::boot();

        // Validate canonical_id references active substance
        static::saving(function ($substance) {
            if ($substance->canonical_id && $substance->isDirty('canonical_id')) {
                $canonical = self::find($substance->canonical_id);
                if (! $canonical || $canonical->status !== 'active') {
                    throw new \InvalidArgumentException('canonical_id must reference an active substance');
                }
            }
        });
    }

    /**
     * Get the user who added this substance.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the categories associated with this substance.
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'susdat_category_substance', 'substance_id', 'category_id')->withTimestamps();
    }

    /**
     * Get the three-level use categories associated with this substance.
     *
     * A substance is linked to every node on its path, so filtering on a
     * parent category needs no recursive query.
     */
    public function useCategories()
    {
        return $this->belongsToMany(UseCategory::class, 'susdat_substance_use_category', 'substance_id', 'use_category_id')->withTimestamps();
    }

    /**
     * Get the sources associated with this substance.
     */
    public function sources()
    {
        return $this->belongsToMany(SuspectListExchangeSource::class, 'susdat_source_substance', 'substance_id', 'source_id');
    }

    /**
     * Get the prefixed code attribute.
     *
     * @return string
     */
    public function getPrefixedCodeAttribute()
    {
        return 'NS'.$this->code;
    }

    /**
     * Store code without prefix.
     *
     * @param  string  $code
     * @return bool
     */
    public function storeCodeWithoutPrefix($code)
    {
        $this->code = str_replace('NS', '', $code);

        return $this->save();
    }

    /**
     * Get the primary name for display purposes.
     * Prioritizes dashboard name over regular name.
     *
     * @return string|null
     */
    public function getDisplayNameAttribute()
    {
        return $this->name_dashboard ?: $this->name;
    }

    /**
     * Get formatted CAS number for display.
     *
     * @return string|null
     */
    public function getFormattedCasAttribute()
    {
        if (! $this->cas_number) {
            return null;
        }

        // If CAS number doesn't contain dashes, format it
        if (! str_contains($this->cas_number, '-')) {
            $cas = $this->cas_number;
            if (strlen($cas) >= 3) {
                // Format as XXXXX-XX-X
                return substr($cas, 0, -3).'-'.substr($cas, -3, 2).'-'.substr($cas, -1);
            }
        }

        return $this->cas_number;
    }

    /**
     * Get all synonyms from metadata.
     *
     * @return array
     */
    public function getAllSynonymsAttribute()
    {
        $synonyms = [];

        if ($this->metadata_synonyms && is_array($this->metadata_synonyms)) {
            $synonyms = array_merge($synonyms, $this->metadata_synonyms);
        }

        // Add other name fields as synonyms if they exist and are different
        $nameFields = ['name', 'name_dashboard', 'name_chemspider', 'name_iupac'];
        foreach ($nameFields as $field) {
            if ($this->$field && ! in_array($this->$field, $synonyms)) {
                $synonyms[] = $this->$field;
            }
        }

        return array_unique(array_filter($synonyms));
    }

    /**
     * Check if substance has molecular structure data.
     *
     * @return bool
     */
    public function getHasStructureDataAttribute()
    {
        return ! empty($this->smiles) || ! empty($this->stdinchi) || ! empty($this->molecular_formula);
    }

    /**
     * Get the structure image URL for this substance.
     */
    public function getStructureImageUrlAttribute(): ?string
    {
        $filename = $this->prefixed_code.'.png';
        $path = 'substances/'.$filename;

        if (\Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        return null;
    }

    /**
     * Check if a structure image exists for this substance.
     */
    public function getHasStructureImageAttribute(): bool
    {
        return $this->structure_image_url !== null;
    }

    /**
     * Get external database links.
     *
     * @return array
     */
    public function getExternalLinksAttribute()
    {
        $links = [];

        if ($this->pubchem_cid) {
            $links['pubchem'] = "https://pubchem.ncbi.nlm.nih.gov/compound/{$this->pubchem_cid}";
        }

        if ($this->chemspider_id) {
            $links['chemspider'] = "https://www.chemspider.com/Chemical-Structure.{$this->chemspider_id}.html";
        }

        if ($this->dtxid) {
            $links['comptox'] = "https://comptox.epa.gov/dashboard/chemical/details/{$this->dtxid}";
        }

        return $links;
    }

    /**
     * Scope to search substances by name or CAS number.
     */
    public function scopeSearchByName($query, $searchTerm)
    {
        return $query->where(function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', "%{$searchTerm}%")
                ->orWhere('name_dashboard', 'LIKE', "%{$searchTerm}%")
                ->orWhere('name_chemspider', 'LIKE', "%{$searchTerm}%")
                ->orWhere('name_iupac', 'LIKE', "%{$searchTerm}%")
                ->orWhere('cas_number', 'LIKE', "%{$searchTerm}%")
                ->orWhere('code', 'LIKE', "%{$searchTerm}%");
        });
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, $categoryIds)
    {
        if (empty($categoryIds)) {
            return $query;
        }

        return $query->whereHas('categories', function ($q) use ($categoryIds) {
            $q->whereIn('susdat_categories.id', $categoryIds);
        });
    }

    /**
     * Scope to filter substances with structure data.
     */
    public function scopeWithStructureData($query)
    {
        return $query->where(function ($q) {
            $q->whereNotNull('smiles')->orWhereNotNull('stdinchi')->orWhereNotNull('molecular_formula');
        });
    }

    /**
     * Get the canonical substance this points to.
     */
    public function canonical()
    {
        return $this->belongsTo(Substance::class, 'canonical_id');
    }

    /**
     * Get substances that point to this as canonical.
     */
    public function deprecatedVersions()
    {
        return $this->hasMany(Substance::class, 'canonical_id');
    }

    /**
     * Get the user who merged this substance.
     */
    public function mergedBy()
    {
        return $this->belongsTo(User::class, 'merged_by');
    }

    /**
     * Get the canonical version of this substance.
     *
     * @return Substance
     */
    public function getCanonicalSubstanceAttribute()
    {
        if ($this->canonical_id === null) {
            return $this; // This IS the canonical version
        }

        // Follow the chain to find canonical (handles multiple levels)
        return $this->canonical->canonicalSubstance;
    }

    /**
     * Get the resolved canonical ID.
     *
     * @return int
     */
    public function getCanonicalIdResolvedAttribute()
    {
        return $this->canonical_id ?? $this->id;
    }

    /**
     * Scope to only show active (canonical) substances.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to exclude deprecated/merged substances.
     */
    public function scopeCanonicalOnly($query)
    {
        return $query->whereNull('canonical_id');
    }

    /**
     * Check if this substance is the canonical version.
     *
     * @return bool
     */
    public function getIsCanonicalAttribute()
    {
        return $this->canonical_id === null && $this->status === 'active';
    }

    /**
     * Check if substance has been merged/deprecated.
     *
     * @return bool
     */
    public function getIsDeprecatedAttribute()
    {
        return in_array($this->status, ['deprecated', 'merged']);
    }
}
