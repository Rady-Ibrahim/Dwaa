@extends('layouts.client')

@section('title', 'المقارنة الذكية')

@section('content')
    <style>
        /* تحسينات الجدول لتناسب الثيم الداكن */
        .compare-table thead th {
            background: rgba(255, 255, 255, 0.02);
            color: #94a3b8;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .compare-table tbody tr {
            border-bottom: 1px solid rgba(255, 255, 255, 0.02);
            transition: all 0.2s;
        }

        .compare-table tbody tr:hover {
            background: rgba(56, 189, 248, 0.03);
        }

        /* الكبسولات السعرية */
        .pill-price {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 8px;
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }

        .pill-discount {
            display: inline-flex;
            padding: 2px 8px;
            border-radius: 6px;
            background: rgba(34, 197, 94, 0.1);
            color: #4ade80;
            font-size: 0.8rem;
        }

        .pill-good {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .pill-bad {
            background: rgba(244, 63, 94, 0.15);
            color: #fb7185;
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .pill-neutral {
            background: rgba(148, 163, 184, 0.15);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.2);
        }

        .pill-best-a {
            background: rgba(34, 197, 94, 0.15);
            color: #4ade80;
            border: 1px solid rgba(34, 197, 94, 0.2);
        }

        .pill-best-b {
            background: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        /* منطقة رفع الملفات */
        .file-drop-zone {
            border: 2px dashed rgba(255, 255, 255, 0.1);
            background: rgba(15, 23, 42, 0.3);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .file-drop-zone:hover {
            border-color: #38bdf8;
            background: rgba(56, 189, 248, 0.02);
        }

        .file-drop-zone.active {
            border-color: #38bdf8;
            box-shadow: 0 0 20px rgba(56, 189, 248, 0.1);
        }
    </style>

    <div class="space-y-6">
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center">⚖️</div>
                <h4 class="text-xl font-bold text-white">مقارنة ملفات التوريد</h4>
            </div>

            <form onsubmit="compareFiles(event)" class="space-y-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <div class="space-y-3">
                        <input type="file" id="fileA" accept=".xlsx,.xls,.csv" required class="hidden" />
                        <div onclick="document.getElementById('fileA').click()"
                            class="file-drop-zone rounded-2xl p-6 cursor-pointer text-center group" id="dropZoneA">
                            <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">📄</div>
                            <p id="fileAName" class="text-sm font-semibold text-slate-300">اسحب أو اختر الملف الأول</p>
                            <p class="text-[10px] text-slate-500 mt-1 uppercase">XLSX, XLS, CSV ONLY</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <input type="file" id="fileB" accept=".xlsx,.xls,.csv" required class="hidden" />
                        <div onclick="document.getElementById('fileB').click()"
                            class="file-drop-zone rounded-2xl p-6 cursor-pointer text-center group" id="dropZoneB">
                            <div class="text-3xl mb-2 group-hover:scale-110 transition-transform">📄</div>
                            <p id="fileBName" class="text-sm font-semibold text-slate-300">اسحب أو اختر الملف الثاني</p>
                            <p class="text-[10px] text-slate-500 mt-1 uppercase">XLSX, XLS, CSV ONLY</p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pt-4">
                    <button type="submit" id="compareBtn"
                        class="px-10 py-4 bg-sky-600 hover:bg-sky-500 text-white rounded-2xl font-bold shadow-lg shadow-sky-900/20 transition-all flex items-center gap-3 active:scale-[0.97]">
                        <span>بدء المقارنة الذكية</span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-8.707l-3-3a1 1 0 00-1.414 1.414L10.586 9H7a1 1 0 100 2h3.586l-1.293 1.293a1 1 0 101.414 1.414l3-3a1 1 0 000-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </button>
                </div>
            </form>
        </div>

        <style>
            select option {
                background-color: #0f172a !important; /* لون متناسق مع السايد بار */
                color: #ffffff !important;
            }
        </style>
        
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-[2rem] overflow-hidden shadow-2xl">
            <div class="p-6 border-b border-white/5 bg-slate-950/20">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 items-center">
                    
                    <div class="relative group">
                        <input id="compareSearchInput" type="text" placeholder="بحث باسم الصنف..."
                            class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40 transition-all" />
                    </div>
        
                    <div>
                        <select id="priceWinnerFilter"
                            class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                            <option value="all" selected>فلتر السعر: الكل</option>
                            <option value="A">سعر A أقل</option>
                            <option value="B">سعر B أقل</option>
                            <option value="equal">متساوي</option>
                        </select>
                    </div>
        
                    <div>
                        <select id="discountWinnerFilter"
                            class="w-full rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                            <option value="all" selected>فلتر الخصم: الكل</option>
                            <option value="A">خصم A أعلى</option>
                            <option value="B">خصم B أعلى</option>
                            <option value="equal">متساوي</option>
                        </select>
                    </div>
        
                    <div class="flex gap-2">
                        <select id="supplierWinnerFilter"
                            class="flex-1 rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white focus:outline-none focus:ring-2 focus:ring-sky-500/40 cursor-pointer appearance-none">
                            <option value="all" selected>فلتر المورد: الكل</option>
                            <option value="A">المورد A الأفضل</option>
                            <option value="B">المورد B الأفضل</option>
                            <option value="equal">متساوي</option>
                        </select>
        
                        <button id="clearCompareFiltersBtn" 
                            title="مسح كافة الفلاتر"
                            class="px-4 py-3 rounded-xl bg-slate-800 hover:bg-rose-500/20 text-rose-500 border border-white/10 transition-all flex items-center justify-center group">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 transition-transform group-hover:rotate-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-4v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        
            <div class="overflow-x-auto">
                <table class="compare-table w-full text-right border-collapse">
                    <thead>
                        <tr class="bg-slate-950/50 text-slate-400 border-b border-white/5 text-xs uppercase tracking-wider">
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
                                    <span class="text-xs text-slate-500">ارفع ملفات Excel للمقارنة بين الأسعار والخصومات</span>
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

        // تحديث المسميات عند الاختيار
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
                compareBtn.innerHTML = '<span>بدء المقارنة الذكية</span>';
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
                    className: 'pill-neutral',
                };
            }

            if (priceA < priceB || (priceA === priceB && discountA > discountB)) {
                return {
                    label: `${fileALabel} الأفضل`,
                    className: 'pill-best-a',
                };
            }

            return {
                label: `${fileBLabel} الأفضل`,
                className: 'pill-best-b',
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
            if (!latestCompareData) {
                return;
            }

            if (latestSavedComparisonId) {
                window.clientNotify('المقارنة محفوظة بالفعل', 'success');
                return;
            }

            await persistComparison(true);
        }

        async function persistComparison(showToast = true) {
            if (!latestCompareData) {
                return;
            }

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
