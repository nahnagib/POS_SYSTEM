@extends('layouts.admin')
@section('title','الأصناف')
@section('header')
<div class="flex">
  <strong>الأصناف</strong>
  <a href="{{ route('admin.categories.create') }}" class="btn small">إضافة صنف</a>
</div>
@endsection

@section('content')
<div class="card" style="padding:0">
  <table>
    <thead><tr><th>#</th><th>الاسم</th><th>الأب</th><th class="right">إجراءات</th></tr></thead>
    <tbody>
    @forelse($items as $c)
      <tr>
        <td>{{ $c->id }}</td>
        <td>{{ $c->name }}</td>
        <td class="muted">{{ optional($c->parent)->name ?? '-' }}</td>
        <td class="right">
          <a class="btn small secondary" href="{{ route('admin.categories.edit',$c) }}">تعديل</a>
          <form class="inline" method="POST" action="{{ route('admin.categories.destroy',$c) }}" onsubmit="return confirm('حذف الصنف؟')">
            @csrf @method('DELETE')
            <button class="btn small danger">حذف</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="4" class="muted">لا يوجد أصناف</td></tr>
    @endforelse
    </tbody>
  </table>
</div>

<div class="mt-3">{{ $items->links() }}</div>
@endsection
