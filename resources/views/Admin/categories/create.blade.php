@extends('layouts.admin')
@section('title','إضافة صنف')
@section('header','إضافة صنف')

@section('content')
<form method="POST" action="{{ route('admin.categories.store') }}" class="card" style="padding:16px">
  @csrf
  <label>الاسم</label>
  <input name="name" required>
  <div class="mt-3">
    <label>الأب (اختياري)</label>
    <select name="parent_id">
      <option value="">—</option>
      @foreach(\App\Models\Category::orderBy('name')->get() as $c)
        <option value="{{ $c->id }}">{{ $c->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="mt-4">
    <button class="btn">حفظ</button>
    <a class="btn secondary" href="{{ route('admin.categories.index') }}">إلغاء</a>
  </div>
</form>
@endsection
