<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'POS' }}</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @stack('head')
</head>
<body class="bg-gray-50 text-gray-900">
  <div class="max-w-7xl mx-auto p-4">
    <header class="flex items-center justify-between mb-4">
      <h1 class="text-xl font-bold">{{ $title ?? 'POS' }}</h1>
      <div class="flex items-center gap-2">
        <span class="text-sm">مرحباً، {{ auth()->user()->name ?? '' }}</span>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button class="btn btn-secondary">تسجيل الخروج</button>
        </form>
      </div>
    </header>

    @yield('content')
  </div>

  @stack('scripts')
</body>
</html>
