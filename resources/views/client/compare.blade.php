@extends('layouts.client')

@section('title', 'مقارنة الملفات')

@section('content')
    <style>
        .compare-table th { font-weight: 700; }
        .compare-table tbody tr { transition: background-color .15s ease; }
        .compare-table tbody tr:hover { background: rgba(2, 132, 199, 0.08); }
        .pill-price {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: rgba(2, 132, 199, .14);
            color: #0369a1;
            font-weight: 700;
        }
        .pill-discount {
            display: inline-block;
            padding: .2rem .55rem;
            border-radius: 999px;
            background: rgba(22, 163, 74, .15);
            color: #166534;
            font-weight: 700;
        }
        .pill-best-a { background: rgba(22, 163, 74, .14); color: #166534; }
        .pill-best-b { background: rgba(37, 99, 235, .14); color: #1d4ed8; }
    </style>

    <div class="bg-white p-6 rounded-2xl shadow">
        <h4 class="text-lg font-semibold mb-4">مقارنة ملفين Excel</h4>
        <form onsubmit="compareFiles(event)" class="space-y-4">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                <div class="space-y-3">
                    <label class="block text-sm font-medium">الملف الأول</label>
                    <input type="file" id="fileA" accept=".xlsx,.xls,.csv" required class="w-full" />
                </div>
                <div class="space-y-3">
                    <label class="block text-sm font-medium">الملف الثاني</label>
                    <input type="file" id="fileB" accept=".xlsx,.xls,.csv" required class="w-full" />
                </div>
            </div>
            <div class="flex flex-col sm:flex-row gap-3">
                <button type="submit" class="rounded bg-blue-600 text-white px-4 py-2">مقارنة</button>
            </div>
        </form>
    </div>

    <div class="mt-6 bg-white rounded-2xl shadow overflow-hidden">
        <table class="compare-table w-full text-sm text-right">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3">اسم المنتج</th>
                    <th class="p-3">سعر </th>
                    <th class="p-3">سعر </th>
                    <th class="p-3">الخصم</th>
                    <th class="p-3">الفرق</th>
                    <th class="p-3">الأفضل</th>
                </tr>
            </thead>
            <tbody id="compareTable"></tbody>
        </table>
        <div id="compareActions" class="p-4"></div>
    </div>
@endsection

@push('scripts')
    <script>
        let latestCompareData = null;

        async function compareFiles(event) {
            event.preventDefault();

            const formData = new FormData();
            formData.append('file_a', document.getElementById('fileA').files[0]);
            formData.append('file_b', document.getElementById('fileB').files[0]);
            // Fixed mapping as agreed: name C, price B, discount A
            formData.append('col_name_a', 'C');
            formData.append('col_price_a', 'B');
            formData.append('col_discount_a', 'A');
            formData.append('col_name_b', 'C');
            formData.append('col_price_b', 'B');
            formData.append('col_discount_b', 'A');
            formData.append('min_similarity', 80);

            try {
                const res = await axios.post('/compare-files', formData);
                latestCompareData = res.data;
                renderCompare(res.data);
            } catch (err) {
                clientNotify('خطأ في المقارنة. تأكد من تنسيق الملف وتوافق الأعمدة.', 'error');
            }
        }

        function renderCompare(data) {
            const table = document.getElementById('compareTable');
            document.getElementById('compareActions').innerHTML = '';

            if (!data.pairs || data.pairs.length === 0) {
                table.innerHTML =
                    '<tr><td colspan="6" class="p-4 text-center text-slate-500">لا توجد أزواج متطابقة.</td></tr>';
                return;
            }

            table.innerHTML = data.pairs.map(pair => {
                const priceA = pair.file_a.price.toFixed(2);
                const priceB = pair.file_b.price.toFixed(2);
                const discountA = Number(pair.file_a.discount || 0).toFixed(2);
                const discountB = Number(pair.file_b.discount || 0).toFixed(2);
                const diff = Math.abs(pair.file_a.price - pair.file_b.price).toFixed(2);
                const best = pair.file_a.price < pair.file_b.price ? 'الملف الأول' : 'الملف الثاني';
                const bestClass = best === 'الملف الأول' ? 'pill-best-a' : 'pill-best-b';
                return `
            <tr class="border-t">
                <td class="p-3">${pair.file_a.raw_name}</td>
                <td class="p-3"><span class="pill-price">${priceA}</span></td>
                <td class="p-3"><span class="pill-price">${priceB}</span></td>
                <td class="p-3">
                    <span class="pill-discount">${discountA}%</span>
                    <span class="pill-discount">${discountB}%</span>
                </td>
                <td class="p-3 font-semibold text-amber-700">${diff}</td>
                <td class="p-3"><span class="pill-discount ${bestClass}">${best}</span></td>
            </tr>
        `;
            }).join('');

            const saveBtn = document.createElement('button');
            saveBtn.type = 'button';
            saveBtn.textContent = 'حفظ هذه المقارنة';
            saveBtn.className = 'rounded bg-green-600 text-white px-4 py-2';
            saveBtn.onclick = () => saveComparison();
            document.getElementById('compareActions').appendChild(saveBtn);
        }

        async function saveComparison() {
            if (!latestCompareData) {
                return;
            }

            const title = prompt('اكتب عنوان المقارنة:');
            if (!title) return;

            try {
                await axios.post('/saved-comparisons', {
                    title,
                    payload: latestCompareData
                });
                clientNotify('تم حفظ المقارنة بنجاح', 'success');
            } catch (err) {
                clientNotify('فشل حفظ المقارنة', 'error');
            }
        }
    </script>
@endpush
