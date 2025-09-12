@extends('layouts.admin')
@section('title','لوحة التحكم')
@section('header','لوحة التحكم')

@push('head')
  {{-- Chart.js CDN (lightweight) --}}
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .kpi-grid{display:grid;gap:12px;grid-template-columns:repeat(6,1fr)}
    @media (max-width:1200px){.kpi-grid{grid-template-columns:repeat(3,1fr)}}
    @media (max-width:700px){.kpi-grid{grid-template-columns:repeat(2,1fr)}}
    .kpi{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:16px}
    .kpi .label{color:#6B7280;font-size:12px;margin-bottom:6px}
    .kpi .value{font-weight:800;font-size:20px}
    .cards-2{display:grid;gap:14px;grid-template-columns:1.2fr .8fr}
    @media (max-width:1100px){.cards-2{grid-template-columns:1fr}}
    .panel{background:#fff;border:1px solid #E5E7EB;border-radius:12px;padding:16px}
    .panel h3{margin:0 0 8px 0;font-size:16px}
    .table-wrap{overflow:auto;border:1px solid #E5E7EB;border-radius:12px;background:#fff}
    .tbl{width:100%;border-collapse:collapse}
    .tbl th,.tbl td{padding:10px;border-bottom:1px solid #F3F4F6;white-space:nowrap}
    .tbl th{font-weight:700;font-size:12px;color:#6B7280}
    .badge{padding:2px 8px;border-radius:999px;border:1px solid #E5E7EB;font-size:12px}
  </style>
@endpush

@section('content')

{{-- ===== KPIs ===== --}}
<div class="kpi-grid">
  <div class="kpi">
    <div class="label">إجمالي المبيعات</div>
    <div class="value">{{ number_format($kpis['totalRevenue'] ?? 0,2) }} LYD</div>
  </div>
  <div class="kpi">
    <div class="label">عدد الطلبات</div>
    <div class="value">{{ $kpis['ordersCount'] ?? 0 }}</div>
  </div>
  <div class="kpi">
    <div class="label">القطع المباعة</div>
    <div class="value">{{ $kpis['itemsSold'] ?? 0 }}</div>
  </div>
  <div class="kpi">
    <div class="label">متوسط الفاتورة</div>
    <div class="value">{{ number_format($kpis['avgOrderValue'] ?? 0,2) }} LYD</div>
  </div>
  <div class="kpi">
    <div class="label">مبيعات اليوم</div>
    <div class="value">{{ number_format($kpis['todayRevenue'] ?? 0,2) }} LYD</div>
  </div>
  <div class="kpi">
    <div class="label">مبيعات هذا الشهر</div>
    <div class="value">{{ number_format($kpis['monthRevenue'] ?? 0,2) }} LYD</div>
  </div>
</div>

{{-- ===== Charts row ===== --}}
<div class="cards-2 mt-6">
  <div class="panel">
    <h3>الإيراد خلال 30 يومًا</h3>
    <canvas id="rev30Chart" height="110"></canvas>
  </div>
  <div class="panel">
    <h3>الأصناف الأعلى إيرادًا</h3>
    <canvas id="catDonut" height="110"></canvas>
  </div>
</div>

{{-- ===== Top products + Low stock ===== --}}
<div class="cards-2 mt-6">
  <div class="panel">
    <h3>أكثر المنتجات مبيعًا (إيراد)</h3>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>المنتج</th>
            <th>الحجم</th>
            <th>الكمية</th>
            <th>الإيراد</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($topProducts ?? []) as $r)
            <tr>
              <td>{{ $r->product }}</td>
              <td>{{ $r->size_ml ? $r->size_ml.' ml' : '-' }}</td>
              <td>{{ (int)$r->qty_sold }}</td>
              <td>{{ number_format($r->revenue,2) }} LYD</td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">لا يوجد بيانات</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  <div class="panel">
    <h3>المخزون المنخفض</h3>
    <div class="table-wrap">
      <table class="tbl">
        <thead>
          <tr>
            <th>المنتج</th>
            <th>الحجم</th>
            <th>المتوفر</th>
            <th>حد التنبيه</th>
          </tr>
        </thead>
        <tbody>
          @forelse(($lowStock ?? []) as $v)
            <tr>
              <td>{{ $v->product->name }}</td>
              <td>{{ $v->size_ml ? $v->size_ml.' ml' : '-' }}</td>
              <td><span class="badge">{{ $v->stock }}</span></td>
              <td>{{ $v->product->low_stock_threshold ?? 0 }}</td>
            </tr>
          @empty
            <tr><td colspan="4" class="muted">لا يوجد منتجات منخفضة المخزون</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@endsection

@push('scripts')
<script>
  // === Revenue 30 days (line)
  const revLabels = @json($revLabels ?? []);
  const revValues = @json($revValues ?? []);
  const ctx1 = document.getElementById('rev30Chart').getContext('2d');
  new Chart(ctx1, {
    type: 'line',
    data: {
      labels: revLabels,
      datasets: [{
        label: 'LYD',
        data: revValues,
        fill: false,
        tension: .3,
        borderWidth: 2,
        pointRadius: 2
      }]
    },
    options: {
      responsive: true,
      plugins: { legend: { display:false }},
      scales: {
        x: { grid: { display:false }},
        y: { grid: { color:'#F3F4F6' }, ticks: { callback:(v)=>v.toLocaleString() } }
      }
    }
  });

  // === Top categories donut (build from $topCategories)
  const catLabels = @json(($topCategories ?? collect())->pluck('category'));
  const catValues = @json(($topCategories ?? collect())->pluck('revenue')->map(fn($v)=>(float)$v));
  const ctx2 = document.getElementById('catDonut').getContext('2d');
  new Chart(ctx2, {
    type: 'doughnut',
    data: {
      labels: catLabels,
      datasets: [{ data: catValues }]
    },
    options: {
      responsive: true,
      plugins: {
        legend: { position:'bottom' },
        tooltip: { callbacks:{ label:(ctx)=> `${ctx.label}: ${Number(ctx.parsed).toLocaleString()} LYD` } }
      },
      cutout: '60%'
    }
  });
</script>
@endpush
