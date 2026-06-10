@extends('layouts.client')

@section('body_class', 'search-scene')

@section('content')
    <style>
        /* تطبيق الخلفية التي اخترتها */
        body.search-scene {
            background-image: radial-gradient(circle at top left, rgba(125, 211, 252, 0.1), rgba(15, 23, 42, 0.9)),
                url('/images/abstract-digital-grid-black-background.jpg');
            /* تأكد من مسار الصورة الصحيح */
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .search-shell {
            min-height: calc(100vh - 150px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            /* جعل البحث في منتصف الصفحة */
            transition: all 0.5s ease;
        }

        /* عندما تظهر النتائج، يرتفع شريط البحث للأعلى قليلاً */
        .search-shell.has-results {
            justify-content: flex-start;
            padding-top: 2rem;
        }

        .search-container {
            width: min(100%, 850px);
            position: relative;
            z-index: 10;
        }

        .search-box-wrapper {
            display: flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 18px;
            padding: 8px 12px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            transition: border-color 0.3s;
        }

        .search-box-wrapper:focus-within {
            border-color: #38bdf8;
        }

        .search-input {
            flex: 1;
            background: transparent;
            border: none;
            color: white;
            padding: 12px 15px;
            font-size: 1.1rem;
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(226, 232, 240, 0.4);
        }

        /* إحصائيات الخصومات (مشكلة 6) */
        .discount-stats-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(15, 23, 42, 0.5);
            border: 1px solid rgba(56, 189, 248, 0.15);
            border-radius: 12px;
            animation: fadeIn 0.3s ease;
        }

        .discount-stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.3rem 0.75rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.12);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
            cursor: pointer;
            transition: all 0.15s;
        }

        .discount-stat-chip:hover {
            background: rgba(34, 197, 94, 0.25);
        }

        .discount-stat-chip .chip-count {
            background: rgba(34, 197, 94, 0.2);
            border-radius: 999px;
            padding: 0 0.4rem;
            font-size: 0.72rem;
            color: #86efac;
        }

        .brand-header {
            display: inline-block;
            margin-bottom: 2rem;
            text-align: center;
            line-height: 1.1;
        }

        .brand-header .brand-name {
            font-size: 3rem;
            font-weight: 900;
            color: #f8fafc;
            letter-spacing: 0.08em;
            text-shadow: 0 20px 45px rgba(0, 0, 0, 0.25);
            margin: 0;
        }

        .brand-header .brand-name span {
            color: #991b1b;
            letter-spacing: 0.15em;
        }

        .brand-header .brand-tagline {
            margin: 0.75rem auto 0;
            font-size: 1.05rem;
            color: rgba(226, 232, 240, 0.82);
            opacity: 0.95;
            letter-spacing: 0.02em;
        }

        .brand-header .brand-line {
            margin: 1rem auto 0.75rem;
            width: 4rem;
            height: 2px;
            background: linear-gradient(90deg, rgba(56, 189, 248, 0.9), rgba(56, 189, 248, 0));
            border-radius: 999px;
        }

        /* جدول النتائج */
        .results-container {
            width: min(100%, 1100px);
            margin-top: 2rem;
            display: none;
            /* مخفي افتراضياً */
            animation: fadeIn 0.4s ease;
        }

        .results-container.show {
            display: block;
        }

        .custom-table-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            overflow: hidden;
        }

        .result-table thead {
            background: rgba(56, 189, 248, 0.1);
        }

        .result-table th {
            color: #38bdf8;
            text-align: right;
            padding: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .result-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
            color: #e2e8f0;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .badge-price {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge-price-good {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge-price-bad {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge-pill-neutral {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }
    </style>

    <div class="search-shell" id="searchShell">
        <div class="search-container text-center">
            <div class="brand-header">
                <h1 class="brand-name">Med <span>RANKO</span></h1>
                <div class="brand-line"></div>
                <p class="brand-tagline">رتب صح .. ووفر أكتر</p>
            </div>

            <div class="search-box-wrapper">
                <input type="text" id="searchInput" placeholder="ابحث باسم الصنف أو الدواء..." class="search-input"
                    oninput="debouncedSearch()">
                <div class="px-3 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>

            {{-- input ملف Excel مخفي — لا يزال مطلوباً لخاصية رفع الشيت --}}
            <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden" />

            {{-- إحصائيات الخصومات (مشكلة 6) — تظهر فقط عند كتابة سعر --}}
            <div id="discountStatsBar" class="discount-stats-bar" style="display:none;"></div>
        </div>

        <div class="results-container" id="resultsWrap">
            <!-- Filters Section -->
            <div class="filters-section mb-4" id="filtersSection" style="display: none;">
                <div class="custom-table-card p-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-2">تصفية حسب السعر</label>
                            <input type="number" id="priceFilter" placeholder="سعر المنتج"
                                class="w-full px-3 py-2 bg-slate-800 border border-slate-600 rounded-lg text-slate-200 focus:border-sky-500 focus:outline-none text-right"
                                dir="rtl">
                        </div>

                        <style>
                            select option {
                                background-color: #0f172a !important;
                                /* لون الصفحة الداكن */
                                color: #ffffff !important;
                            }

                            .filter-slim {
                                padding: 0.5rem 0.75rem !important;
                            }
                        </style>

                        <div class="relative group">
                            <label class="block text-sm font-medium text-slate-300 mb-2">تصفية حسب التاريخ</label>
                            <select id="dateFilter"
                                class="filter-slim w-full rounded-xl bg-slate-900/60 border border-white/5 text-sm text-white focus:outline-none focus:border-sky-500/50 appearance-none cursor-pointer transition-all"
                                dir="rtl">
                                <option value="" class="bg-slate-950">الكل</option>
                                <option value="24" class="bg-slate-950">آخر 24 ساعة</option>
                                <option value="48" class="bg-slate-950">آخر 48 ساعة</option>
                                <option value="72" class="bg-slate-950">آخر 3 أيام</option>
                                <option value="168" class="bg-slate-950">آخر 7 أيام</option>
                                <option value="720" class="bg-slate-950">آخر 30 يوم</option>
                            </select>
                        </div>


                        <div class="flex items-end gap-2">
                            <button onclick="applyFilters()"
                                class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                                تطبيق
                            </button>
                            <button onclick="resetFilters()"
                                class="px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition">
                                إعادة ضبط
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="custom-table-card">
                <table class="result-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>المورد</th>
                            <th>الصنف</th>
                            <th>السعر</th>
                            <th>الخصم</th>
                            <th>تاريخ التحديث</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="resultsTable"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- تم إزالة Overlay الخاصة بعرض تفاصيل المنتج (البيانات تظهر مباشرة في الجدول) --}}
@endsection

@push('scripts')
    <script>
        let debounceTimer;
        const searchShell = document.getElementById('searchShell');
        const resultsWrap = document.getElementById('resultsWrap');
        const welcomeText = document.getElementById('welcomeText');
        const discountStatsBar = document.getElementById('discountStatsBar');

        // ── مشكلة 5: زر + أُزيل من HTML — لا يوجد listener هنا ─────────────
        // لو أردت إعادته لاحقاً: document.getElementById('uploadSheetBtn')?.addEventListener(...)

        document.getElementById('excelFile').addEventListener('change', function() {
            if (this.files?.length) uploadExcel();
        });

        function debouncedSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const q = document.getElementById('searchInput').value.trim();
                if (q.length >= 3) {
                    search(q);
                } else {
                    hideResults();
                }
            }, 300);
        }

        function showResults() {
            searchShell.classList.add('has-results');
            resultsWrap.classList.add('show');
            document.getElementById('filtersSection').style.display = 'block';
            if (welcomeText) {
                welcomeText.style.display = 'none'; // Hide welcome text when results appear
            }
        }

        function hideResults() {
            searchShell.classList.remove('has-results');
            resultsWrap.classList.remove('show');
            document.getElementById('filtersSection').style.display = 'none';
            discountStatsBar.style.display = 'none';
            discountStatsBar.innerHTML = '';
            if (welcomeText) {
                welcomeText.style.display = 'block';
            }
            document.getElementById('resultsTable').innerHTML = '';
        }

        async function search(q) {
            try {
                const filters = getActiveFilters();
                const res = await axios.get('/search', {
                    params: {
                        q,
                        ...filters
                    }
                });

                // ── إحصائيات الموردين حسب الخصم — تظهر فقط بعد تطبيق فلتر ──
                // تظهر لما المستخدم يضغط "تطبيق" أو يكتب سعر أو يختار تاريخ
                const filtersActive = !!(filters.price || filters.date_filter);
                if (filtersActive) {
                    renderDiscountStats(res.data.discount_stats || []);
                } else {
                    // بحث عادي بدون فلتر — نخفي الـ bar
                    discountStatsBar.style.display = 'none';
                    discountStatsBar.innerHTML = '';
                }
                // ─────────────────────────────────────────────────────────────

                renderResults(res.data.results);
                showResults();
            } catch (err) {
                console.error(err);
                clientNotify('حدث خطأ أثناء البحث', 'error');
            }
        }

        function getActiveFilters() {
            const priceFilter = document.getElementById('priceFilter').value;
            const dateFilter = document.getElementById('dateFilter').value;

            const filters = {};
            if (priceFilter) filters.price = priceFilter;
            if (dateFilter) filters.date_filter = dateFilter;

            return filters;
        }

        function applyFilters() {
            const query = document.getElementById('searchInput').value.trim();
            if (query.length >= 3) {
                search(query);
            }
        }

        function resetFilters() {
            document.getElementById('priceFilter').value = '';
            document.getElementById('dateFilter').value = '';

            // إخفاء الـ bar فور الـ reset
            discountStatsBar.style.display = 'none';
            discountStatsBar.innerHTML = '';

            const query = document.getElementById('searchInput').value.trim();
            if (query.length >= 3) {
                search(query);
            }
        }

        let lastFlatOffers = [];

        function escapeForAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        // ── رسم شريط إحصائيات الخصومات ─────────────────────────────────────
        let activeDiscountFilter = null; // الخصم المختار حالياً (null = الكل)

        function renderDiscountStats(stats) {
            if (!stats || stats.length === 0) {
                discountStatsBar.style.display = 'none';
                discountStatsBar.innerHTML = '';
                return;
            }

            discountStatsBar.innerHTML =
                '<span class="text-slate-400 text-xs ml-2">الموردين حسب الخصم:</span>' +
                // زر "الكل" للرجوع
                `<span class="discount-stat-chip" id="discountChipAll"
                      onclick="applyDiscountFilter(null)"
                      style="background:rgba(148,163,184,0.12);color:#cbd5e1;border-color:rgba(148,163,184,0.2);"
                      title="عرض كل النتائج">
                    الكل
                </span>` +
                stats.map(stat => `
                    <span class="discount-stat-chip"
                          data-discount-val="${stat.discount}"
                          onclick="applyDiscountFilter(${stat.discount})"
                          title="اضغط لعرض عروض خصم ${stat.discount}% فقط">
                        خصم ${stat.discount}%
                        <span class="chip-count">${stat.suppliers_count} مورد</span>
                    </span>
                `).join('');

            discountStatsBar.style.display = 'flex';
            activeDiscountFilter = null;
        }

        function applyDiscountFilter(discountValue) {
            activeDiscountFilter = discountValue;

            const rows = document.querySelectorAll('#resultsTable tr[data-discount]');

            if (discountValue === null) {
                // إظهار الكل
                rows.forEach(row => row.style.display = '');
            } else {
                // مقارنة بـ Math.abs للتعامل مع دقة الـ float
                rows.forEach(row => {
                    const rowDiscount = parseFloat(row.dataset.discount || '0');
                    // نستخدم epsilon صغير للتعامل مع فروق الدقة العشرية
                    row.style.display = Math.abs(rowDiscount - discountValue) < 0.001 ? '' : 'none';
                });
            }

            // تحديث تمييز الـ chips
            document.querySelectorAll('.discount-stat-chip').forEach(chip => {
                const chipVal = chip.dataset.discountVal;
                if (discountValue === null) {
                    // الكل مختار
                    chip.style.opacity = '1';
                    chip.style.fontWeight = chip.id === 'discountChipAll' ? '700' : '';
                } else if (chipVal !== undefined) {
                    const match = Math.abs(parseFloat(chipVal) - discountValue) < 0.001;
                    chip.style.opacity = match ? '1' : '0.4';
                    chip.style.fontWeight = match ? '700' : '';
                } else {
                    // زر "الكل"
                    chip.style.opacity = '0.4';
                    chip.style.fontWeight = '';
                }
            });
        }
        // ──────────────────────────────────────────────────────────────────────

        function renderResults(results) {
            const table = document.getElementById('resultsTable');

            // إعادة ضبط الـ chips عند render جديد
            activeDiscountFilter = null;
            document.querySelectorAll('.discount-stat-chip').forEach(chip => {
                chip.style.opacity = '1';
                chip.style.fontWeight = '';
            });

            if (!results || results.length === 0) {
                table.innerHTML =
                    '<tr><td colspan="6" class="p-8 text-center text-slate-500">لا توجد نتائج مطابقة لبحثك أو لا توجد عروض متاحة للمنتجات الموجودة.</td></tr>';
                return;
            }

            table.innerHTML = results.flatMap(item => {
                const productName = item.name_ar || item.name_en || '-';
                const offers = item.offers || [];

                if (offers.length === 0) {
                    return `<tr>
                        <td class="p-4">-</td>
                        <td class="p-4 font-bold">${escapeForAttr(productName)}</td>
                        <td colspan="4" class="p-4 text-slate-500">لا توجد عروض حالياً</td>
                    </tr>`;
                }

                return offers.map(offer => `
                    <tr class="${offer.is_lowest_price ? 'bg-sky-500/5' : ''}" data-discount="${parseFloat(offer.discount).toFixed(4)}">
                        <td class="p-4">${escapeForAttr(offer.supplier)}</td>
                        <td class="p-4 font-bold">${escapeForAttr(productName)}</td>
                        <td class="p-4"><span class="badge-price">${offer.price} ج</span></td>
                        <td class="p-4"><span class="${offer.is_best_discount ? 'text-green-400 font-bold' : 'text-green-400'}">${offer.discount}%</span></td>
                        <td class="p-4"><span class="badge-pill-neutral">${escapeForAttr(offer.upload_date || '-')}</span></td>
                        <td class="p-4">
                            <div class="flex items-center justify-center gap-2" dir="ltr">
                                <button onclick="addFavorite(${item.id}, this)" class="text-rose-400 hover:text-rose-500 transition-transform hover:scale-110">
                                    ❤️
                                </button>
                            </div>
                        </td>
                    </tr>
                `);
            }).join('');
        }

        async function addFavorite(productId, button) {
            try {
                if (button) {
                    button.disabled = true;
                }

                await axios.post('/favorites', {
                    product_id: productId
                });
                clientNotify('تمت الإضافة للمفضلة', 'success');
            } catch (error) {
                console.error(error);
                clientNotify('فشل حفظ المنتج في المفضلة. حاول مرة أخرى.', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                }
            }
        }

        async function uploadExcel() {
            const input = document.getElementById('excelFile');
            const file = input.files?.[0];
            if (!file) return;

            const table = document.getElementById('resultsTable');
            const formData = new FormData();
            formData.append('file', file);
            formData.append('log_mode', 'bulk');
            formData.append('limit', '1000');

            try {
                showResults();
                table.innerHTML =
                    '<tr><td colspan="6" class="p-8 text-center text-slate-400">جاري قراءة الشيت والبحث...</td></tr>';

                const res = await axios.post('/search/from-excel', formData);
                const lines = res.data?.lines || [];

                if (!lines.length) {
                    table.innerHTML =
                        '<tr><td colspan="6" class="p-8 text-center text-slate-500">لا توجد أصناف صالحة للبحث داخل الشيت.</td></tr>';
                    return;
                }

                const mergedResults = lines.flatMap(line => line?.results || []);
                if (!mergedResults.length) {
                    table.innerHTML =
                        '<tr><td colspan="6" class="p-8 text-center text-slate-500">تمت قراءة الشيت لكن لا توجد نتائج مطابقة.</td></tr>';
                    return;
                }

                renderResults(mergedResults);
                clientNotify('تمت قراءة الشيت بنجاح', 'success');
            } catch (error) {
                console.error(error);
                const firstValidationError = error.response?.data?.errors ?
                    Object.values(error.response.data.errors)?.flat()?.[0] :
                    null;
                const serverMsg = firstValidationError || error.response?.data?.message || 'فشل رفع الشيت أو قراءته.';
                table.innerHTML =
                    `<tr><td colspan="6" class="p-8 text-center text-rose-400">${escapeForAttr(serverMsg)}</td></tr>`;
                clientNotify(serverMsg, 'error');
            } finally {
                input.value = '';
            }
        }
    </script>
@endpush
