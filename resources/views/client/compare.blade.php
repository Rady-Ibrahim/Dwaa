@extends('layouts.client')

@section('title', 'المقارنة الذكية')

@section('content')
    <style>
        .compare-shell {
            display: flex;
            flex-direction: column;
            gap: 1.4rem;
        }

        .compare-hero {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 1rem 1.2rem;
            border-radius: 22px;
            background: rgba(18, 18, 18, 0.75);
            border: 1px solid rgba(59, 130, 246, 0.45);
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.2);
        }

        .compare-hero-title {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            font-weight: 800;
            color: var(--text-main);
            font-size: 1.4rem;
        }

        .compare-hero-badge {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: grid;
            place-items: center;
            background: rgba(37, 99, 235, 0.14);
            border: 1px solid rgba(59, 130, 246, 0.38);
            color: var(--accent-soft);
        }

        .compare-hero-meta {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            flex-wrap: wrap;
        }

        .compare-pill {
            padding: 0.5rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(59, 130, 246, 0.28);
            color: var(--text-soft);
            font-size: 0.8rem;
        }

        .compare-upload-panel {
            padding: 1.2rem;
            border-radius: 22px;
            background: rgba(18, 18, 18, 0.75);
            border: 1px solid rgba(59, 130, 246, 0.45);
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.2);
        }

        .file-drop-zone {
            border: 2px dashed rgba(59, 130, 246, 0.45);
            background: rgba(255, 255, 255, 0.01);
            transition: all 0.25s ease;
            min-height: 170px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            cursor: pointer;
            border-radius: 18px;
        }

        .file-drop-zone:hover {
            border-color: rgba(96, 165, 250, 0.8);
            background: rgba(37, 99, 235, 0.04);
        }

        .file-drop-zone.active {
            border-color: rgba(96, 165, 250, 0.9);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
        }

        .file-drop-zone .inner {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.7rem;
            color: var(--text-soft);
        }

        .file-drop-zone .icon {
            width: 62px;
            height: 62px;
            border-radius: 18px;
            display: grid;
            place-items: center;
            background: rgba(37, 99, 235, 0.10);
            border: 1px solid rgba(59, 130, 246, 0.24);
            color: var(--accent-soft);
            font-size: 1.7rem;
        }

        .compare-primary-btn {
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1rem 1.3rem;
            font-weight: 800;
            background: linear-gradient(90deg, #1b070d, #4f0a19, #1b070d);
            color: #fff;
            box-shadow: 0 16px 24px rgba(33, 4, 10, 0.45);
            transition: transform 0.15s ease, filter 0.15s ease;
        }

        .compare-primary-btn:hover {
            filter: brightness(1.08);
            transform: translateY(-1px);
        }

        .compare-primary-btn:disabled {
            opacity: 0.75;
            cursor: not-allowed;
        }

        .compare-table-wrap {
            overflow: hidden;
            border-radius: 22px;
            background: rgba(18, 18, 18, 0.75);
            border: 1px solid rgba(59, 130, 246, 0.45);
            box-shadow: 0 16px 28px rgba(0, 0, 0, 0.2);
        }

        .compare-filters {
            padding: 1rem 1rem 0.8rem;
            border-bottom: 1px solid rgba(59, 130, 246, 0.18);
            background: rgba(255, 255, 255, 0.02);
        }

        .compare-filter-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .compare-filter-box {
            width: 100%;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            background: rgba(20, 20, 20, 0.8);
            color: var(--text-soft2);
            padding: 0.8rem 0.9rem;
            outline: none;
        }

        .compare-filter-box:focus {
            border-color: rgba(59, 130, 246, 0.45);
            box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.08);
        }

        .compare-table thead th {
            background: rgba(22, 22, 22, 0.9);
            color: var(--text-soft);
            text-transform: uppercase;
            font-size: 0.74rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(59, 130, 246, 0.18);
        }

        .compare-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
            transition: background 0.2s ease;
        }

        .compare-table tbody tr:hover {
            background: rgba(239, 35, 60, 0.03);
        }

        .pill-price,
        .pill-discount,
        .pill-good,
        .pill-bad,
        .pill-neutral,
        .pill-best-a,
        .pill-best-b {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 90px;
            padding: 0.42rem 0.7rem;
            border-radius: 10px;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .pill-price {
            background: rgba(239, 35, 60, 0.12);
            color: var(--danger-soft);
        }

        .pill-discount {
            background: rgba(34, 197, 94, 0.12);
            color: var(--success-soft);
        }

        .pill-good {
            background: rgba(34, 197, 94, 0.16);
            color: var(--success-soft);
            border: 1px solid rgba(34, 197, 94, 0.22);
        }

        .pill-bad {
            background: rgba(239, 35, 60, 0.14);
            color: var(--danger-soft);
            border: 1px solid rgba(239, 35, 60, 0.22);
        }

        .pill-neutral {
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-soft);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .pill-best-a {
            background: rgba(34, 197, 94, 0.16);
            color: var(--success-soft);
            border: 1px solid rgba(34, 197, 94, 0.22);
        }

        .pill-best-b {
            background: rgba(239, 35, 60, 0.10);
            color: var(--accent-soft);
            border: 1px solid rgba(239, 35, 60, 0.20);
        }

        select option {
            background-color: var(--option-bg) !important;
            color: var(--option-color) !important;
        }

        @media (max-width: 960px) {
            .compare-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {
            .compare-hero {
                flex-direction: column;
                align-items: flex-start;
            }

            .compare-filter-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="compare-shell">
        <div class="compare-hero">
            <div class="compare-hero-title">
                <div class="compare-hero-badge">⚖️</div>
                <span>مقارنة ملفات التوريد</span>
            </div>
            <div class="compare-hero-meta">
                <span class="compare-pill">مقارنة ذكية</span>
                <span class="compare-pill">MedRANKO</span>
            </div>
        </div>

        <div class="compare-upload-panel">
            <form onsubmit="compareFiles(event)" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <input type="file" id="fileA" accept=".xlsx,.xls,.csv" required class="hidden" />
                        <div onclick="document.getElementById('fileA').click()" id="dropZoneA" class="file-drop-zone">
                            <div class="inner">
                                <div class="icon">📄</div>
                                <div>
                                    <p id="fileAName" class="font-semibold text-slate-200">اختر الملف الأول</p>
                                    <div class="text-[10px] text-slate-500 uppercase mt-1">XLSX, XLS, CSV</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <input type="file" id="fileB" accept=".xlsx,.xls,.csv" required class="hidden" />
                        <div onclick="document.getElementById('fileB').click()" id="dropZoneB" class="file-drop-zone">
                            <div class="inner">
                                <div class="icon"
                                    style="background: rgba(168,85,247,0.12); border-color: rgba(168,85,247,0.22); color: #d8b4fe;">
                                    📄</div>
                                <div>
                                    <p id="fileBName" class="font-semibold text-slate-200">اختر الملف الثاني</p>
                                    <div class="text-[10px] text-slate-500 uppercase mt-1">XLSX, XLS, CSV</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pt-2">
                    <button type="submit" id="compareBtn" class="compare-primary-btn">
                        بدء المقارنة الذكية
                    </button>
                </div>
            </form>
        </div>

        <div class="compare-table-wrap">
            <div class="compare-filters">
                <div class="compare-filter-grid">
                    <input id="compareSearchInput" type="text" placeholder="بحث باسم الصنف..."
                        class="compare-filter-box" />

                    <select id="priceWinnerFilter" class="compare-filter-box">
                        <option value="all" selected>فلتر السعر: الكل</option>
                        <option value="A">سعر A أقل</option>
                        <option value="B">سعر B أقل</option>
                        <option value="equal">متساوي</option>
                    </select>

                    <select id="discountWinnerFilter" class="compare-filter-box">
                        <option value="all" selected>فلتر الخصم: الكل</option>
                        <option value="A">خصم A أعلى</option>
                        <option value="B">خصم B أعلى</option>
                        <option value="equal">متساوي</option>
                    </select>

                    <div class="flex gap-2">
                        <select id="supplierWinnerFilter" class="compare-filter-box flex-1">
                            <option value="all" selected>فلتر المورد: الكل</option>
                            <option value="A">المورد A الأفضل</option>
                            <option value="B">المورد B الأفضل</option>
                            <option value="equal">متساوي</option>
                        </select>

                        <button id="clearCompareFiltersBtn" title="مسح كافة الفلاتر"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-500 border border-white/10 transition-all">
                            🗑️
                        </button>
                    </div>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="compare-table w-full text-right border-collapse">
                    <thead>
                        <tr>
                            <th class="p-5 font-semibold">الصنف</th>
                            <th class="p-5 font-semibold text-center" id="priceAHeader">سعر الملف 1</th>
                            <th class="p-5 font-semibold text-center" id="priceBHeader">سعر الملف 2</th>
                            <th class="p-5 font-semibold text-center" id="discountAHeader">خصم الملف 1</th>
                            <th class="p-5 font-semibold text-center" id="discountBHeader">خصم الملف 2</th>
                            <th class="p-5 font-semibold text-center">الفارق</th>
                            <th class="p-5 font-semibold text-left">الخيار الأفضل</th>
                        </tr>
                    </thead>
                    <tbody id="compareTable" class="text-slate-300 divide-y divide-white/[0.03]">
                        <tr>
                            <td colspan="7" class="p-20 text-center">
                                <div class="flex flex-col items-center gap-4">
                                    <div class="w-16 h-16 bg-white/5 rounded-full flex items-center justify-center mb-2">
                                        <span class="text-3xl opacity-40">📊</span>
                                    </div>
                                    <p class="text-slate-400 text-base">بانتظار رفع الملفات لبدء التحليل الفوري...</p>
                                    <span class="text-xs text-slate-500">ارفع ملفات Excel للمقارنة بين الأسعار
                                        والخصومات</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div id="compareActions" class="p-6 bg-slate-950/30 border-t border-white/5 flex justify-end"></div>
            <div id="comparePagination" class="p-4 border-t border-white/5 bg-slate-950/10"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let latestCompareData = null;
        let latestSavedComparisonId = null;
        let currentPage = 1;
        const pageSize = 15;
        let filteredPairs = [];
        let filterDebounceTimer = null;
        const fileAInput = document.getElementById('fileA');
        const fileBInput = document.getElementById('fileB');
        const fileAName = document.getElementById('fileAName');
        const fileBName = document.getElementById('fileBName');
        const compareBtn = document.getElementById('compareBtn');
        const compareTable = document.getElementById('compareTable');
        const compareActions = document.getElementById('compareActions');
        const comparePagination = document.getElementById('comparePagination');
        const priceAHeader = document.getElementById('priceAHeader');
        const priceBHeader = document.getElementById('priceBHeader');
        const discountAHeader = document.getElementById('discountAHeader');
        const discountBHeader = document.getElementById('discountBHeader');
        const compareSearchInput = document.getElementById('compareSearchInput');
        const priceWinnerFilter = document.getElementById('priceWinnerFilter');
        const discountWinnerFilter = document.getElementById('discountWinnerFilter');
        const supplierWinnerFilter = document.getElementById('supplierWinnerFilter');
        const clearCompareFiltersBtn = document.getElementById('clearCompareFiltersBtn');

        fileAInput.addEventListener('change', () => {
            if (fileAInput.files?.[0]) {
                fileAName.textContent = fileAInput.files[0].name;
                document.getElementById('dropZoneA').classList.add('active');
            }
        });

        fileBInput.addEventListener('change', () => {
            if (fileBInput.files?.[0]) {
                fileBName.textContent = fileBInput.files[0].name;
                document.getElementById('dropZoneB').classList.add('active');
            }
        });

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

        function scheduleFilterApply() {
            clearTimeout(filterDebounceTimer);
            filterDebounceTimer = setTimeout(() => {
                if (!latestCompareData) return;
                applyFiltersAndRender(1);
            }, 250);
        }

        compareSearchInput.addEventListener('input', scheduleFilterApply);
        priceWinnerFilter.addEventListener('change', () => latestCompareData ? applyFiltersAndRender(1) : null);
        discountWinnerFilter.addEventListener('change', () => latestCompareData ? applyFiltersAndRender(1) : null);
        supplierWinnerFilter.addEventListener('change', () => latestCompareData ? applyFiltersAndRender(1) : null);
        clearCompareFiltersBtn.addEventListener('click', () => {
            compareSearchInput.value = '';
            priceWinnerFilter.value = 'all';
            discountWinnerFilter.value = 'all';
            supplierWinnerFilter.value = 'all';
            if (!latestCompareData) return;
            applyFiltersAndRender(1);
        });

        async function compareFiles(event) {
            event.preventDefault();
            if (!fileAInput.files?.[0] || !fileBInput.files?.[0]) {
                window.clientNotify('يرجى اختيار ملفين للمقارنة', 'error');
                return;
            }

            compareBtn.disabled = true;
            compareBtn.innerHTML = '<span class="animate-pulse">جاري التحليل...</span>';

            compareTable.innerHTML =
                '<tr><td colspan="7" class="p-12 text-center"><div class="flex justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-sky-500"></div></div><p class="mt-4 text-sky-400">نقوم الآن بمطابقة الأسماء والأسعار باستخدام الخوارزميات الذكية...</p></td></tr>';

            const formData = new FormData();
            formData.append('file_a', fileAInput.files[0]);
            formData.append('file_b', fileBInput.files[0]);
            formData.append('min_similarity', 80);

            try {
                const res = await axios.post('/compare-files', formData);
                latestCompareData = {
                    ...res.data,
                    file_a_label: getBaseFileName(fileAInput.files[0].name),
                    file_b_label: getBaseFileName(fileBInput.files[0].name),
                };
                latestSavedComparisonId = null;
                currentPage = 1;
                renderCompare(latestCompareData);
                window.clientNotify('اكتملت المقارنة بنجاح', 'success');
            } catch (err) {
                window.clientNotify('خطأ في معالجة الملفات. تأكد من وجود هيدر واضح للاسم والسعر.', 'error');
                compareTable.innerHTML =
                    '<tr><td colspan="7" class="p-12 text-center text-rose-400">فشلت المقارنة. تأكد أن الملف يحتوي على هيدر واضح لاسم الصنف والسعر (والخصم اختياري).</td></tr>';
            } finally {
                compareBtn.disabled = false;
                compareBtn.innerHTML = 'بدء المقارنة الذكية';
            }
        }

        function applyFiltersAndRender(page) {
            const q = (compareSearchInput.value || '').trim().toLowerCase();
            const pFilter = priceWinnerFilter.value;
            const dFilter = discountWinnerFilter.value;
            const sFilter = supplierWinnerFilter.value;

            filteredPairs = (latestCompareData?.pairs || []).filter(pair => {
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
                compareTable.innerHTML =
                    '<tr><td colspan="7" class="p-12 text-center text-slate-500">لا توجد نتائج بعد تطبيق الفلاتر.</td></tr>';
                comparePagination.innerHTML = '';
                return;
            }

            renderComparePage(page);
        }

        function renderCompare(data) {
            compareActions.innerHTML = '';
            updateHeaders(data);

            if (!data.pairs || data.pairs.length === 0) {
                compareTable.innerHTML =
                    '<tr><td colspan="7" class="p-12 text-center text-slate-500">لم نجد منتجات متطابقة بين الملفين.</td></tr>';
                comparePagination.innerHTML = '';
                return;
            }

            applyFiltersAndRender(1);

            compareActions.innerHTML = `
                <button id="saveComparisonBtn" onclick="saveComparisonManually()" class="px-4 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold">
                    ${latestSavedComparisonId ? 'تم حفظ المقارنة' : 'حفظ المقارنة'}
                </button>
            `;
        }

        function renderComparePage(page) {
            if (!filteredPairs?.length) {
                return;
            }

            const total = filteredPairs.length;
            const totalPages = Math.max(1, Math.ceil(total / pageSize));
            currentPage = Math.min(Math.max(1, page), totalPages);
            const start = (currentPage - 1) * pageSize;
            const rows = filteredPairs.slice(start, start + pageSize);

            compareTable.innerHTML = rows.map(pair => {
                const priceA = pair.file_a.price.toFixed(2);
                const priceB = pair.file_b.price.toFixed(2);
                const discA = Number(pair.file_a.discount || 0).toFixed(1);
                const discB = Number(pair.file_b.discount || 0).toFixed(1);
                const diff = Math.abs(pair.file_a.price - pair.file_b.price).toFixed(2);
                const decision = getBestDecision(pair);

                return `
                    <tr>
                        <td class="p-4 font-semibold text-white">${pair.file_a.raw_name}</td>
                        <td class="p-4"><span class="pill-price ${getMetricClass(pair.file_a.price, pair.file_b.price, 'price')}">${priceA}</span></td>
                        <td class="p-4"><span class="pill-price ${getMetricClass(pair.file_b.price, pair.file_a.price, 'price')}">${priceB}</span></td>
                        <td class="p-4"><span class="pill-discount ${getMetricClass(Number(pair.file_a.discount || 0), Number(pair.file_b.discount || 0), 'discount')}">${discA}%</span></td>
                        <td class="p-4"><span class="pill-discount ${getMetricClass(Number(pair.file_b.discount || 0), Number(pair.file_a.discount || 0), 'discount')}">${discB}%</span></td>
                        <td class="p-4 text-amber-500 font-mono font-bold">${diff}</td>
                        <td class="p-4 text-left">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase ${decision.className}">
                                ${decision.label}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

            comparePagination.innerHTML = `
                <div class="flex items-center justify-between gap-3 text-xs text-slate-400">
                    <span>عرض ${start + 1} - ${Math.min(start + pageSize, total)} من ${total} منتج</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-slate-800 ${currentPage === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === 1 ? 'disabled' : ''} onclick="renderComparePage(${currentPage - 1})">السابق</button>
                        <span>${currentPage} / ${totalPages}</span>
                        <button class="px-3 py-1 rounded bg-slate-800 ${currentPage === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${currentPage === totalPages ? 'disabled' : ''} onclick="renderComparePage(${currentPage + 1})">التالي</button>
                    </div>
                </div>
            `;
        }

        function getMetricClass(current, other, type) {
            if (current === other) {
                return 'pill-neutral';
            }

            if (type === 'discount') {
                return current > other ? 'pill-good' : 'pill-bad';
            }

            return current < other ? 'pill-good' : 'pill-bad';
        }

        function getBestDecision(pair) {
            const fileALabel = latestCompareData?.file_a_label || 'الملف الأول';
            const fileBLabel = latestCompareData?.file_b_label || 'الملف الثاني';
            const priceA = pair.file_a.price;
            const priceB = pair.file_b.price;
            const discountA = Number(pair.file_a.discount || 0);
            const discountB = Number(pair.file_b.discount || 0);

            if (priceA === priceB && discountA === discountB) {
                return {
                    label: 'متساوي',
                    className: 'pill-neutral'
                };
            }

            if (priceA < priceB || (priceA === priceB && discountA > discountB)) {
                return {
                    label: `${fileALabel} الأفضل`,
                    className: 'pill-best-a'
                };
            }

            return {
                label: `${fileBLabel} الأفضل`,
                className: 'pill-best-b'
            };
        }

        function updateHeaders(data) {
            const fileALabel = data.file_a_label || 'الملف الأول';
            const fileBLabel = data.file_b_label || 'الملف الثاني';
            priceAHeader.textContent = `سعر ${fileALabel}`;
            priceBHeader.textContent = `سعر ${fileBLabel}`;
            discountAHeader.textContent = `خصم ${fileALabel}`;
            discountBHeader.textContent = `خصم ${fileBLabel}`;
        }

        function getBaseFileName(fileName) {
            return (fileName || '').replace(/\.[^/.]+$/, '').trim();
        }

        function getComparisonTitle() {
            const fileALabel = latestCompareData?.file_a_label || 'الملف الأول';
            const fileBLabel = latestCompareData?.file_b_label || 'الملف الثاني';
            return `مقارنه بين ${fileALabel} و ${fileBLabel}`;
        }

        async function saveComparisonManually() {
            if (!latestCompareData) return;
            if (latestSavedComparisonId) {
                window.clientNotify('المقارنة محفوظة بالفعل', 'success');
                return;
            }
            await persistComparison(true);
        }

        async function persistComparison(showToast = true) {
            if (!latestCompareData) return;

            const response = await axios.post('/saved-comparisons', {
                title: getComparisonTitle(),
                payload: {
                    ...latestCompareData,
                    file_a_name: fileAInput.files?.[0]?.name || null,
                    file_b_name: fileBInput.files?.[0]?.name || null,
                },
            });

            latestSavedComparisonId = response.data.id || true;
            renderCompare(latestCompareData);

            if (showToast) {
                window.clientNotify('تم حفظ المقارنة', 'success');
            }
        }
    </script>
@endpush
