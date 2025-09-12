<?php

namespace App\Models;

use Kyslik\ColumnSortable\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'name',
        'brand',
        'sku',
        'category_id',
        'track_stock',
        'low_stock_threshold',
        'is_active',
    ];

    protected $casts = [
        'track_stock'        => 'boolean',
        'low_stock_threshold'=> 'integer',
        'is_active'          => 'boolean',
    ];

    // Columns available to Kyslik sorting
    public $sortable = [
        'name',
        'brand',
        'sku',
        'category_id',
        'is_active',
        'created_at',
    ];
    /* -------------------- Relationships -------------------- */

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    // Link to order lines (helpful for reports)
    public function orderDetails()
    {
        return $this->hasMany(OrderDetail::class);
    }

    /* -------------------- Scopes -------------------- */

    public function scopeActive($query, bool $onlyActive = true)
    {
        return $onlyActive ? $query->where('is_active', true) : $query;
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('brand', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function scopeBrand($query, ?string $brand)
    {
        if (!$brand) return $query;
        return $query->where('brand', $brand);
    }

    public function scopeCategoryId($query, $categoryId)
    {
        if (!$categoryId) return $query;
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        // Stable catalog ordering
        return $query->orderBy('name')->orderBy('brand');
    }

    /* -------------------- Accessors / Virtuals -------------------- */

    // Sum of all variant stocks (fast enough for dashboards; for big data use eager-loaded sums)
    public function getTotalStockAttribute(): int
    {
        // If already loaded, avoid extra query
        if ($this->relationLoaded('variants')) {
            return (int) $this->variants->sum('stock');
        }
        return (int) $this->variants()->sum('stock');
    }

    // Minimum selling price among variants (null if no variants)
    public function getMinPriceAttribute(): ?string
    {
        if ($this->relationLoaded('variants')) {
            $min = $this->variants->min('price');
            return is_null($min) ? null : number_format((float) $min, 2, '.', '');
        }
        $min = $this->variants()->min('price');
        return is_null($min) ? null : number_format((float) $min, 2, '.', '');
    }

    public function getMaxPriceAttribute(): ?string
    {
        if ($this->relationLoaded('variants')) {
            $max = $this->variants->max('price');
            return is_null($max) ? null : number_format((float) $max, 2, '.', '');
        }
        $max = $this->variants()->max('price');
        return is_null($max) ? null : number_format((float) $max, 2, '.', '');
    }

    public function getHasVariantsAttribute(): bool
    {
        if ($this->relationLoaded('variants')) return $this->variants->isNotEmpty();
        return $this->variants()->exists();
    }

    /* -------------------- Boot (optional SKU autogen) -------------------- */

    protected static function booted()
    {
        static::creating(function (Product $p) {
            if (blank($p->sku)) {
                $p->sku = static::uniqueSku();
            }

        });
    }

    protected static function uniqueSku(): string
    {
        do {
            $code = 'PRD-' . strtoupper(Str::random(8));
        } while (static::where('sku', $code)->exists());

        return $code;
    }
  /* -------------------- Helpers -------------------- */

    /**
     * Generate a unique EAN-13 barcode (numeric) for scanners/printers.
     */
    protected static function uniqueEan13(): string
    {
        do {
            $base12 = static::randomDigits(12);      // 12-digit payload
            $check  = static::ean13Checksum($base12);
            $code   = $base12 . $check;              // 13 digits total
        } while (static::where('barcode', $code)->exists());

        return $code;
    }

    /** Return n random digits as a string (no leading zero issues). */
    protected static function randomDigits(int $n): string
    {
        // Mix time + random to reduce collisions
        $seed = now()->format('ymdHis') . random_int(100000, 999999);
        $digits = preg_replace('/\D/', '', (string) $seed);
        // Pad/repeat to reach length n
        while (strlen($digits) < $n) {
            $digits .= (string) random_int(0, 999999);
        }
        return substr($digits, 0, $n);
    }

    /** Compute the EAN-13 checksum for a 12-digit string. */
    protected static function ean13Checksum(string $base12): int
    {
        // Positions counted from left, index 0..11
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $digit = (int) $base12[$i];
            $sum += ($i % 2 === 0) ? $digit : $digit * 3;
        }
        $mod = $sum % 10;
        return $mod === 0 ? 0 : 10 - $mod;
    }
}
