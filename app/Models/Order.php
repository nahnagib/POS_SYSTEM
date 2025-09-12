<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Kyslik\ColumnSortable\Sortable;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory, Sortable;

    protected $fillable = [
        'order_date',
        'invoice_no',
        'user_id',
        'order_status',
        'payment_status',
        'payment_method',
        'total_items',
        'sub_total',
        'discount',
        'vat',
        'total',
    ];

    protected $casts = [
        'order_date'   => 'datetime',
        'total_items'  => 'integer',
        'sub_total'    => 'decimal:2',
        'discount'     => 'decimal:2',
        'vat'          => 'decimal:2',
        'total'        => 'decimal:2',
    ];

    /* -------------------- Relationships -------------------- */

    public function details()
    {
        return $this->hasMany(OrderDetail::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
 /* -------------------- Query Scopes -------------------- */

    public function scopePaid($query)
    {
        return $query->where('payment_status', 'paid');
    }

    public function scopeUnpaid($query)
    {
        return $query->where('payment_status', 'unpaid');
    }

    public function scopeDateBetween($query, $from, $to)
    {
        return $query->whereBetween('order_date', [$from, $to]);
    }

    public function scopeSearch($query, $term)
    {
        if (!$term) return $query;
        return $query->where(function ($q) use ($term) {
            $q->where('invoice_no', 'like', "%{$term}%")
              ->orWhere('order_status', 'like', "%{$term}%")
              ->orWhere('payment_status', 'like', "%{$term}%")
              ->orWhere('payment_method', 'like', "%{$term}%");
        });
    }

    /* -------------------- Boot & Helpers -------------------- */

    protected static function booted()
    {
        static::creating(function (Order $order) {
            if (blank($order->invoice_no)) {
                $order->invoice_no = static::makeInvoiceNo();
            }
        });
    }

    public static function makeInvoiceNo(): string
    {
        return 'INV-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
    }

    /**
     * Recalculate sub_total, discount, vat, total_items, total.
     * Call after adding/updating/deleting details.
     */
    public function recalcTotals(int $vatPercent = 0, float $globalDiscount = 0): self
    {
        $items = $this->details()->get(['quantity', 'unit_price', 'line_discount', 'line_total']);

        $sub = 0;
        $qty = 0;
        $lineDiscounts = 0;

        foreach ($items as $it) {
            $sub += $it->unit_price * $it->quantity;
            $qty += $it->quantity;
            $lineDiscounts += $it->line_discount;
        }

        $discount = $lineDiscounts + $globalDiscount;
        $vatAmount = round(($sub - $discount) * ($vatPercent / 100), 2);
        $total = ($sub - $discount) + $vatAmount;

        $this->fill([
            'total_items' => $qty,
            'sub_total'   => $sub,
            'discount'    => $discount,
            'vat'         => $vatAmount,
            'total'       => $total,
        ])->save();

        return $this->fresh();
    }
}
