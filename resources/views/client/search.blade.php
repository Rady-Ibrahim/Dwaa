@extends('layouts.client')

@section('body_class', 'search-scene')

@section('content')
    <style>
        body.search-scene {
            background:
                radial-gradient(circle at top left, rgba(239, 35, 60, 0.15), transparent 20%),
                radial-gradient(circle at bottom right, rgba(239, 35, 60, 0.08), transparent 24%),
                var(--client-bg);
            background-attachment: fixed;
            background-size: cover;
        }

        .search-shell {
            min-height: calc(100vh - 120px);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.5s ease;
            padding: 1rem 0 2rem;
        }

        .search-shell.has-results {
            justify-content: flex-start;
            padding-top: 1.5rem;
        }

        .search-container {
            width: min(100%, 980px);
            position: relative;
            z-index: 10;
            text-align: center;
        }

        .brand-header {
            margin-bottom: 2rem;
            display: inline-block;
            text-align: center;
            line-height: 1.1;
        }

        .brand-header .brand-name {
            margin: 0;
            font-size: clamp(2.8rem, 5vw, 5.2rem);
            font-weight: 900;
            letter-spacing: -0.06em;
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.1em;
        }

        .brand-header .brand-name .brand-red {
            color: var(--brand-red);
        }

        .brand-header .brand-name .brand-blue {
            color: var(--brand-blue);
        }

        .brand-header .brand-line {
            margin: 0.9rem auto 0.7rem;
            width: 4rem;
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0.85), rgba(140, 18, 40, 0.95));
            border-radius: 999px;
        }

        .brand-header .brand-tagline {
            margin: 0;
            color: var(--text-soft2);
            font-size: 1.15rem;
            letter-spacing: 0.02em;
        }

        .search-box-wrapper {
            display: flex;
            align-items: center;
            width: min(100%, 860px);
            margin: 0 auto;
            padding: 0.8rem 1rem 0.8rem 1.15rem;
            border-radius: 30px;
            background: rgba(15, 15, 15, 0.8);
            border: 2px solid rgba(136, 12, 34, 0.95);
            box-shadow: inset 0 0 0 1px rgba(136, 12, 34, 0.12), 0 0 22px rgba(136, 12, 34, 0.15);
            transition: all 0.2s ease;
        }

        .search-box-wrapper:focus-within {
            box-shadow: inset 0 0 0 1px rgba(136, 12, 34, 0.22), 0 0 28px rgba(136, 12, 34, 0.20);
            border-color: rgba(175, 20, 49, 0.95);
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            color: var(--text-main);
            font-size: 1.1rem;
            padding: 0.95rem 1rem;
            outline: none;
        }

        .search-input::placeholder {
            color: rgba(var(--border-rgb), 0.9);
        }

        .search-icon-wrap {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: rgba(136, 12, 34, 0.14);
            color: var(--accent-soft);
            border: 1px solid rgba(136, 12, 34, 0.28);
        }

        .feature-grid {
            width: min(100%, 980px);
            margin: 2.2rem auto 0;
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 1.2rem;
        }

        .feature-card {
            background: rgba(var(--surface-rgb), 0.72);
            border: 1px solid rgba(var(--border-rgb), 0.18);
            border-radius: 24px;
            padding: 1.5rem 1rem 1.2rem;
            min-height: 180px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: var(--text-soft2);
            box-shadow: 0 18px 35px rgba(2, 6, 23, 0.22);
        }

        .feature-card:nth-child(1) {
            border-color: rgba(96, 165, 250, 0.28);
        }

        .feature-card:nth-child(2) {
            border-color: rgba(52, 211, 153, 0.28);
        }

        .feature-card:nth-child(3) {
            border-color: rgba(168, 85, 247, 0.28);
        }

        .feature-card:nth-child(4) {
            border-color: rgba(244, 63, 94, 0.28);
        }

        .feature-card .icon-box {
            width: 64px;
            height: 64px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            background: rgba(59, 130, 246, 0.12);
            border: 1px solid rgba(59, 130, 246, 0.25);
            color: var(--accent-soft);
        }

        .feature-card:nth-child(2) .icon-box {
            background: rgba(34, 197, 94, 0.10);
            border-color: rgba(34, 197, 94, 0.22);
            color: var(--success-soft);
        }

        .feature-card:nth-child(3) .icon-box {
            background: rgba(168, 85, 247, 0.12);
            border-color: rgba(168, 85, 247, 0.24);
            color: var(--violet-soft);
        }

        .feature-card:nth-child(4) .icon-box {
            background: rgba(244, 63, 94, 0.10);
            border-color: rgba(244, 63, 94, 0.22);
            color: var(--danger-soft);
        }

        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 800;
            margin: 0;
            color: var(--text-main);
        }

        .feature-card p {
            margin: 0.5rem 0 0;
            font-size: 0.8rem;
            line-height: 1.7;
            color: var(--text-soft);
        }

        .discount-stats-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.75rem;
            padding: 0.75rem 1rem;
            background: rgba(var(--surface-rgb), 0.5);
            border: 1px solid rgba(56, 189, 248, 0.15);
            border-radius: 12px;
            animation: fadeIn 0.3s ease;
            justify-content: center;
        }

        .discount-stat-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.8rem;
            border-radius: 999px;
            font-size: 0.78rem;
            font-weight: 600;
            background: rgba(34, 197, 94, 0.12);
            color: var(--success-soft);
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
            color: var(--success-soft);
        }

        .results-container {
            width: min(100%, 1100px);
            margin-top: 2rem;
            display: none;
            animation: fadeIn 0.4s ease;
        }

        .results-container.show {
            display: block;
        }

        .custom-table-card {
            background: rgba(var(--surface-rgb), 0.72);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(var(--overlay-rgb), 0.06);
            border-radius: 22px;
            overflow-x: auto;
            box-shadow: 0 22px 40px rgba(2, 6, 23, 0.25);
        }

        .result-table thead {
            background: rgba(var(--surface-rgb), 0.86);
        }

        .result-table th {
            color: var(--accent-soft);
            text-align: right;
            padding: 15px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .result-table td {
            padding: 15px;
            border-bottom: 1px solid rgba(var(--overlay-rgb), 0.04);
            color: var(--text-soft2);
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
            color: var(--success-soft);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge-price-bad {
            background: rgba(244, 63, 94, 0.15);
            color: var(--danger-soft);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        .badge-pill-neutral {
            background: rgba(var(--border-rgb), 0.15);
            color: var(--text-soft);
            padding: 4px 10px;
            border-radius: 8px;
            font-weight: 600;
        }

        @media (max-width: 980px) {
            .feature-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .feature-grid {
                grid-template-columns: 1fr;
            }

            .brand-header .brand-name {
                font-size: 2.5rem;
            }
        }
    </style>

    <div class="search-shell" id="searchShell">
        <div class="search-container">
            <div class="brand-header">
                <h1 class="brand-name">
                    <span class="brand-blue">RANKO</span><span class="brand-red">Med</span>
                </h1>
                <div class="brand-line"></div>
                <p class="brand-tagline">رتب صح .. ووفر أكتر</p>
            </div>

            <div class="search-box-wrapper">
                <input type="text" id="searchInput" placeholder="ابحث باسم الصنف أو الدواء..." class="search-input"
                    oninput="debouncedSearch()">
                <div class="search-icon-wrap">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>
            </div>



            <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden" />
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
                                background-color: var(--option-bg) !important;
                                /* لون الصفحة الداكن */
                                color: var(--option-color) !important;
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
