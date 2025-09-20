@extends('layouts.app')
@php($title = 'شاشة الكاشير')

@section('content')
<div 
    x-data="pos()"
    x-init="init()"
    class="grid grid-cols-12 gap-4"
>
    {{-- LEFT: Scan & Cart --}}
    <section class="col-span-8 space-y-3">
        {{-- Scan/Add --}}
        <div class="bg-white rounded shadow p-3">
            <div class="flex items-end gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium mb-1">اسحب الباركود / ابحث بالباركود</label>
                    <input x-model="barcode" @keydown.enter.prevent="scan()"
                        type="text" placeholder="امسح الباركود هنا"
                        class="w-full border rounded p-2 mono" autofocus>
                    <p class="text-xs text-gray-500 mt-1">ENTER للإضافة السريعة</p>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">&nbsp;</label>
                    <button @click="scan()" class="btn btn-primary">إضافة</button>
                </div>
            </div>
            <template x-if="flash">
                <p class="mt-2 text-sm" x-text="flash" :class="flashClass"></p>
            </template>
        </div>

        {{-- Cart --}}
        <div class="bg-white rounded shadow">
            <div class="px-3 py-2 border-b flex items-center justify-between">
                <h2 class="font-semibold">عربة المشتريات</h2>
                <div class="text-sm">رقم الفاتورة: <span class="mono" x-text="order?.invoice_no ?? '—'"></span></div>
            </div>
            <div class="overflow-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-right">الصنف</th>
                            <th class="px-3 py-2 text-right">السعر</th>
                            <th class="px-3 py-2 text-right">الكمية</th>
                            <th class="px-3 py-2 text-right">خصم</th>
                            <th class="px-3 py-2 text-right">الإجمالي</th>
                            <th class="px-3 py-2"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="item in order?.details ?? []" :key="item.id">
                            <tr class="border-t">
                                <td class="px-3 py-2">
                                    <div class="font-medium" x-text="item.product_name"></div>
                                    <div class="text-xs text-gray-500" x-text="item.variant_name"></div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" class="w-24 border rounded p-1 text-right"
                                        x-model.number="item.unit_price"
                                        @change="updateItem(item)">
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex items-center gap-1">
                                        <button class="btn btn-secondary" @click="item.quantity = Math.max(1, (item.quantity||1)-1); updateItem(item)">-</button>
                                        <input type="number" class="w-16 border rounded p-1 text-center" x-model.number="item.quantity" @change="updateItem(item)">
                                        <button class="btn btn-secondary" @click="item.quantity = (item.quantity||1)+1; updateItem(item)">+</button>
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <input type="number" step="0.01" class="w-24 border rounded p-1 text-right"
                                        x-model.number="item.line_discount"
                                        @change="updateItem(item)">
                                </td>
                                <td class="px-3 py-2 mono" x-text="money(item.line_total)"></td>
                                <td class="px-3 py-2 text-left">
                                    <button class="btn btn-danger" @click="removeItem(item)">حذف</button>
                                </td>
                            </tr>
                        </template>
                        <tr x-show="!order || (order.details?.length ?? 0) === 0">
                            <td class="px-3 py-6 text-center text-gray-500" colspan="6">لا توجد عناصر</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    {{-- RIGHT: Totals & Payment --}}
    <aside class="col-span-4 space-y-3">
        {{-- Start / Order controls --}}
        <div class="bg-white rounded shadow p-3">
            <div class="flex items-center gap-2">
                <button class="btn btn-primary" @click="startOrder()">فاتورة جديدة</button>
                <button class="btn btn-secondary" :disabled="!order" @click="reload()">تحديث</button>
            </div>
        </div>

        {{-- Discounts / VAT --}}
        <div class="bg-white rounded shadow p-3 space-y-2">
            <div>
                <label class="block text-sm font-medium mb-1">خصم على الفاتورة</label>
                <div class="flex gap-2">
                    <input type="number" step="0.01" class="w-32 border rounded p-2 text-right"
                           x-model.number="order.discount"
                           @change="applyOrderDiscount()">
                    <span class="self-center text-xs text-gray-500">LYD</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">ضريبة (قيمة مُضافة)</label>
                <div class="flex gap-2">
                    <input type="number" step="0.01" class="w-32 border rounded p-2 text-right"
                           x-model.number="order.vat">
                    <button class="btn btn-secondary" @click="setVat()">تطبيق الضريبة</button>
                </div>
            </div>
        </div>

        {{-- Totals --}}
        <div class="bg-white rounded shadow">
            <div class="px-3 py-2 border-b">
                <h3 class="font-semibold">الإجماليات</h3>
            </div>
            <div class="p-3 space-y-1 text-sm">
                <div class="flex justify-between">
                    <span>عدد الأصناف</span>
                    <span class="mono" x-text="order?.total_items ?? 0"></span>
                </div>
                <div class="flex justify-between">
                    <span>المجموع قبل الخصم</span>
                    <span class="mono" x-text="money(order?.sub_total ?? 0)"></span>
                </div>
                <div class="flex justify-between">
                    <span>خصم الفاتورة</span>
                    <span class="mono" x-text="money(order?.discount ?? 0)"></span>
                </div>
                <div class="flex justify-between">
                    <span>الضريبة</span>
                    <span class="mono" x-text="money(order?.vat ?? 0)"></span>
                </div>
                <hr>
                <div class="flex justify-between text-lg font-bold">
                    <span>الإجمالي</span>
                    <span class="mono" x-text="money(order?.total ?? 0)"></span>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="bg-white rounded shadow p-3 space-y-3">
            <div>
                <label class="block text-sm font-medium mb-1">طريقة الدفع</label>
                <select class="w-full border rounded p-2"
                    x-model="payment_method"
                    @change="setPayment()">
                    <option value="cash">نقدي</option>
                    <option value="edfali">ادفعلي</option>
                    <option value="mobicash">موبي كاش</option>
                    <option value="local_service">خدمات محلية</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium mb-1">المبلغ المدفوع (اختياري للجزئي)</label>
                <input type="number" step="0.01" class="w-full border rounded p-2 text-right mono"
                    x-model.number="paid_amount" placeholder="اتركه فارغاً لاعتبارها مدفوعة بالكامل">
            </div>

            <div class="flex items-center gap-2">
                <button class="btn btn-primary flex-1" :disabled="!order" @click="checkout()">تحصيل / إنهاء</button>
                <button class="btn btn-danger" :disabled="!order" @click="voidOrder()">إلغاء</button>
            </div>

            <template x-if="order?.payment_status">
                <div>
                    <span class="badge" :class="{
                        'badge-green': order.payment_status==='paid',
                        'badge-yellow': order.payment_status==='partial',
                        'badge-red': order.payment_status==='unpaid',
                    }" x-text="'حالة الدفع: ' + showPayment(order.payment_status)"></span>
                </div>
            </template>
        </div>
    </aside>
</div>
@endsection

@push('scripts')
<script>
function pos() {
    const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    return {
        order: null,
        barcode: '',
        payment_method: 'cash',
        paid_amount: null,
        flash: '',
        flashClass: 'text-green-600',

        api: {
            startOrder:      `{{ route('cashier.orders.start') }}`,
            applyDiscount:   (id)        => `{{ url('cashier/orders') }}/${id}/apply-discount`,
            setPayment:      (id)        => `{{ url('cashier/orders') }}/${id}/set-payment`,
            checkout:        (id)        => `{{ url('cashier/orders') }}/${id}/checkout`,
            void:            (id)        => `{{ url('cashier/orders') }}/${id}/void`,
            refund:          (id)        => `{{ url('cashier/orders') }}/${id}/refund`,
            addItem:         (id)        => `{{ url('cashier/orders') }}/${id}/items`,
            updateItem:      (id,detail) => `{{ url('cashier/orders') }}/${id}/items/${detail}`,
            removeItem:      (id,detail) => `{{ url('cashier/orders') }}/${id}/items/${detail}`,
            lookupBarcode:   (code)      => `{{ url('cashier/lookup/barcode') }}/${encodeURIComponent(code)}`,
        },

        init() { this.startOrder(); },

        async startOrder() {
            const res = await fetch(this.api.startOrder, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept': 'application/json' }
            });
            this.order = await res.json();
            this.payment_method = this.order?.payment_method ?? 'cash';
            this.paid_amount = null;
        },

        async scan() {
            if (!this.order) return this.flashError('ابدأ فاتورة أولاً');
            const code = (this.barcode || '').trim();
            if (!code) return;

            try {
                const look = await fetch(this.api.lookupBarcode(code), { headers: { 'Accept':'application/json' }});
                if (!look.ok) throw new Error('غير موجود');
                const data = await look.json();

                await this.addVariant(data.variant.id);
                this.flashOk(`تمت إضافة: ${data.product.name} - ${data.variant.name || data.variant.barcode}`);
            } catch {
                this.flashError('لم يتم العثور على الصنف');
            } finally {
                this.barcode = '';
            }
        },

        async addVariant(variantId, qty=1) {
            const res = await fetch(this.api.addItem(this.order.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ variant_id: variantId, quantity: qty })
            });
            this.order = await res.json();
        },

        async updateItem(item) {
            const res = await fetch(this.api.updateItem(this.order.id, item.id), {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    quantity: item.quantity,
                    unit_price: item.unit_price,
                    line_discount: item.line_discount
                })
            });
            this.order = await res.json();
        },

        async removeItem(item) {
            const res = await fetch(this.api.removeItem(this.order.id, item.id), {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': token, 'Accept':'application/json' }
            });
            this.order = await res.json();
        },

        async applyOrderDiscount() {
            if (!this.order) return;
            const res = await fetch(this.api.applyDiscount(this.order.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ discount: this.order.discount || 0 })
            });
            this.order = await res.json();
        },

        async setVat() {
            if (!this.order) return;
            this.flashOk('تم ضبط الضريبة مؤقتاً — سيتم تطبيقها عند التحصيل.');
        },

        async setPayment() {
            if (!this.order) return;
            const res = await fetch(this.api.setPayment(this.order.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ payment_method: this.payment_method, paid_amount: this.paid_amount })
            });
            this.order = await res.json();
        },

        async checkout() {
            if (!this.order) return;
            const res = await fetch(this.api.checkout(this.order.id), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ paid_amount: this.paid_amount, vat: this.order.vat })
            });

            if (!res.ok) {
                const err = await res.text();
                return this.flashError(err || 'فشل التحصيل (تحقق من المخزون)');
            }

            const data = await res.json();
            this.order = data.order;
            this.flashOk('تم التحصيل بنجاح. اطبع الإيصال.');
            // window.open(`{{ url('cashier/orders') }}/${this.order.id}/receipt`, '_blank');
        },

        async voidOrder() {
            if (!this.order) return;
            const res = await fetch(this.api.void(this.order.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept':'application/json' }
            });
            this.order = await res.json();
            this.flashOk('تم إلغاء الفاتورة.');
        },

        async refundOrder() {
            if (!this.order) return;
            const res = await fetch(this.api.refund(this.order.id), {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': token, 'Accept':'application/json' }
            });
            if (!res.ok) return this.flashError('لا يمكن رد فاتورة غير مدفوعة');
            this.order = await res.json();
            this.flashOk('تم رد المبلغ وإرجاع المخزون.');
        },

        reload() { if (this.order) this.startOrder(); },

        money(v) {
            const n = Number(v || 0);
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' LYD';
        },

        showPayment(s) {
            return s === 'paid' ? 'مدفوعة' : s === 'partial' ? 'مدفوع جزئياً' : 'غير مدفوعة';
        },

        flashOk(msg) { this.flash = msg; this.flashClass = 'text-green-600'; setTimeout(()=>this.flash='', 2500); },
        flashError(msg) { this.flash = msg; this.flashClass = 'text-red-600'; setTimeout(()=>this.flash='', 3000); },
    }
}
</script>
@endpush
