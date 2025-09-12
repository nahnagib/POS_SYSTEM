@extends('layouts.admin')
@section('title','إضافة منتج')
@section('header','إضافة منتج')

@section('content')
<form method="POST" action="{{ route('admin.products.store') }}">
  @csrf
  <div class="grid two">
    <div class="card" style="padding:16px">
      <label>الاسم</label>
      <input name="name" value="{{ old('name') }}" required>

      <div class="mt-3"><label>الماركة</label>
      <input name="brand" value="{{ old('brand') }}"></div>

      <div class="mt-3"><label>SKU</label>
      <input name="sku" value="{{ old('sku') }}"></div>

      <div class="mt-3"><label>الصنف</label>
      <select name="category_id">
        <option value="">—</option>
        @foreach(\App\Models\Category::orderBy('name')->get() as $c)
          <option value="{{ $c->id }}">{{ $c->name }}</option>
        @endforeach
      </select></div>

      <div class="mt-3"><label>تتبع المخزون</label>
      <select name="track_stock">
        <option value="1">نعم</option>
        <option value="0">لا</option>
      </select></div>

      <div class="mt-3"><label>حد التنبيه للمخزون</label>
      <input type="number" min="0" name="low_stock_threshold" value="2"></div>

      <div class="mt-3"><label>نشط</label>
      <select name="is_active">
        <option value="1">نشط</option>
        <option value="0">غير نشط</option>
      </select></div>
    </div>

    <div class="card" style="padding:16px">
      <h3>المتغيرات (Variants)</h3>
      <p class="muted">أضف أحجام/باركود/سعر… يمكن إضافة أكثر من صف.</p>

      <div id="variants">
        <div class="variant">
          <div class="grid two">
            <div><label>الحجم (ml)</label><input type="number" step="0.01" name="variants[0][size_ml]"></div>
            <div><label>SKU</label><input name="variants[0][sku]"></div>
            <div><label>الباركود</label><input name="variants[0][barcode]"></div>
            <div><label>السعر</label><input type="number" step="0.01" name="variants[0][price]" required></div>
            <div><label>تكلفة الشراء</label><input type="number" step="0.01" name="variants[0][cost_price]"></div>
            <div><label>الكمية</label><input type="number" name="variants[0][stock_qty]" value="0"></div>
          </div>
          <hr class="mt-3">
        </div>
      </div>

      <button type="button" class="btn small secondary mt-2" onclick="addVariant()">+ إضافة متغير</button>
    </div>
  </div>

  <div class="mt-4">
    <button class="btn">حفظ</button>
    <a class="btn secondary" href="{{ route('admin.products.index') }}">إلغاء</a>
  </div>
</form>

@push('scripts')
<script>
let idx = 1;
function addVariant(){
  const wrap = document.getElementById('variants');
  const html = `
  <div class="variant mt-3">
    <div class="grid two">
      <div><label>الحجم (ml)</label><input type="number" step="0.01" name="variants[${idx}][size_ml]"></div>
      <div><label>SKU</label><input name="variants[${idx}][sku]"></div>
      <div><label>الباركود</label><input name="variants[${idx}][barcode]"></div>
      <div><label>السعر</label><input type="number" step="0.01" name="variants[${idx}][price]" required></div>
      <div><label>تكلفة الشراء</label><input type="number" step="0.01" name="variants[${idx}][cost_price]"></div>
      <div><label>الكمية</label><input type="number" name="variants[${idx}][stock_qty]" value="0"></div>
    </div>
    <hr class="mt-3">
  </div>`;
  wrap.insertAdjacentHTML('beforeend', html);
  idx++;
}
</script>
@endpush
@endsection
