@extends('layouts.admin')
@section('title','تعديل صنف')
@section('header','تعديل صنف')

@section('content')
<form method="POST" action="{{ route('admin.categories.update',$category) }}" class="card" style="padding:16px">
  @csrf @method('PUT')
  <label>الاسم</label>
  <input name="name" value="{{ old('name',$category->name) }}" required>
  <div class="mt-3">
    <label>الأب (اختياري)</label>
    <select name="parent_id">
      <option value="">—</option>
      @foreach($parents as $c)
        <option value="{{ $c->id }}" @selected($category->parent_id==$c->id)>{{ $c->name }}</option>
      @endforeach
    </select>
  </div>
  <div class="mt-4">
    <button class="btn">حفظ</button>
    <a class="btn secondary" href="{{ route('admin.categories.index') }}">إلغاء</a>
  </div>
</form>
@endsection
