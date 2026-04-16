@extends('layouts.client')

@section('title', 'كل المنتجات')

@section('content')
    <style>
        .badge-price {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>
    <div class="space-y-6">
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center">📦</div>
                <h4 class="text-xl font-bold text-white">كل المنتجات</h4>
            </div>
            <p class="text-sm text-slate-400">اختر المورد والسعر/الخصم ثم اعرض النتائج.</p>
        </div>

        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-4 border-b border-white/5 bg-slate-950/35">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs text-slate-300 mb-2">فلتر الموردين</label>
                        <select id="productsSupplierFilter"
                            class="w-full rounded-2xl bg-slate-950/60 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:border-sky-500">
                            <option value="all" selected>كل الموردين</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-2">سعر من</label>
                        <input id="productsMinPrice" type="number" step="0.01" placeholder="سعر من"
                            class="w-full rounded-2xl bg-slate-950/60 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-2">سعر إلى</label>
                        <input id="productsMaxPrice" type="number" step="0.01" placeholder="سعر إلى"
                            class="w-full rounded-2xl bg-slate-950/60 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-sky-500">
                    </div>
                    <div>
                        <label class="block text-xs text-slate-300 mb-2">خصم من %</label>
                        <input id="productsMinDiscount" type="number" step="0.1" placeholder="خصم من %"
                            class="w-full rounded-2xl bg-slate-950/60 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-sky-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3 mt-3">
                    <div>
                        <label class="block text-xs text-slate-300 mb-2">خصم إلى %</label>
                        <input id="productsMaxDiscount" type="number" step="0.1" placeholder="خصم إلى %"
                            class="w-full rounded-2xl bg-slate-950/60 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:border-sky-500">
                    </div>
                    <div class="md:col-span-3 lg:col-span-3 flex items-end justify-end">
                        <button id="clearProductsFiltersBtn"
                            class="px-6 py-3 rounded-2xl bg-slate-800 hover:bg-slate-700 text-slate-200 text-sm">
                            مسح الفلاتر
                        </button>
                    </div>
                </div>
            </div>

            <div class="p-4">
                <div class="custom-table-card overflow-hidden">
                    <table class="w-full text-sm text-right">
                        <thead class="bg-slate-950/50 text-slate-400">
                            <tr>
                                <th class="p-4">المورد</th>
                                <th class="p-4">المنطقة</th>
                                <th class="p-4">تليفون المورد</th>
                                <th class="p-4">الصنف</th>
                                <th class="p-4">السعر</th>
                                <th class="p-4">الخصم</th>
                                <th class="p-4">الإجراء</th>
                            </tr>
                        </thead>
                        <tbody id="productsTableBody"></tbody>
                    </table>
                </div>

                <div id="productsPagination" class="mt-4"></div>
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script>
        let currentPage = 1;
        let lastApiResponse = null;

        const productsTableBody = document.getElementById('productsTableBody');
        const productsPagination = document.getElementById('productsPagination');

        const productsSupplierFilter = document.getElementById('productsSupplierFilter');
        const productsMinPrice = document.getElementById('productsMinPrice');
        const productsMaxPrice = document.getElementById('productsMaxPrice');
        const productsMinDiscount = document.getElementById('productsMinDiscount');
        const productsMaxDiscount = document.getElementById('productsMaxDiscount');
        const clearProductsFiltersBtn = document.getElementById('clearProductsFiltersBtn');

        function escapeForAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function getFiltersPayload() {
            const payload = {};
            const supplierId = productsSupplierFilter.value;
            if (supplierId !== 'all') payload.supplier_id = supplierId;

            if (productsMinPrice.value !== '') payload.min_price = productsMinPrice.value;
            if (productsMaxPrice.value !== '') payload.max_price = productsMaxPrice.value;
            if (productsMinDiscount.value !== '') payload.min_discount = productsMinDiscount.value;
            if (productsMaxDiscount.value !== '') payload.max_discount = productsMaxDiscount.value;

            payload.page = currentPage;
            return payload;
        }

        async function loadProducts(page = 1) {
            currentPage = page;
            try {
                const res = await axios.get('/products', {
                    params: getFiltersPayload()
                });
                lastApiResponse = res.data || null;
                renderProducts(lastApiResponse);
            } catch (err) {
                console.error(err);
                productsTableBody.innerHTML = '<tr><td colspan="7" class="p-6 text-center text-rose-400">فشل تحميل المنتجات.</td></tr>';
            }
        }

        function renderProducts(response) {
            const data = response?.data || [];

            const suppliers = response?.suppliers || [];
            if (suppliers.length) {
                productsSupplierFilter.innerHTML = `
                    <option value="all" selected>كل الموردين</option>
                    ${suppliers.map(s => `<option value="${s.id}">${escapeForAttr(s.name)}</option>`).join('')}
                `;
            }

            if (!data.length) {
                productsTableBody.innerHTML = '<tr><td colspan="7" class="p-6 text-center text-slate-500">لا توجد نتائج.</td></tr>';
                productsPagination.innerHTML = '';
                return;
            }

            // حساب (lowest price / best discount) داخل الصفحة فقط (تزيين UI فقط)
            const perProduct = new Map();
            data.forEach(row => {
                const id = row.product_id;
                if (!perProduct.has(id)) perProduct.set(id, []);
                perProduct.get(id).push(row);
            });

            perProduct.forEach(rows => {
                const prices = rows.map(r => Number(r.price));
                const discounts = rows.map(r => Number(r.discount));
                const lowestPrice = prices.length ? Math.min(...prices) : null;
                const highestDiscount = discounts.length ? Math.max(...discounts) : null;
                rows.forEach(r => {
                    r.is_lowest_price = lowestPrice !== null && Number(r.price) === lowestPrice;
                    r.is_best_discount = highestDiscount !== null && Number(r.discount) === highestDiscount;
                });
            });

            productsTableBody.innerHTML = data.map(row => {
                return `
                    <tr class="${row.is_lowest_price ? 'bg-sky-500/5' : ''} border-t border-white/5">
                        <td class="p-4">${escapeForAttr(row.supplier || '-')}</td>
                        <td class="p-4">${escapeForAttr(row.area || '-')}</td>
                        <td class="p-4">${escapeForAttr(row.supplier_phone || '-')}</td>
                        <td class="p-4 font-semibold text-white">${escapeForAttr(row.product_name || '-')}</td>
                        <td class="p-4"><span class="badge-price">${row.price} ج</span></td>
                        <td class="p-4"><span class="${row.is_best_discount ? 'text-green-400' : 'text-green-400'}">${row.discount}%</span></td>
                        <td class="p-4">
                            <button onclick="addFavorite(${row.product_id}, this)" class="text-rose-400 hover:text-rose-500">❤️</button>
                        </td>
                    </tr>
                `;
            }).join('');

            renderPagination(response);
        }

        function renderPagination(response) {
            const current = response.current_page || 1;
            const last = response.last_page || 1;

            productsPagination.innerHTML = `
                <div class="flex items-center justify-between text-xs text-slate-400">
                    <span>صفحة ${current} / ${last}</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-slate-800 ${current <= 1 ? 'opacity-50 cursor-not-allowed' : ''}"
                            ${current <= 1 ? 'disabled' : ''} onclick="loadProducts(${current - 1})">السابق</button>
                        <button class="px-3 py-1 rounded bg-slate-800 ${current >= last ? 'opacity-50 cursor-not-allowed' : ''}"
                            ${current >= last ? 'disabled' : ''} onclick="loadProducts(${current + 1})">التالي</button>
                    </div>
                </div>
            `;
        }

        clearProductsFiltersBtn.addEventListener('click', () => {
            productsSupplierFilter.value = 'all';
            productsMinPrice.value = '';
            productsMaxPrice.value = '';
            productsMinDiscount.value = '';
            productsMaxDiscount.value = '';
            loadProducts(1);
        });

        productsSupplierFilter.addEventListener('change', () => loadProducts(1));
        productsMinPrice.addEventListener('input', () => loadProducts(1));
        productsMaxPrice.addEventListener('input', () => loadProducts(1));
        productsMinDiscount.addEventListener('input', () => loadProducts(1));
        productsMaxDiscount.addEventListener('input', () => loadProducts(1));

        async function addFavorite(productId, button) {
            try {
                if (button) button.disabled = true;
                await axios.post('/favorites', { product_id: productId });
                clientNotify('تمت الإضافة للمفضلة', 'success');
            } catch (error) {
                console.error(error);
                clientNotify('فشل حفظ المنتج في المفضلة. حاول مرة أخرى.', 'error');
            } finally {
                if (button) button.disabled = false;
            }
        }

        document.addEventListener('DOMContentLoaded', () => loadProducts(1));
    </script>
@endpush

