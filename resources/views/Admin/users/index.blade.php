@extends('layouts.admin')
@section('title','المستخدمون')
@section('header','المستخدمون')

@section('content')
<div class="grid two">
  <div class="card" style="padding:16px">
    <h3>إضافة مستخدم</h3>
    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-3">
      @csrf
      <label>الاسم</label>
      <input name="name" required>
      <div class="mt-2"><label>البريد</label><input name="email" type="email" required></div>
      <div class="mt-2"><label>كلمة المرور</label><input name="password" type="password" required></div>
      <div class="mt-2"><label>الأدوار</label>
        <select name="roles[]" multiple size="4">
          @foreach($roles as $r)
            <option value="{{ $r->name }}">{{ $r->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="mt-3"><button class="btn">إضافة</button></div>
    </form>
  </div>

  <div class="card" style="padding:16px">
    <h3>قائمة المستخدمين</h3>
    <table class="mt-2">
      <thead><tr><th>#</th><th>الاسم</th><th>البريد</th><th>الأدوار</th><th class="right">إجراءات</th></tr></thead>
      <tbody>
      @foreach($users as $u)
        <tr>
          <td>{{ $u->id }}</td>
          <td>{{ $u->name }}</td>
          <td class="muted">{{ $u->email }}</td>
          <td class="muted">{{ $u->roles->pluck('name')->join(', ') }}</td>
          <td class="right">
            <details>
              <summary class="btn small secondary">تعديل</summary>
              <form method="POST" action="{{ route('admin.users.update',$u) }}" class="mt-2">
                @csrf @method('PUT')
                <div class="grid two">
                  <div><label>الاسم</label><input name="name" value="{{ $u->name }}" required></div>
                  <div><label>البريد</label><input name="email" type="email" value="{{ $u->email }}" required></div>
                  <div><label>كلمة المرور (اختياري)</label><input name="password" type="password"></div>
                  <div>
                    <label>الأدوار</label>
                    <select name="roles[]" multiple size="4">
                      @foreach($roles as $r)
                        <option value="{{ $r->name }}" @selected($u->roles->pluck('name')->contains($r->name))>{{ $r->name }}</option>
                      @endforeach
                    </select>
                  </div>
                </div>
                <div class="mt-2"><button class="btn small">حفظ</button></div>
              </form>
              <form method="POST" action="{{ route('admin.users.destroy',$u) }}" class="mt-2" onsubmit="return confirm('حذف المستخدم؟')">
                @csrf @method('DELETE')
                <button class="btn small danger">حذف</button>
              </form>
            </details>
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
    <div class="mt-3">{{ $users->links() }}</div>
  </div>
</div>
@endsection
