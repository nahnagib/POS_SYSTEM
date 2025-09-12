<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderDetail extends Model
{
    use HasFactory;
     // Explicit because model is singular but table is plural
    protected $table = 'order_details';

    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_name',
        'quantity',
        'unit_price',
        'line_discount',
        'line_total',
    ];

    protected $casts = [
        'quantity'      => 'integer',
        'unit_price'    => 'decimal:2',
        'line_discount' => 'decimal:2',
        'line_total'    => 'decimal:2',
    ];


    /* -------------------- Relationships -------------------- */

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class);
    }

    /* -------------------- Helpers -------------------- */

    /**
     * Recalculate the line total.
     */
    public function recalcLineTotal(): void
    {
        $this->line_total = max(
            ($this->unit_price * $this->quantity) - $this->line_discount,
            0
        );
    }

}
