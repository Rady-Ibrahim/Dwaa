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

        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="compare-table w-full text-sm text-right">
                    <thead>
                        <tr>
                            <th class="p-4">الصنف</th>
                            <th class="p-4">سعر الملف الأول</th>
                            <th class="p-4">سعر الملف الثاني</th>
                            <th class="p-4">خصم الملف الأول</th>
                            <th class="p-4">خصم الملف الثاني</th>
                            <th class="p-4">الفارق</th>
                            <th class="p-4 text-left">الخيار الأفضل</th>
                        </tr>
                    </thead>
                    <tbody id="compareTable" class="text-slate-300">
                        <tr>
                            <td colspan="7" class="p-12 text-center text-slate-500">
                                <div class="flex flex-col items-center gap-3">
                                    <span class="text-4xl opacity-20">📊</span>
                                    <p>بانتظار رفع الملفات لبدء التحليل...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div id="compareActions" class="p-6 bg-slate-950/30 border-t border-white/5 flex justify-end"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let latestCompareData = null;
        const fileAInput = document.getElementById('fileA');
        const fileBInput = document.getElementById('fileB');
        const fileAName = document.getElementById('fileAName');
        const fileBName = document.getElementById('fileBName');
        const compareBtn = document.getElementById('compareBtn');

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

        async function compareFiles(event) {
            event.preventDefault();
            if (!fileAInput.files?.[0] || !fileBInput.files?.[0]) {
                window.clientNotify('يرجى اختيار ملفين للمقارنة', 'error');
                return;
            }

            compareBtn.disabled = true;
            compareBtn.innerHTML = '<span class="animate-pulse">جاري التحليل...</span>';

            const table = document.getElementById('compareTable');
            table.innerHTML =
                '<tr><td colspan="7" class="p-12 text-center"><div class="flex justify-center"><div class="animate-spin rounded-full h-8 w-8 border-b-2 border-sky-500"></div></div><p class="mt-4 text-sky-400">نقوم الآن بمطابقة الأسماء والأسعار باستخدام الخوارزميات الذكية...</p></td></tr>';

            const formData = new FormData();
            formData.append('file_a', fileAInput.files[0]);
            formData.append('file_b', fileBInput.files[0]);
            formData.append('min_similarity', 80);

            try {
                const res = await axios.post('/compare-files', formData);
                latestCompareData = res.data;
                renderCompare(res.data);
                window.clientNotify('اكتملت المقارنة بنجاح', 'success');
            } catch (err) {
                window.clientNotify('خطأ في معالجة الملفات. تأكد من وجود هيدر واضح للاسم والسعر.', 'error');
                table.innerHTML =
                    '<tr><td colspan="7" class="p-12 text-center text-rose-400">فشلت المقارنة. تأكد أن الملف يحتوي على هيدر واضح لاسم الصنف والسعر (والخصم اختياري).</td></tr>';
            } finally {
                compareBtn.disabled = false;
                compareBtn.innerHTML = '<span>بدء المقارنة الذكية</span>';
            }
        }

        function renderCompare(data) {
            const table = document.getElementById('compareTable');
            const actions = document.getElementById('compareActions');
            actions.innerHTML = '';

            if (!data.pairs || data.pairs.length === 0) {
                table.innerHTML =
                    '<tr><td colspan="7" class="p-12 text-center text-slate-500">لم نجد منتجات متطابقة بين الملفين.</td></tr>';
                return;
            }

            table.innerHTML = data.pairs.map(pair => {
                const priceA = pair.file_a.price.toFixed(2);
                const priceB = pair.file_b.price.toFixed(2);
                const discA = Number(pair.file_a.discount || 0).toFixed(1);
                const discB = Number(pair.file_b.discount || 0).toFixed(1);
                const diff = Math.abs(pair.file_a.price - pair.file_b.price).toFixed(2);
                const isABest = pair.file_a.price <= pair.file_b.price;

                return `
                    <tr>
                        <td class="p-4 font-semibold text-white">${pair.file_a.raw_name}</td>
                        <td class="p-4"><span class="pill-price">${priceA}</span></td>
                        <td class="p-4"><span class="pill-price">${priceB}</span></td>
                        <td class="p-4"><span class="pill-discount">${discA}%</span></td>
                        <td class="p-4"><span class="pill-discount">${discB}%</span></td>
                        <td class="p-4 text-amber-500 font-mono font-bold">${diff}</td>
                        <td class="p-4 text-left">
                            <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase ${isABest ? 'pill-best-a' : 'pill-best-b'}">
                                ${isABest ? 'المورد A هو الأفضل' : 'المورد B هو الأفضل'}
                            </span>
                        </td>
                    </tr>
                `;
            }).join('');

        }
    </script>
@endpush
