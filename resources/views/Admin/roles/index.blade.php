@extends('layouts.admin')
@section('title','الأدوار والصلاحيات')
@section('header','الأدوار والصلاحيات')

@section('content')
<div class="grid two">
  <div class="card" style="padding:16px">
    <h3>إضافة دور</h3>
    <form method="POST" action="{{ route('admin.roles.store') }}" class="mt-3">
      @csrf
      <label>اسم الدور</label>
      <input name="name" required>
      <div class="mt-3"><button class="btn">إضافة</button></div>
    </form>
  </div>

  <div class="card" style="padding:16px">
    <h3>الأدوار</h3>
    @foreach($roles as $role)
      <form method="POST" action="{{ route('admin.roles.update',$role) }}" class="card mt-3" style="padding:12px">
        @csrf @method('PUT')
        <div class="grid two">
          <div>
            <label>الاسم</label>
            <input name="name" value="{{ $role->name }}" required>
          </div>
          <div>
            <label>الصلاحيات</label>
            <select name="permissions[]" multiple size="6">
              @foreach($allPermissions as $p)
                <option value="{{ $p->name }}" @selected($role->permissions->pluck('name')->contains($p->name))>{{ $p->name }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="mt-2">
          <button class="btn small">حفظ</button>
          <form method="POST" action="{{ route('admin.roles.destroy',$role) }}" class="inline" onsubmit="return confirm('حذف الدور؟')">
            @csrf @method('DELETE')
            <button class="btn small danger">حذف</button>
          </form>
        </div>
      </form>
    @endforeach
    <div class="mt-3">{{ $roles->links() }}</div>
  </div>
</div>
@endsection
