<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // optional
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
        'is_active',
    ];

    protected $sortable = [
        'name',
        'slug',
        'sort_order',
        'created_at',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function products()
    {
        return $this->hasMany(Product::class);
    }

/* -------------------- Scopes -------------------- */
    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? false, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        });
    }

    public function scopeActive($query, bool $onlyActive = true)
    {
        return $onlyActive ? $query->where('is_active', true) : $query;
    }

    public function scopeOrdered($query)
    {
        // Primary by sort_order, then by name as a stable tiebreaker
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /* -------------------- Route binding -------------------- */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /* -------------------- Slugging -------------------- */
    protected static function booted()
    {
        static::creating(function ($model) {
            if (blank($model->slug) && filled($model->name)) {
                $model->slug = Str::slug($model->name);
            }
            $model->slug = static::uniqueSlug($model->slug);
        });

        static::updating(function ($model) {
            // If you want slug to follow name, handle here (currently fixed unless manually changed)
            $model->slug = static::uniqueSlug($model->slug, $model->getKey());
        });
    }

    protected static function uniqueSlug(string $base, $ignoreId = null): string
    {
        $slug = Str::slug($base ?: Str::random(6));
        $original = $slug;
        $i = 2;

        while (static::query()
                ->when($ignoreId, fn($q) => $q->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()) {
            $slug = "{$original}-{$i}";
            $i++;
        }

        return $slug;
    }


}
