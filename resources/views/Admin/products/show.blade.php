@extends('layouts.admin')
@section('title','تفاصيل منتج')
@section('header','تفاصيل منتج')

@section('content')
<div class="card" style="padding:16px">
  <h3>{{ $product->name }}</h3>
  <div class="muted">ماركة: {{ $product->brand ?? '-' }} | SKU: {{ $product->sku ?? '-' }}</div>
  <div class="muted mt-2">الصنف: {{ optional($product->category)->name ?? '-' }}</div>
</div>

<div class="card mt-4" style="padding:0">
  <table>
    <thead><tr><th>#</th><th>الحجم</th><th>SKU</th><th>باركود</th><th>سعر</th><th>كمية</th><th>حالة</th></tr></thead>
    <tbody>
    @foreach($product->variants as $v)
      <tr>
        <td>{{ $v->id }}</td>
        <td>{{ $v->size_ml ? $v->size_ml.' ml' : '-' }}</td>
        <td>{{ $v->sku ?? '-' }}</td>
        <td class="muted">{{ $v->barcode ?? '-' }}</td>
        <td>{{ number_format($v->price,2) }} LYD</td>
        <td>{{ $v->stock_qty ?? 0 }}</td>
        <td><span class="badge">{{ $v->is_active ? 'نشط' : 'موقوف' }}</span></td>
      </tr>
    @endforeach
    </tbody>
  </table>
</div>
@endsection
