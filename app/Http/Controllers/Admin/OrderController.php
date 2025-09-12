<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class OrderController extends Controller
{
    public function index(Request $r)
    {
        $orders = Order::query()
            ->when($r->q, fn($q)=>$q->where('invoice_no','like',"%{$r->q}%"))
            ->orderByDesc('id')
            ->paginate(25)->withQueryString();

        return view('admin.orders.index', compact('orders'));
    }

    public function show(Order $order)
    {
        $order->load('details'); // and relationships you have
        return view('admin.orders.show', compact('order'));
    }

    // keep destroy if you need to void/remove an order; else drop it
    public function destroy(Order $order)
    {
        $order->delete();
        return back()->with('success','Order deleted.');
    }
}
