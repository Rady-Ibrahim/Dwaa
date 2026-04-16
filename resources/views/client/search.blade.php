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

        /* زر الـ + بأسلوب ChatGPT */
        .upload-plus-btn {
            height: 42px;
            width: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            color: #38bdf8;
            font-size: 1.5rem;
            transition: all 0.2s;
            border: 1px solid rgba(56, 189, 248, 0.2);
        }

        .upload-plus-btn:hover {
            background: #38bdf8;
            color: #0f172a;
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
                <button type="button" id="uploadSheetBtn" class="upload-plus-btn" title="رفع ملف Excel">+</button>
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

            <input type="file" id="excelFile" accept=".xlsx,.xls,.csv" class="hidden" />
        </div>

        <div class="results-container" id="resultsWrap">
            <div class="custom-table-card">
                <table class="result-table w-full text-sm">
                    <thead>
                        <tr>
                            <th>المورد</th>
                            <th>المنطقة</th>
                            <th>تليفون المورد</th>
                            <th>الصنف</th>
                            <th>السعر</th>
                            <th>الخصم</th>
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
        // لا توجد فلاتر في صفحة البحث (تظهر في صفحة "كل المنتجات" فقط).

        document.getElementById('uploadSheetBtn').addEventListener('click', () => document.getElementById('excelFile')
            .click());

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
            welcomeText.style.display = 'none'; // إخفاء النص الترحيبي عند ظهور النتائج
        }

        function hideResults() {
            searchShell.classList.remove('has-results');
            resultsWrap.classList.remove('show');
            welcomeText.style.display = 'block';
            document.getElementById('resultsTable').innerHTML = '';
        }

        async function search(q) {
            try {
                const res = await axios.get('/search', {
                    params: {
                        q
                    }
                });
                renderResults(res.data.results);
                showResults();
            } catch (err) {
                console.error(err);
                clientNotify('حدث خطأ أثناء البحث', 'error');
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

        function renderResults(results) {
            const table = document.getElementById('resultsTable');
            if (!results || results.length === 0) {
                table.innerHTML = '<tr><td colspan="7" class="p-8 text-center text-slate-500">لا توجد نتائج مطابقة لبحثك.</td></tr>';
                return;
            }

            table.innerHTML = results.flatMap(item => {
                const productName = item.name_ar || item.name_en || '-';
                const productCode = item.code || '';
                const offers = item.offers || [];

                if (offers.length === 0) {
                    return `<tr>
                        <td class="p-4">-</td>
                        <td class="p-4">-</td>
                        <td class="p-4">-</td>
                        <td class="p-4 font-bold">${productName}</td>
                        <td colspan="3" class="p-4 text-slate-500">لا توجد عروض حالياً</td>
                    </tr>`;
                }

                return offers.map(offer => `
                    <tr class="${offer.is_lowest_price ? 'bg-sky-500/5' : ''}">
                        <td>${offer.supplier}</td>
                        <td>${offer.area || '-'}</td>
                        <td>${offer.supplier_phone || '-'}</td>
                        <td class="font-bold">${productName}</td>
                        <td><span class="badge-price">${offer.price} ج</span></td>
                        <td><span class="${offer.is_best_discount ? 'text-green-400' : 'text-green-400'}">${offer.discount}%</span></td>
                        <td>
                            <div class="flex items-center gap-2" dir="ltr">
                                <button onclick="addFavorite(${item.id}, this)" class="text-rose-400 hover:text-rose-500">❤️</button>
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

        // ... بقية دوال الـ upload و الـ notify كما هي في الكود الأصلي ...
    </script>
@endpush
