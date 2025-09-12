@extends('layouts.admin')
@section('title','الطلبات')
@section('header','الطلبات')

@section('content')
<form method="GET" class="search mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="رقم الفاتورة">
  <button class="btn small secondary">بحث</button>
</form>

<div class="card" style="padding:0">
  <table>
    <thead><tr><th>#</th><th>رقم الفاتورة</th><th>التاريخ</th><th>حالة</th><th>الإجمالي</th><th class="right">إجراءات</th></tr></thead>
    <tbody>
    @forelse($orders as $o)
      <tr>
        <td>{{ $o->id }}</td>
        <td><a href="{{ route('admin.orders.show',$o) }}">{{ $o->invoice_no ?? ('INV-'.$o->id) }}</a></td>
        <td class="muted">{{ $o->created_at->format('Y-m-d H:i') }}</td>
        <td><span class="badge">{{ $o->order_status ?? 'completed' }}</span></td>
        <td>{{ number_format($o->total,2) }} LYD</td>
        <td class="right">
          <form class="inline" method="POST" action="{{ route('admin.orders.destroy',$o) }}" onsubmit="return confirm('حذف/إبطال الطلب؟')">
            @csrf @method('DELETE')
            <button class="btn small danger">حذف</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">لا يوجد طلبات</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">{{ $orders->links() }}</div>
@endsection
