<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'لوحة التحكم')</title>
  <style>
    :root { color-scheme: dark light; }
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,'Helvetica Neue',Arial}
    .wrap{display:grid;grid-template-columns:240px 1fr;min-height:100dvh}
    aside{background:#111;color:#eee;padding:18px;}
    aside a{display:block;color:#eee;text-decoration:none;padding:8px 10px;border-radius:6px}
    aside a.active, aside a:hover{background:#222}
    header{display:flex;align-items:center;gap:10px;justify-content:space-between;padding:14px 18px;border-bottom:1px solid #eee}
    main{padding:18px}
    .container{max-width:1100px}
    .card{border:1px solid #e5e7eb;border-radius:10px;background:#fff;color:#111}
    .btn{display:inline-block;padding:8px 12px;border:1px solid #111;border-radius:8px;background:#111;color:#fff;text-decoration:none;font-size:14px}
    .btn.secondary{background:#fff;color:#111}
    .btn.danger{background:#b91c1c;border-color:#b91c1c}
    .btn.small{padding:6px 9px;font-size:13px}
    .grid{display:grid;gap:12px}
    .grid.kpi{grid-template-columns:repeat(6,1fr)}
    .grid.two{grid-template-columns:1fr 1fr}
    table{width:100%;border-collapse:collapse}
    th,td{border-bottom:1px solid #eee;padding:10px;text-align:start}
    input,select{padding:9px 10px;border:1px solid #ddd;border-radius:8px;width:100%}
    form.inline{display:inline}
    .muted{color:#666}
    .mt-2{margin-top:8px}.mt-3{margin-top:12px}.mt-4{margin-top:16px}.mt-6{margin-top:24px}
    .mb-2{margin-bottom:8px}.mb-4{margin-bottom:16px}
    .flex{display:flex;gap:10px;align-items:center}
    .right{text-align:end}
    .badge{padding:3px 8px;border-radius:999px;border:1px solid #ddd;font-size:12px}
    .success{color:#065f46}.error{color:#991b1b}
    .rtl{direction:rtl}
    .search{display:flex;gap:8px}
    .nav-group{margin-top:18px;font-weight:700;color:#bbb;text-transform:uppercase;font-size:12px}
  </style>
  @stack('head')
</head>
<body>
<div class="wrap">
  <aside>
    <div style="font-weight:700;font-size:18px;margin-bottom:10px">Luxury POS</div>
    <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">لوحة التحكم</a>
    <div class="nav-group">المخزون</div>
    <a href="{{ route('admin.products.index') }}" class="{{ request()->routeIs('admin.products.*') ? 'active' : '' }}">المنتجات</a>
    <a href="{{ route('admin.categories.index') }}" class="{{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">الأصناف</a>
    <div class="nav-group">المبيعات</div>
    <a href="{{ route('admin.orders.index') }}" class="{{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">الطلبات</a>
    <div class="nav-group">المستخدمون</div>
    <a href="{{ route('admin.users.index') }}" class="{{ request()->routeIs('admin.users.*') ? 'active' : '' }}">المستخدمون</a>
    <a href="{{ route('admin.roles.index') }}" class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">الأدوار والصلاحيات</a>
  </aside>

  <div>
    <header>
      <div>@yield('header','')</div>
      <div class="flex">
        <span class="muted">{{ auth()->user()->name ?? '' }}</span>
        <form method="POST" action="{{ route('logout') }}" class="inline">@csrf
          <button class="btn small secondary">تسجيل الخروج</button>
        </form>
      </div>
    </header>
    <main>
      <div class="container">
        @if(session('success')) <div class="success mb-4">{{ session('success') }}</div> @endif
        @if(session('error')) <div class="error mb-4">{{ session('error') }}</div> @endif
        @yield('content')
      </div>
    </main>
  </div>
</div>
@stack('scripts')
</body>
</html>
