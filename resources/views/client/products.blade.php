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
        </div>

        <style>
            select option {
                background-color: #0f172a !important; 
                color: #ffffff !important;
            }
            /* تصغير حجم المدخلات لتناسب الصف الواحد */
            .filter-input-slim {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
                padding-left: 0.75rem !important;
                padding-right: 0.75rem !important;
            }
        </style>
        
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <div class="p-4 border-b border-white/5 bg-slate-950/35">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-6 gap-3 items-end">
                    
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 mr-1">المورد</label>
                        <select id="productsSupplierFilter"
                            class="filter-input-slim w-full rounded-xl bg-slate-900 border border-white/10 text-sm text-white focus:outline-none focus:border-sky-500 appearance-none cursor-pointer">
                            <option value="all" selected>كل الموردين</option>
                        </select>
                    </div>
        
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 mr-1">سعر من</label>
                        <input id="productsMinPrice" type="number" step="0.01" placeholder="0.00"
                            class="filter-input-slim w-full rounded-xl bg-slate-900 border border-white/10 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-sky-500">
                    </div>
        
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 mr-1">سعر إلى</label>
                        <input id="productsMaxPrice" type="number" step="0.01" placeholder="0.00"
                            class="filter-input-slim w-full rounded-xl bg-slate-900 border border-white/10 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-sky-500">
                    </div>
        
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 mr-1">خصم من %</label>
                        <input id="productsMinDiscount" type="number" step="0.1" placeholder="0%"
                            class="filter-input-slim w-full rounded-xl bg-slate-900 border border-white/10 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-sky-500">
                    </div>
        
                    <div>
                        <label class="block text-[10px] text-slate-400 mb-1 mr-1">خصم إلى %</label>
                        <input id="productsMaxDiscount" type="number" step="0.1" placeholder="100%"
                            class="filter-input-slim w-full rounded-xl bg-slate-900 border border-white/10 text-sm text-white placeholder:text-slate-600 focus:outline-none focus:border-sky-500">
                    </div>
        
                    <div>
                        <button id="clearProductsFiltersBtn" 
                            title="مسح الفلاتر"
                            class="w-full filter-input-slim rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-500 border border-white/5 transition-all flex items-center justify-center gap-2 group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span class="text-xs">مسح</span>
                        </button>
                    </div>
        
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

        // ── مشكلة 4: debounce على inputs الأرقام لمنع الـ API calls المتكررة ──
        let productsDebounceTimer;
        function debouncedLoad() {
            clearTimeout(productsDebounceTimer);
            productsDebounceTimer = setTimeout(() => loadProducts(1), 400);
        }

        productsSupplierFilter.addEventListener('change', () => loadProducts(1));
        productsMinPrice.addEventListener('input', debouncedLoad);
        productsMaxPrice.addEventListener('input', debouncedLoad);
        productsMinDiscount.addEventListener('input', debouncedLoad);
        productsMaxDiscount.addEventListener('input', debouncedLoad);
        // ─────────────────────────────────────────────────────────────────────

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

