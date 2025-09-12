<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>تسجيل الدخول</title>
  <style>
    :root { color-scheme: dark light; }
    body{margin:0;font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu}
    .wrap{min-height:100dvh;display:grid;place-items:center;background:#f6f7f8}
    .card{width:100%;max-width:380px;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:22px}
    label{display:block;margin-top:12px;margin-bottom:6px;color:#444}
    input[type="email"],input[type="password"]{width:100%;padding:10px;border:1px solid #ddd;border-radius:8px}
    .btn{width:100%;margin-top:16px;padding:10px;border-radius:8px;border:1px solid #111;background:#111;color:#fff}
    .muted{color:#666;font-size:12px;margin-top:10px}
    .error{background:#fee2e2;border:1px solid #ef4444;color:#991b1b;padding:10px;border-radius:8px;margin-bottom:12px}
    .brand{font-weight:700;font-size:18px;text-align:center;margin-bottom:10px}
  </style>
</head>
<body>
<div class="wrap">
  <form class="card" method="POST" action="{{ route('login.attempt') }}">
    @csrf
    <div class="brand">Luxury POS</div>

    @if ($errors->any())
      <div class="error">
        <ul style="padding-left:18px;margin:0">
          @foreach ($errors->all() as $err)
            <li>{{ $err }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <label>البريد الإلكتروني</label>
    <input type="email" name="email" value="{{ old('email') }}" required autofocus>

    <label>كلمة المرور</label>
    <input type="password" name="password" required>

    <div style="margin-top:10px">
      <label style="display:flex;align-items:center;gap:8px">
        <input type="checkbox" name="remember" value="1"> تذكرني
      </label>
    </div>

    <button class="btn">تسجيل الدخول</button>

    <div class="muted">
      جرّب: admin@pos.local / admin123 أو cashier@pos.local / cashier123
    </div>
  </form>
</div>
</body>
</html>
