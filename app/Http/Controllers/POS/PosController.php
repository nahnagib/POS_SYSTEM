<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\ReceiptPrinter; 

class PosController extends Controller
{
    // ---------------------------
    // Quick Lookups
    // ---------------------------

    public function lookupByBarcode(string $code)
    {
        $code = trim($code);

            $v = ProductVariant::with('product')
                ->whereRaw('LOWER(product_variants.sku) = LOWER(?)', [$code])
                ->orWhereHas('product', function ($q) use ($code) {
                    $q->whereRaw('LOWER(products.sku) = LOWER(?)', [$code]);
                })
                ->first();

            if (!$v) {
                return response()->json(['found' => false, 'message' => 'Variant not found'], 404);
            }

            return response()->json([
                'found'   => true,
                'variant' => [
                    'id'      => $v->id,
                    'name'    => $v->name ?? ($v->size_ml ? "{$v->size_ml} ml" : null),
                    'price'   => $v->price,
                    'stock'   => $v->stock_qty ?? null, // adjust if your column differs
                    'sku'     => $v->sku,               // variant SKU (may be null)
                ],
                'product' => [
                    'id'      => $v->product->id ?? null,
                    'name'    => $v->product->name ?? null,
                    'brand'   => $v->product->brand ?? null,
                    'sku'     => $v->product->sku ?? null, // product SKU (often what you’re typing)
                ],
            ]);
    }


    public function lookupByVariantId(int $variantId)
    {
        $v = ProductVariant::with('product')->findOrFail($variantId);

        return [
            'variant' => [
                'id'    => $v->id,
                'name'  => $v->name ?? ($v->size_ml ? "{$v->size_ml} ml" : null),
                'price' => $v->price,
                'stock' => $v->stock_qty ?? null, // adjust column name if different
                'barcode' => $v->barcode,
            ],
            'product' => [
                'id'    => $v->product->id,
                'name'  => $v->product->name,
                'brand' => $v->product->brand,
            ],
        ];
    }


    // ---------------------------
    // Order Lifecycle
    // ---------------------------

    public function startOrder(Request $request)
    {
        $data = $request->validate([
            'payment_method' => ['nullable', 'in:cash,edfali,mobicash,local_service'],
            'vat'            => ['nullable', 'numeric', 'min:0'],
        ]);

        $order = Order::create([
            'order_date'     => now(),
            'invoice_no'     => $this->nextInvoiceNo(),
            'user_id'        => Auth::id(),
            'order_status'   => 'paid',      // stays 'paid' once checkout succeeds; will flip to void/refunded by actions below
            'payment_status' => 'unpaid',
            'payment_method' => $data['payment_method'] ?? 'cash',
            'total_items'    => 0,
            'sub_total'      => 0,
            'discount'       => 0,
            'vat'            => (float)($data['vat'] ?? 0),
            'total'          => 0,
        ]);

        return $order->load('details');
    }

    public function addItem(Request $request, Order $order)
    {
        $payload = $request->validate([
            'variant_id'    => ['required', 'exists:product_variants,id'],
            'quantity'      => ['required', 'integer', 'min:1'],
            'unit_price'    => ['nullable', 'numeric', 'min:0'],
            'line_discount' => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($order, $payload) {
            /** @var ProductVariant $v */
            $v = ProductVariant::with('product')->findOrFail($payload['variant_id']);

            $qty       = (int)$payload['quantity'];
            $unitPrice = (float)($payload['unit_price'] ?? $v->price);
            $lineDisc  = (float)($payload['line_discount'] ?? 0);

            // (Optionally merge same variant in one line — if desired, query existing detail and update qty instead of creating)
            $detail = OrderDetail::create([
                'order_id'      => $order->id,
                'product_id'    => $v->product->id,
                'variant_id'    => $v->id,
                'product_name'  => $v->product->name,
                'variant_name'  => $v->name ?? ($v->size_ml ? "{$v->size_ml} ml" : null),
                'quantity'      => $qty,
                'unit_price'    => $unitPrice,
                'line_discount' => $lineDisc,
                'line_total'    => max(0, ($unitPrice * $qty) - $lineDisc),
            ]);

            $this->recalcTotals($order)->save();

            return $order->fresh()->load('details');
        });
    }

    public function updateItem(Request $request, Order $order, OrderDetail $detail)
    {
        abort_unless($detail->order_id === $order->id, 404);

        $payload = $request->validate([
            'quantity'      => ['sometimes', 'integer', 'min:1'],
            'unit_price'    => ['sometimes', 'numeric', 'min:0'],
            'line_discount' => ['sometimes', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($order, $detail, $payload) {
            $detail->fill($payload);

            $detail->line_total = max(
                0,
                ($detail->unit_price * $detail->quantity) - $detail->line_discount
            );
            $detail->save();

            $this->recalcTotals($order)->save();

            return $order->fresh()->load('details');
        });
    }

    public function removeItem(Order $order, OrderDetail $detail)
    {
        abort_unless($detail->order_id === $order->id, 404);

        return DB::transaction(function () use ($order, $detail) {
            $detail->delete();
            $this->recalcTotals($order)->save();
            return $order->fresh()->load('details');
        });
    }

    public function applyOrderDiscount(Request $request, Order $order)
    {
        $data = $request->validate([
            'discount' => ['required', 'numeric', 'min:0'],
        ]);

        $order->discount = (float)$data['discount'];
        $this->recalcTotals($order)->save();

        return $order->fresh()->load('details');
    }

    public function setPayment(Request $request, Order $order)
    {
        $data = $request->validate([
            'payment_method' => ['required', 'in:cash,edfali,mobicash,local_service'],
            'paid_amount'    => ['nullable', 'numeric', 'min:0'], // optional: set partial/paid here
        ]);

        $order->payment_method = $data['payment_method'];

        if (array_key_exists('paid_amount', $data)) {
            $paid = (float)$data['paid_amount'];
            $order->payment_status = $paid >= $order->total
                ? 'paid'
                : ($paid > 0 ? 'partial' : 'unpaid');
        }

        $order->save();

        return $order->fresh()->load('details');
    }

    public function checkout(Request $request, Order $order)
    {
        $data = $request->validate([
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'vat'         => ['nullable', 'numeric', 'min:0'],
        ]);

        return DB::transaction(function () use ($order, $data) {
            if (isset($data['vat'])) {
                $order->vat = (float)$data['vat'];
            }

            $this->recalcTotals($order)->save();


            app(ReceiptPrinter::class)->printOrder($order->fresh()->load('details'));

            // Decrement stock for each detail atomically
            foreach ($order->details as $d) {
                $this->decrementStockOrFail($d->variant_id, $d->quantity);
            }

            $paid = (float)($data['paid_amount'] ?? $order->total);
            $order->payment_status = $paid >= $order->total
                ? 'paid'
                : ($paid > 0 ? 'partial' : 'unpaid');

            $order->order_status = 'paid';
            $order->save();

            return [
                'order' => $order->fresh()->load('details'),
                'receipt_hint' => 'Print receipt here',
            ];
        });
    }

    public function void(Order $order)
    {
        // If order already decremented stock (after checkout), restore it.
        return DB::transaction(function () use ($order) {
            if ($order->order_status === 'paid') {
                foreach ($order->details as $d) {
                    $this->incrementStock($d->variant_id, $d->quantity);
                }
            }

            $order->order_status   = 'void';
            $order->payment_status = 'unpaid';
            $order->save();

            return $order->fresh()->load('details');
        });
    }

    public function refund(Order $order)
    {
        abort_unless($order->order_status === 'paid', 422, 'Only paid orders can be refunded.');

        return DB::transaction(function () use ($order) {
            foreach ($order->details as $d) {
                $this->incrementStock($d->variant_id, $d->quantity);
            }

            $order->order_status   = 'refunded';
            $order->payment_status = 'unpaid'; // or keep 'paid' and track refund elsewhere
            $order->save();

            return $order->fresh()->load('details');
        });
    }



    // ---------------------------
    // Helpers (private)
    // ---------------------------

    /**
     * Generate invoice like INV-YYYYMMDD-000001
     */
    private function nextInvoiceNo(): string
    {
        $prefix = now()->format('Ymd');

        $last = DB::table('orders')
            ->select(DB::raw('MAX(invoice_no) as last'))
            ->where('invoice_no', 'like', "INV-$prefix%")
            ->first();

        $seq = 1;
        if ($last && $last->last) {
            $seq = (int)substr($last->last, -6) + 1;
        }

        return sprintf("INV-%s-%06d", $prefix, $seq);
    }

    /**
     * Recalculate totals on the order model in memory (caller should ->save()).
     */
    private function recalcTotals(Order $order): Order
    {
        $items = $order->details; // hasMany

        $order->total_items = (int)$items->sum('quantity');

        $subTotal = 0.0;
        foreach ($items as $d) {
            $subTotal += max(0, ($d->unit_price * $d->quantity) - $d->line_discount);
        }
        $order->sub_total = $subTotal;

        $afterOrderDiscount = max(0, $order->sub_total - $order->discount);
        $order->total = $afterOrderDiscount + (float)$order->vat;

        return $order;
    }

    /**
     * Decrement variant stock with row lock; fail if insufficient.
     * Adjust 'stock_qty' if your column name differs.
     */
    private function decrementStockOrFail(int $variantId, int $qty): void
    {
        /** @var ProductVariant $v */
        $v = ProductVariant::lockForUpdate()->findOrFail($variantId);

        $current = (int)($v->stock_qty ?? 0);
        if ($current < $qty) {
            abort(422, "Insufficient stock for variant #{$variantId}");
        }
        $v->stock_qty = $current - $qty;
        $v->save();
    }

    /**
     * Increment variant stock with row lock (for void/refund).
     */
    private function incrementStock(int $variantId, int $qty): void
    {
        /** @var ProductVariant $v */
        $v = ProductVariant::lockForUpdate()->findOrFail($variantId);
        $v->stock_qty = (int)($v->stock_qty ?? 0) + $qty;
        $v->save();
    }

}
