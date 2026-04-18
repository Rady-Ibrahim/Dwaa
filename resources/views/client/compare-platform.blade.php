@extends('layouts.client')

@section('title', 'مقارنة ملف مع المنصة')

@section('content')
    <div class="p-6 space-y-6">
        <div class="bg-slate-900/50 border border-white/10 rounded-3xl p-6">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-xl bg-sky-500/20 flex items-center justify-center">📑</div>
                <h3 class="text-xl font-bold text-white">مقارنة ملف واحد مع بيانات المنصة</h3>
            </div>

            <div class="text-sm text-slate-400 mb-5">
                ارفع ملف المورد، وسيتم مقارنة اسم الصنف والسعر والخصم مع أفضل عرض موجود لدينا.
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <input type="file" id="platformCompareFile" class="hidden" accept=".xlsx,.xls,.csv" />
                <button type="button" id="pickPlatformCompareFile"
                    class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white">
                    اختيار ملف
                </button>
                <span id="platformCompareFileName" class="text-sm text-slate-300">لم يتم اختيار ملف</span>
                <button type="button" id="runPlatformCompareBtn"
                    class="px-5 py-2 rounded-xl bg-sky-600 hover:bg-sky-500 text-white font-bold">
                    بدء المقارنة
                </button>
            </div>
        </div>

        <div class="bg-slate-900/50 border border-white/10 rounded-3xl overflow-hidden">
            <div class="p-4 border-b border-white/10 flex items-center justify-between gap-4">
                <input id="platformCompareSearch" type="text" placeholder="بحث داخل النتائج..."
                    class="flex-1 rounded-xl bg-slate-900 border border-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-500 focus:outline-none focus:ring-2 focus:ring-sky-500/40" />
                <button type="button" id="savePlatformComparisonBtn"
                    class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 text-white font-bold hidden">
                    💾 حفظ المقارنة
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right">
                    <thead class="bg-slate-950/40 text-slate-300">
                        <tr>
                            <th class="p-3">الصنف من الملف</th>
                            <th class="p-3">سعر الملف</th>
                            <th class="p-3">خصم الملف</th>
                            <th class="p-3">الصنف المطابق</th>
                            <th class="p-3">أفضل مورد</th>
                            <th class="p-3">سعر المنصة</th>
                            <th class="p-3">خصم المنصة</th>
                            <th class="p-3">فرق السعر</th>
                            <th class="p-3">فرق الخصم</th>
                        </tr>
                    </thead>
                    <tbody id="platformCompareTable">
                        <tr>
                            <td colspan="9" class="p-8 text-center text-slate-500">بانتظار رفع الملف.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="platformComparePager" class="p-4 border-t border-white/10 text-xs text-slate-400"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const fileInput = document.getElementById('platformCompareFile');
        const fileName = document.getElementById('platformCompareFileName');
        const pickBtn = document.getElementById('pickPlatformCompareFile');
        const runBtn = document.getElementById('runPlatformCompareBtn');
        const table = document.getElementById('platformCompareTable');
        const pager = document.getElementById('platformComparePager');
        const searchInput = document.getElementById('platformCompareSearch');
        const saveBtn = document.getElementById('savePlatformComparisonBtn');
        const pageSize = 20;
        let rows = [];
        let filtered = [];
        let page = 1;
        let latestComparisonData = null;

        pickBtn.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', () => {
            fileName.textContent = fileInput.files?.[0]?.name || 'لم يتم اختيار ملف';
        });
        searchInput.addEventListener('input', () => applyFilter(1));
        saveBtn.addEventListener('click', savePlatformComparison);

        runBtn.addEventListener('click', uploadAndCompare);

        async function uploadAndCompare() {
            const file = fileInput.files?.[0];
            if (!file) {
                clientNotify('اختر ملف أولاً', 'warning');
                return;
            }

            runBtn.disabled = true;
            table.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-400">جاري المقارنة...</td></tr>';
            pager.innerHTML = '';

            const formData = new FormData();
            formData.append('file', file);
            formData.append('limit', '3');

            try {
                const res = await axios.post('/compare-platform-file', formData);
                rows = res.data?.lines || [];
                latestComparisonData = {
                    file_name: file.name,
                    lines: rows,
                    timestamp: new Date().toISOString(),
                };
                applyFilter(1);
                saveBtn.classList.remove('hidden');
                clientNotify('تمت المقارنة بنجاح', 'success');
            } catch (error) {
                const msg = error.response?.data?.message || 'فشل تنفيذ المقارنة.';
                table.innerHTML =
                    `<tr><td colspan="9" class="p-8 text-center text-rose-400">${escapeHtml(msg)}</td></tr>`;
                clientNotify(msg, 'error');
                saveBtn.classList.add('hidden');
            } finally {
                runBtn.disabled = false;
            }
        }

        async function savePlatformComparison() {
            if (!latestComparisonData || !rows.length) {
                clientNotify('لا توجد بيانات للحفظ', 'warning');
                return;
            }

            const title = latestComparisonData.file_name || `مقارنة المنصة - ${new Date().toLocaleDateString('ar-EG')}`;

            try {
                await axios.post('/saved-comparisons', {
                    title,
                    payload: {
                        type: 'platform_compare',
                        ...latestComparisonData,
                    },
                });
                clientNotify('تم حفظ المقارنة بنجاح', 'success');
                saveBtn.disabled = true;
                saveBtn.textContent = '✓ تم الحفظ';
            } catch (error) {
                const msg = error.response?.data?.message || 'فشل حفظ المقارنة';
                clientNotify(msg, 'error');
            }
        }

        function applyFilter(nextPage) {
            page = nextPage;
            const q = (searchInput.value || '').trim().toLowerCase();
            filtered = rows.filter((r) => {
                if (!q) return true;
                const hay = `${r.sheet?.name || ''} ${r.matched_product || ''} ${r.platform_best?.supplier || ''}`
                    .toLowerCase();
                return hay.includes(q);
            });
            renderPage();
        }

        function renderPage() {
            if (!filtered.length) {
                table.innerHTML = '<tr><td colspan="9" class="p-8 text-center text-slate-500">لا توجد نتائج.</td></tr>';
                pager.innerHTML = '';
                return;
            }

            const totalPages = Math.max(1, Math.ceil(filtered.length / pageSize));
            page = Math.min(Math.max(1, page), totalPages);
            const start = (page - 1) * pageSize;
            const view = filtered.slice(start, start + pageSize);

            table.innerHTML = view.map((line) => {
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
                    <tr class="border-b border-white/5">
                        <td class="p-3 font-semibold text-white">${escapeHtml(sheet.name ?? '-')}</td>
                        <td class="p-3">${formatNum(sheet.price)}</td>
                        <td class="p-3">${formatNum(sheet.discount)}</td>
                        <td class="p-3">${escapeHtml(line.matched_product ?? '-')}</td>
                        <td class="p-3">${escapeHtml(best.supplier ?? '-')}</td>
                        <td class="p-3">${formatNum(best.price)}</td>
                        <td class="p-3">${formatNum(best.discount)}</td>
                        <td class="p-3 ${priceCls}">${formatNum(priceDiff)}</td>
                        <td class="p-3 ${discountCls}">${formatNum(discountDiff)}</td>
                    </tr>
                `;
            }).join('');

            pager.innerHTML = `
                <div class="flex items-center justify-between">
                    <span>عرض ${start + 1} - ${Math.min(start + pageSize, filtered.length)} من ${filtered.length}</span>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 rounded bg-slate-800 ${page === 1 ? 'opacity-50 cursor-not-allowed' : ''}" ${page === 1 ? 'disabled' : ''} onclick="platformCompareGo(${page - 1})">السابق</button>
                        <span>${page} / ${totalPages}</span>
                        <button class="px-3 py-1 rounded bg-slate-800 ${page === totalPages ? 'opacity-50 cursor-not-allowed' : ''}" ${page === totalPages ? 'disabled' : ''} onclick="platformCompareGo(${page + 1})">التالي</button>
                    </div>
                </div>
            `;
        }

        function platformCompareGo(nextPage) {
            page = nextPage;
            renderPage();
        }
        window.platformCompareGo = platformCompareGo;

        function formatNum(v) {
            if (v === null || v === undefined || v === '') return '-';
            if (typeof v === 'number') return Number.isInteger(v) ? String(v) : v.toFixed(2);
            return escapeHtml(String(v));
        }

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }
    </script>
@endpush
