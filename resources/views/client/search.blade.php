@extends('layouts.client')

@section('title', 'البحث')

@section('content')
    <style>
        .result-table th { font-weight: 700; }
        .result-table tbody tr { transition: background-color .15s ease; }
        .result-table tbody tr:hover { background: rgba(2, 132, 199, 0.08); }
        .badge-price {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: rgba(2, 132, 199, .14);
            color: #0369a1;
            font-weight: 700;
        }
        .badge-discount {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            font-weight: 700;
        }
        .badge-discount-high { background: rgba(22, 163, 74, .15); color: #166534; }
        .badge-discount-mid { background: rgba(245, 158, 11, .16); color: #92400e; }
        .badge-discount-low { background: rgba(71, 85, 105, .15); color: #334155; }
        .badge-flag {
            display: inline-block;
            padding: .15rem .5rem;
            border-radius: 999px;
            font-size: .75rem;
            font-weight: 700;
            margin-left: .35rem;
        }
        .flag-best-price { background: rgba(37, 99, 235, .14); color: #1d4ed8; }
        .flag-best-discount { background: rgba(22, 163, 74, .15); color: #166534; }
    </style>

    <div class="mb-6 rounded-2xl bg-white p-6 shadow">
        <label class="mb-3 block text-sm font-medium text-slate-700">ابحث عن منتج</label>
        <div class="flex items-center gap-3">
            <button type="button" id="uploadSheetBtn"
                class="h-12 w-12 shrink-0 rounded-xl border border-slate-300 text-2xl leading-none text-slate-700 hover:bg-slate-100"
                title="رفع ملف Excel">
                +
            </button>
            <input type="text" id="searchInput" placeholder="اكتب اسم الصنف للبحث..."
                class="h-12 w-full rounded-xl border border-slate-300 px-4 text-base focus:border-blue-500 focus:outline-none"
                oninput="debouncedSearch()">
        </div>
        <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden" />

    </div>

    <div class="bg-white rounded-2xl shadow overflow-hidden">
        <table class="result-table w-full text-sm text-right">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3">المورد</th>
                    <th class="p-3">الصنف</th>
                    <th class="p-3">السعر</th>
                    <th class="p-3">الخصم</th>
                    <th class="p-3">الإجراء</th>
                </tr>
            </thead>
            <tbody id="resultsTable"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        let debounceTimer;
        const DEFAULT_NAME_COLUMN = 'C';
        const DEFAULT_HEADER_ROWS = 1;

        document.getElementById('uploadSheetBtn').addEventListener('click', function () {
            document.getElementById('excelFile').click();
        });

        document.getElementById('excelFile').addEventListener('change', function () {
            if (this.files?.length) {
                uploadExcel();
            }
        });

        function debouncedSearch() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                const q = document.getElementById('searchInput').value.trim();
                if (q.length >= 3) {
                    search(q);
                } else {
                    document.getElementById('resultsTable').innerHTML = '';
                }
            }, 300);
        }

        async function search(q) {
            try {
                const res = await axios.get('/search', {
                    params: {
                        q
                    }
                });
                updateSearchStats(q);
                renderResults(res.data.results);
            } catch (err) {
                console.error(err);
                const status = err?.response?.status;
                const apiMessage = err?.response?.data?.message;
                if (status === 402) {
                    clientNotify(apiMessage || 'لا يمكن تنفيذ البحث: الاشتراك غير مفعل أو منتهي.', 'error');
                    return;
                }
                if (status === 403) {
                    clientNotify(apiMessage || 'لا يمكن تنفيذ البحث: الحساب غير مفعل.', 'error');
                    return;
                }
                if (status === 401) {
                    clientNotify('انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.', 'error');
                    return;
                }
                clientNotify(apiMessage || 'فشل البحث. حاول مرة أخرى.', 'error');
            }
        }

        async function uploadExcel() {
            const file = document.getElementById('excelFile').files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('file', file);
            formData.append('col_name', DEFAULT_NAME_COLUMN);
            formData.append('header_rows', DEFAULT_HEADER_ROWS);
            formData.append('log_mode', 'bulk');
            formData.append('limit', '20');

            try {
                const res = await axios.post('/search/from-excel', formData);
                renderExcelResults(res.data);
                localStorage.setItem('client_last_search', 'بحث من ملف Excel');
            } catch (err) {
                console.error(err);
                const status = err?.response?.status;
                const apiMessage = err?.response?.data?.message;
                if (status === 402) {
                    clientNotify(apiMessage || 'لا يمكن البحث من Excel: الاشتراك غير مفعل أو منتهي.', 'error');
                    return;
                }
                if (status === 403) {
                    clientNotify(apiMessage || 'لا يمكن البحث من Excel: الحساب غير مفعل.', 'error');
                    return;
                }
                if (status === 401) {
                    clientNotify('انتهت الجلسة. يرجى تسجيل الدخول مرة أخرى.', 'error');
                    return;
                }
                clientNotify(apiMessage || 'خطأ في رفع الملف أو البحث من Excel.', 'error');
            }
        }

        function formatMoney(value) {
            const num = Number(value);
            if (Number.isNaN(num) || num <= 0) {
                return '-';
            }
            return `${num.toFixed(2)} ج`;
        }

        function discountBadge(discountValue) {
            const value = Number(discountValue || 0);
            if (!value) {
                return '<span class="badge-discount badge-discount-low">-</span>';
            }

            const cls = value >= 25 ? 'badge-discount-high' : value >= 10 ? 'badge-discount-mid' : 'badge-discount-low';
            return `<span class="badge-discount ${cls}">${value.toFixed(2)}%</span>`;
        }

        function renderResults(results) {
            const table = document.getElementById('resultsTable');
            if (!Array.isArray(results) || results.length === 0) {
                table.innerHTML = '<tr><td colspan="5" class="p-4 text-center text-slate-500">لا توجد نتائج.</td></tr>';
                return;
            }

            table.innerHTML = results.flatMap(item => {
                const productName = item.name_ar || item.name_en || item.code || '-';
                const offers = Array.isArray(item.offers) ? item.offers : [];
                const rowClassByItem = offers.some(offer => offer.is_lowest_price) ? 'bg-blue-50' : '';

                if (!offers.length) {
                    return [`
                        <tr class="border-t ${rowClassByItem}">
                            <td class="p-3 text-slate-500">-</td>
                            <td class="p-3 font-semibold">${productName}</td>
                            <td class="p-3"><span class="badge-price">${formatMoney(item.summary?.lowest_price)}</span></td>
                            <td class="p-3">${discountBadge(item.summary?.highest_discount)}</td>
                            <td class="p-3">
                                <button onclick="addFavorite(${item.id})" class="text-red-500">❤️ إضافة للمفضلة</button>
                            </td>
                        </tr>
                    `];
                }

                return offers.map(offer => `
                    <tr class="border-t text-sm ${offer.is_lowest_price ? 'bg-blue-100' : ''} ${offer.is_best_discount ? 'border-y border-green-200' : ''}">
                        <td class="p-2 text-slate-700">${offer.supplier} (${offer.area})</td>
                        <td class="p-2 font-semibold">${productName}</td>
                        <td class="p-2"><span class="badge-price">${formatMoney(offer.price)}</span></td>
                        <td class="p-2">${discountBadge(offer.discount)}</td>
                        <td class="p-2">
                            ${offer.is_lowest_price ? '<span class="badge-flag flag-best-price">أقل سعر</span>' : ''}
                            ${offer.is_best_discount ? '<span class="badge-flag flag-best-discount">أفضل خصم</span>' : ''}
                            ${!offer.is_lowest_price && !offer.is_best_discount ? '-' : ''}
                            <button onclick="addFavorite(${item.id})" class="text-red-500 ml-2">❤️</button>
                        </td>
                    </tr>
                `);
            }).join('');
        }

        function renderExcelResults(data) {
            const table = document.getElementById('resultsTable');
            if (data.lines && data.lines.length > 0) {
                table.innerHTML = data.lines.map(line => {
                    const summary = line.skipped ? 'تخطي' : `${line.count || line.results?.length || 0} نتائج`;
                    return `
                <tr class="border-t">
                    <td class="p-3" colspan="5">
                        <div class="font-semibold">${line.query}</div>
                        <div class="text-slate-500 text-sm">${summary}</div>
                    </td>
                </tr>
            `;
                }).join('');
                return;
            }

            renderResults(data.results || []);
        }

        async function addFavorite(productId) {
            try {
                await axios.post('/favorites', {
                    product_id: productId
                });
                clientNotify('تم إضافة المنتج إلى المفضلة', 'success');
            } catch (err) {
                console.error(err);
                clientNotify('فشل الإضافة للمفضلة', 'error');
            }
        }

        function updateSearchStats(query) {
            const lastSearch = query;
            const count = Number(localStorage.getItem('client_search_count') || 0) + 1;
            localStorage.setItem('client_last_search', lastSearch);
            localStorage.setItem('client_search_count', count);
        }
    </script>
@endpush
