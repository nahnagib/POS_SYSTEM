@extends('layouts.admin')
@section('title','المنتجات')
@section('header')
<div class="flex">
  <strong>المنتجات</strong>
  <a href="{{ route('admin.products.create') }}" class="btn small">إضافة منتج</a>
</div>
@endsection

@section('content')
<form method="GET" class="search mb-4">
  <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث بالاسم / الماركة / SKU">
  <button class="btn small secondary">بحث</button>
</form>

<div class="card" style="padding:0">
  <table>
    <thead>
      <tr><th>#</th><th>الاسم</th><th>الماركة</th><th>SKU</th><th>الأصناف</th><th>عدد المتغيرات</th><th class="right">إجراءات</th></tr>
    </thead>
    <tbody>
      @forelse($products as $p)
        <tr>
          <td>{{ $p->id }}</td>
          <td><a href="{{ route('admin.products.show',$p) }}">{{ $p->name }}</a></td>
          <td>{{ $p->brand ?? '-' }}</td>
          <td class="muted">{{ $p->sku ?? '-' }}</td>
          <td class="muted">{{ optional($p->category)->name ?? '-' }}</td>
          <td><span class="badge">{{ $p->variants_count }}</span></td>
          <td class="right">
            <a class="btn small secondary" href="{{ route('admin.products.edit',$p) }}">تعديل</a>
            <form class="inline" method="POST" action="{{ route('admin.products.destroy',$p) }}" onsubmit="return confirm('حذف المنتج؟')">
              @csrf @method('DELETE')
              <button class="btn small danger">حذف</button>
            </form>
          </td>
        </tr>
      @empty
        <tr><td colspan="7" class="muted">لا يوجد منتجات</td></tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">{{ $products->links() }}</div>
@endsection
