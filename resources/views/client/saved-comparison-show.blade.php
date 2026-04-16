@extends('layouts.client')

@section('title', 'تفاصيل المقارنة المحفوظة')

@section('content')
    <style>
        select option {
            background-color: #0f172a !important; /* نفس لون السايد بار slate-900 */
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
                            <h3 id="comparisonTitle" class="text-2xl font-bold text-white tracking-tight">جاري تحميل المقارنة...</h3>
                        </div>
                        <p id="comparisonMeta" class="text-sm text-slate-400 flex items-center gap-2">
                            </p>
                    </div>
                    
                    <a href="/client/saved-comparisons" class="flex items-center gap-2 px-6 py-3 rounded-xl bg-white/5 hover:bg-white/10 text-white border border-white/10 transition-all group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 group-hover:-translate-x-1 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
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

                <select id="savedPriceWinnerFilter" class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                    <option value="all">فلتر السعر: الكل</option>
                    <option value="A">سعر A أقل</option>
                    <option value="B">سعر B أقل</option>
                    <option value="equal">متساوي</option>
                </select>

                <select id="savedDiscountWinnerFilter" class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                    <option value="all">فلتر الخصم: الكل</option>
                    <option value="A">خصم A أعلى</option>
                    <option value="B">خصم B أعلى</option>
                    <option value="equal">متساوي</option>
                </select>

                <div class="flex gap-2">
                    <select id="savedSupplierWinnerFilter" class="flex-1 rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                        <option value="all">المورد الأفضل: الكل</option>
                        <option value="A">المورد A أفضل</option>
                        <option value="B">المورد B أفضل</option>
                        <option value="equal">متساوي</option>
                    </select>

                    <button id="clearSavedCompareFiltersBtn" 
                        title="مسح الفلاتر"
                        class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-500 border border-white/10 transition-all flex items-center justify-center group">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-slate-900/40 backdrop-blur-xl border border-white/10 rounded-[2rem] shadow-2xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-slate-950/50 text-slate-400 border-b border-white/5">
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider">الصنف</th>
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="priceAHeader">سعر الملف 1</th>
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="priceBHeader">سعر الملف 2</th>
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="discountAHeader">خصم الملف 1</th>
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center" id="discountBHeader">خصم الملف 2</th>
                            <th class="p-5 font-semibold text-xs uppercase tracking-wider text-center">التشابه</th>
                        </tr>
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

        const savedComparisonTable = document.getElementById('savedComparisonTable');
        const savedComparisonPagination = document.getElementById('savedComparisonPagination');
        const savedCompareSearchInput = document.getElementById('savedCompareSearchInput');
        const savedPriceWinnerFilter = document.getElementById('savedPriceWinnerFilter');
        const savedDiscountWinnerFilter = document.getElementById('savedDiscountWinnerFilter');
        const savedSupplierWinnerFilter = document.getElementById('savedSupplierWinnerFilter');
        const clearSavedCompareFiltersBtn = document.getElementById('clearSavedCompareFiltersBtn');

        function winnerKeyForPrice(pair) {
            const priceA = pair.file_a.price;
            const priceB = pair.file_b.price;
            if (priceA === priceB) return 'equal';
            return priceA < priceB ? 'A' : 'B';
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

            if (!filteredPairs.length) {
                savedComparisonTable.innerHTML =
                    '<tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد نتائج بعد تطبيق الفلاتر.</td></tr>';
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

            savedComparisonTable.innerHTML = rows.map((pair) => `
                <tr class="border-t border-white/5 text-slate-300">
                    <td class="p-4 font-semibold text-white">${pair.file_a.raw_name}</td>
                    <td class="p-4">${Number(pair.file_a.price).toFixed(2)}</td>
                    <td class="p-4">${Number(pair.file_b.price).toFixed(2)}</td>
                    <td class="p-4">${Number(pair.file_a.discount || 0).toFixed(1)}%</td>
                    <td class="p-4">${Number(pair.file_b.discount || 0).toFixed(1)}%</td>
                    <td class="p-4">${Number(pair.similarity_percent || 0).toFixed(1)}%</td>
                </tr>
            `).join('');

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
                allPairs = payload.pairs || [];
                const fileALabel = payload.file_a_label || 'الملف الأول';
                const fileBLabel = payload.file_b_label || 'الملف الثاني';

                document.getElementById('comparisonTitle').textContent = item.title || 'بدون عنوان';
                document.getElementById('comparisonMeta').textContent =
                    `تاريخ الحفظ: ${new Date(item.created_at).toLocaleString('ar-EG')} - عدد المنتجات المطابقة: ${allPairs.length}`;

                document.getElementById('priceAHeader').textContent = `سعر ${fileALabel}`;
                document.getElementById('priceBHeader').textContent = `سعر ${fileBLabel}`;
                document.getElementById('discountAHeader').textContent = `خصم ${fileALabel}`;
                document.getElementById('discountBHeader').textContent = `خصم ${fileBLabel}`;

                if (!allPairs.length) {
                    savedComparisonTable.innerHTML =
                        '<tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد منتجات محفوظة داخل هذه المقارنة.</td></tr>';
                    savedComparisonPagination.innerHTML = '';
                    return;
                }

                currentPage = 1;
                applyFiltersAndRender(1);

                savedCompareSearchInput.addEventListener('input', () => applyFiltersAndRender(1));
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
            } catch (error) {
                savedComparisonTable.innerHTML =
                    '<tr><td colspan="6" class="p-6 text-center text-rose-400">تعذر تحميل المقارنة المحفوظة.</td></tr>';
            }
        }

        document.addEventListener('DOMContentLoaded', loadSavedComparison);
    </script>
@endpush
