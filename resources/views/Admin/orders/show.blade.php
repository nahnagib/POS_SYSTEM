@extends('layouts.admin')
@section('title','تفاصيل الطلب')
@section('header','تفاصيل الطلب')

@section('content')
<div class="card" style="padding:16px">
  <div><strong>رقم الفاتورة:</strong> {{ $order->invoice_no ?? ('INV-'.$order->id) }}</div>
  <div class="muted mt-2">التاريخ: {{ $order->created_at->format('Y-m-d H:i') }}</div>
  <div class="muted mt-2">الحالة: {{ $order->order_status ?? 'completed' }}</div>
  <div class="muted mt-2">الضريبة (VAT): {{ number_format($order->vat ?? 0,2) }} LYD</div>
  <div class="muted mt-2">الإجمالي: <strong>{{ number_format($order->total,2) }} LYD</strong></div>
</div>

<div class="card mt-4" style="padding:0">
  <table>
    <thead><tr><th>المنتج</th><th>الحجم</th><th>السعر</th><th>الكمية</th><th>الإجمالي</th></tr></thead>
    <tbody>
    @foreach($order->details as $d)
      @php
        // adjust if you have relationships variant->product
        $variant = \App\Models\ProductVariant::with('product')->find($d->product_id);
      @endphp
      <tr>
        <td>{{ $variant?->product?->name ?? '—' }}</td>
        <td>{{ $variant?->size_ml ? $variant->size_ml.' ml' : '-' }}</td>
        <td>{{ number_format($d->unit_price,2) }} LYD</td>
        <td>{{ $d->qty }}</td>
        <td>{{ number_format($d->total,2) }} LYD</td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
