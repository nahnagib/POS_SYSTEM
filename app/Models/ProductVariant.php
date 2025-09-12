<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ProductVariant extends Model
{
   use HasFactory;

    protected $fillable = [
        'product_id',
        'size_ml',
        'sku',
        'barcode',
        'price',
        'cost_price',
        'stock',
        'is_active',
        'low_stock_threshold',
    ];

    protected $casts = [
        'size_ml'            => 'decimal:2',
        'price'              => 'decimal:2',
        'cost_price'         => 'decimal:2',
        'stock'              => 'integer',
        'is_active'          => 'boolean',
        'low_stock_threshold'=> 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // --- Auto-generate SKU & Barcode ---
    protected static function booted()
    {
        static::creating(function (ProductVariant $v) {
            if (blank($v->sku)) {
                $v->sku = static::uniqueVariantSku();
            }
            if (blank($v->barcode)) {
                $v->barcode = static::uniqueEan13();
            }
        });

        // Optional safety: if someone clears them later
        static::saving(function (ProductVariant $v) {
            if (blank($v->sku))     $v->sku     = static::uniqueVariantSku();
            if (blank($v->barcode)) $v->barcode = static::uniqueEan13();
        });
    }

    protected static function uniqueVariantSku(): string
    {
        do {
            $code = 'VAR-' . strtoupper(Str::random(8));
        } while (static::where('sku', $code)->exists());

        return $code;
    }

    /** Generate a unique EAN-13 (13 digits). */
    protected static function uniqueEan13(): string
    {
        do {
            $base12 = static::randomDigits(12);
            $check  = static::ean13Checksum($base12);
            $code   = $base12 . $check;
        } while (static::where('barcode', $code)->exists());

        return $code;
    }

    protected static function randomDigits(int $n): string
    {
        $seed = now()->format('ymdHis') . random_int(100000, 999999);
        $digits = preg_replace('/\D/', '', (string) $seed);
        while (strlen($digits) < $n) {
            $digits .= (string) random_int(0, 999999);
        }
        return substr($digits, 0, $n);
    }

    protected static function ean13Checksum(string $base12): int
    {
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $d = (int) $base12[$i];
            $sum += ($i % 2 === 0) ? $d : $d * 3;
        }
        $mod = $sum % 10;
        return $mod === 0 ? 0 : 10 - $mod;
    }
}
