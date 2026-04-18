@extends('layouts.client')

@section('title', 'تفاصيل المقارنة المحفوظة')

@section('content')
    <style>
        select option {
            background-color: #0f172a !important;
            /* نفس لون السايد بار slate-900 */
            color: #ffffff !important;
        }
    </style>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 pb-10">

        <div class="bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-[2rem] overflow-hidden shadow-2xl">
            <div class="p-8">
                <div class="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-6">
                    <div class="space-y-2">
                        <div class="flex items-center gap-3">
                            <div class="w-2 h-8 bg-sky-500 rounded-full"></div>
                            <h3 id="comparisonTitle" class="text-2xl font-bold text-white tracking-tight">جاري تحميل
                                المقارنة...</h3>
                        </div>
                        <p id="comparisonMeta" class="text-sm text-slate-400 flex items-center gap-2">
                        </p>
                    </div>

                    <a href="/client/saved-comparisons"
                        class="flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-white border border-white/10 transition-all group">
                        <svg xmlns="http://www.w3.org/2000/svg"
                            class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        رجوع للمحفوظات
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-[2rem] p-6 shadow-xl">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
                <div class="relative group">
                    <input id="savedCompareSearchInput" type="text" placeholder="بحث باسم الصنف..."
                        class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all" />
                </div>

                <select id="savedPriceWinnerFilter"
                    class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                    <option value="all">فلتر السعر: الكل</option>
                    <option value="A">سعر A أقل</option>
                    <option value="B">سعر B أقل</option>
                    <option value="equal">متساوي</option>
                </select>

                <select id="savedDiscountWinnerFilter"
                    class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                    <option value="all">فلتر الخصم: الكل</option>
                    <option value="A">خصم A أعلى</option>
                    <option value="B">خصم B أعلى</option>
                    <option value="equal">متساوي</option>
                </select>

                <div class="flex gap-2">
                    <select id="savedSupplierWinnerFilter"
                        class="flex-1 rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                        <option value="all">المورد الأفضل: الكل</option>
                        <option value="A">المورد A أفضل</option>
                        <option value="B">المورد B أفضل</option>
                        <option value="equal">متساوي</option>
                    </select>

                    <button id="clearSavedCompareFiltersBtn" title="مسح الفلاتر"
                        class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-500 border border-white/10 transition-all flex items-center justify-center group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:rotate-12"
                            fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-[2rem] shadow-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse" id="comparisonTable">
                    <thead id="comparisonTableHead">
                        <!-- Table headers will be set dynamically -->
                    </thead>
                    <tbody id="savedComparisonTable" class="divide-y divide-white/[0.03]">
                    </tbody>
                </table>
            </div>
            <div id="savedComparisonPagination" class="p-6 bg-slate-950/30 border-t border-white/5"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const savedComparisonId = @json($savedComparisonId);
        const pageSize = 15;
        let allPairs = [];
        let filteredPairs = [];
        let currentPage = 1;
        let comparisonType = 'file_compare'; // 'file_compare' or 'platform_compare'

        const savedComparisonTable = document.getElementById('savedComparisonTable');
        const savedComparisonPagination = document.getElementById('savedComparisonPagination');
        const comparisonTableHead = document.getElementById('comparisonTableHead');
        const savedCompareSearchInput = document.getElementById('savedCompareSearchInput');
        const savedPriceWinnerFilter = document.getElementById('savedPriceWinnerFilter');
        const savedDiscountWinnerFilter = document.getElementById('savedDiscountWinnerFilter');
        const savedSupplierWinnerFilter = document.getElementById('savedSupplierWinnerFilter');
        const clearSavedCompareFiltersBtn = document.getElementById('clearSavedCompareFiltersBtn');

        function setupPlatformComparisonTable() {
            comparisonTableHead.innerHTML = `
                <tr class="bg-slate-950/50 text-slate-400 border-b border-white/5">
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider">الصنف من الملف</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">سعر الملف</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">خصم الملف</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider">الصنف المطابق</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider">أفضل مورد</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">سعر المنصة</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">خصم المنصة</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">فرق السعر</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">فرق الخصم</th>
                </tr>
            `;

            // Hide file comparison filters
            document.querySelector('.grid.grid-cols-1.md\\:grid-cols-2.lg\\:grid-cols-4').style.display = 'none';
        }

        function setupFileComparisonTable(payload) {
            const fileALabel = payload.file_a_label || 'الملف الأول';
            const fileBLabel = payload.file_b_label || 'الملف الثاني';

            comparisonTableHead.innerHTML = `
                <tr class="bg-slate-950/50 text-slate-400 border-b border-white/5">
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider">الصنف</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="priceAHeader">سعر ${fileALabel}</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="priceBHeader">سعر ${fileBLabel}</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="discountAHeader">خصم ${fileALabel}</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="discountBHeader">خصم ${fileBLabel}</th>
                    <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">التشابه</th>
                </tr>
            `;
        }

        function winnerKeyForDiscount(pair) {
            const discountA = Number(pair.file_a.discount || 0);
            const discountB = Number(pair.file_b.discount || 0);
            if (discountA === discountB) return 'equal';
            return discountA > discountB ? 'A' : 'B';
        }

        function winnerKeyForSupplier(pair) {
            const priceA = pair.file_a.price;
            const priceB = pair.file_b.price;
            const discountA = Number(pair.file_a.discount || 0);
            const discountB = Number(pair.file_b.discount || 0);

            if (priceA === priceB && discountA === discountB) return 'equal';
            if (priceA < priceB || (priceA === priceB && discountA > discountB)) return 'A';
            return 'B';
        }

        function applyFiltersAndRender(page) {
            const q = (savedCompareSearchInput.value || '').trim().toLowerCase();

            if (comparisonType === 'platform_compare') {
                filteredPairs = allPairs.filter((line) => {
                    if (!q) return true;
                    const hay =
                        `${line.sheet?.name || ''} ${line.matched_product || ''} ${line.platform_best?.supplier || ''}`
                        .toLowerCase();
                    return hay.includes(q);
                });
            } else {
                // File comparison filters
                const pFilter = savedPriceWinnerFilter.value;
                const dFilter = savedDiscountWinnerFilter.value;
                const sFilter = savedSupplierWinnerFilter.value;

                filteredPairs = (allPairs || []).filter(pair => {
                    if (q) {
                        const hay = `${pair.file_a.raw_name || ''} ${pair.file_b.raw_name || ''}`.toLowerCase();
                        if (!hay.includes(q)) return false;
                    }

                    if (pFilter !== 'all' && winnerKeyForPrice(pair) !== pFilter) return false;
                    if (dFilter !== 'all' && winnerKeyForDiscount(pair) !== dFilter) return false;
                    if (sFilter !== 'all' && winnerKeyForSupplier(pair) !== sFilter) return false;

                    return true;
                });
            }

            if (!filteredPairs.length) {
                const colspan = comparisonType === 'platform_compare' ? '9' : '6';
                savedComparisonTable.innerHTML =
                    `<tr><td colspan="${colspan}" class="p-6 text-center text-slate-500">لا توجد نتائج بعد تطبيق الفلاتر.</td></tr>`;
                savedComparisonPagination.innerHTML = '';
                return;
            }

            renderSavedComparisonPage(page);
        }

        function renderSavedComparisonPage(page) {
            const total = filteredPairs.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage - 1) * pageSize;
            const rows = filteredPairs.slice(start, start + pageSize);

            if (comparisonType === 'platform_compare') {
                savedComparisonTable.innerHTML = rows.map((line) => {
                    const sheet = line.sheet || {};
                    const best = line.platform_best || {};
                    const cmp = line.comparison || {};
                    const priceDiff = cmp.price_diff;
                    const discountDiff = cmp.discount_diff;
                    const priceCls = priceDiff === null ? 'text-slate-400' : (priceDiff > 0 ? 'text-rose-400' : (
                        priceDiff < 0 ? 'text-emerald-400' : 'text-slate-200'));
                    const discountCls = discountDiff === null ? 'text-slate-400' : (discountDiff > 0 ?
                        'text-emerald-400' : (discountDiff < 0 ? 'text-rose-400' : 'text-slate-200'));

                    return `
                        <tr class="border-t border-white/5 text-slate-300">
                            <td class="p-4 font-semibold text-white">${escapeHtml(sheet.name ?? '-')}</td>
                            <td class="p-4 text-center">${formatNum(sheet.price)}</td>
                            <td class="p-4 text-center">${formatNum(sheet.discount)}</td>
                            <td class="p-4">${escapeHtml(line.matched_product ?? '-')}</td>
                            <td class="p-4">${escapeHtml(best.supplier ?? '-')}</td>
                            <td class="p-4 text-center">${formatNum(best.price)}</td>
                            <td class="p-4 text-center">${formatNum(best.discount)}</td>
                            <td class="p-4 text-center ${priceCls}">${formatNum(priceDiff)}</td>
                            <td class="p-4 text-center ${discountCls}">${formatNum(discountDiff)}</td>
                        </tr>
                    `;
                }).join('');
            } else {
                savedComparisonTable.innerHTML = rows.map((pair) => `
                    <tr class="border-t border-white/5 text-slate-300">
                        <td class="p-4 font-semibold text-white">${pair.file_a.raw_name}</td>
                        <td class="p-4 text-center">${Number(pair.file_a.price).toFixed(2)}</td>
                        <td class="p-4 text-center">${Number(pair.file_b.price).toFixed(2)}</td>
                        <td class="p-4 text-center">${Number(pair.file_a.discount || 0).toFixed(1)}%</td>
                        <td class="p-4 text-center">${Number(pair.file_b.discount || 0).toFixed(1)}%</td>
                        <td class="p-4 text-center">${Number(pair.similarity_percent || 0).toFixed(1)}%</td>
                    </tr>
                `).join('');
            }

            savedComparisonPagination.innerHTML = `
                <div class="flex items-center justify-between gap-3 text-xs text-slate-400">
                    <span>عرض ${start + 1} - ${Math.min(start + pageSize, total)} من ${total} منتج</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-slate-800 ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === 1 ? 'disabled' : ''} onclick="renderSavedComparisonPage(${currentPage - 1})">السابق</button>
                        <span>${currentPage} / ${totalPages}</span>
                        <button class="px-3 py-1 rounded bg-slate-800 ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === totalPages ? 'disabled' : ''} onclick="renderSavedComparisonPage(${currentPage + 1})">التالي</button>
                    </div>
                </div>
            `;
        }

        async function loadSavedComparison() {
            try {
                const res = await axios.get(`/saved-comparisons/${savedComparisonId}`);
                const item = res.data;
                const payload = item.payload || {};

                // Detect comparison type
                comparisonType = payload.type || 'file_compare';

                if (comparisonType === 'platform_compare') {
                    // Platform comparison: lines with sheet and platform_best
                    allPairs = payload.lines || [];
                    setupPlatformComparisonTable();
                } else {
                    // File comparison: pairs with file_a and file_b
                    allPairs = payload.pairs || [];
                    setupFileComparisonTable(payload);
                }

                document.getElementById('comparisonTitle').textContent = item.title || 'بدون عنوان';
                document.getElementById('comparisonMeta').textContent =
                    `تاريخ الحفظ: ${new Date(item.created_at).toLocaleString('ar-EG')} - عدد المنتجات: ${allPairs.length}`;

                if (!allPairs.length) {
                    const colspan = comparisonType === 'platform_compare' ? '9' : '6';
                    savedComparisonTable.innerHTML =
                        `<tr><td colspan="${colspan}" class="p-6 text-center text-slate-500">لا توجد منتجات محفوظة داخل هذه المقارنة.</td></tr>`;
                    savedComparisonPagination.innerHTML = '';
                    return;
                }

                currentPage = 1;
                applyFiltersAndRender(1);

                savedCompareSearchInput.addEventListener('input', () => applyFiltersAndRender(1));
                if (comparisonType === 'file_compare') {
                    savedPriceWinnerFilter.addEventListener('change', () => applyFiltersAndRender(1));
                    savedDiscountWinnerFilter.addEventListener('change', () => applyFiltersAndRender(1));
                    savedSupplierWinnerFilter.addEventListener('change', () => applyFiltersAndRender(1));
                    clearSavedCompareFiltersBtn.addEventListener('click', () => {
                        savedCompareSearchInput.value = '';
                        savedPriceWinnerFilter.value = 'all';
                        savedDiscountWinnerFilter.value = 'all';
                        savedSupplierWinnerFilter.value = 'all';
                        applyFiltersAndRender(1);
                    });
                }
            } catch (error) {
                savedComparisonTable.innerHTML =
                    '<tr><td colspan="9" class="p-6 text-center text-rose-400">تعذر تحميل المقارنة المحفوظة.</td></tr>';
            }
        }

        function formatNum(v) {
            if (v === null || v === undefined || v === '') return '-';
            const n = parseFloat(v);
            return isNaN(n) ? String(v) : n.toFixed(2);
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        document.addEventListener('DOMContentLoaded', loadSavedComparison);
    </script>
@endpush
