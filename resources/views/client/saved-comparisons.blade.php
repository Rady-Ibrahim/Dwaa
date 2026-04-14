@extends('layouts.client')

@section('title', 'المقارنات المحفوظة')

@section('content')
    <div class="mb-6">
        <div>
            <h3 class="text-xl font-semibold">المقارنات المحفوظة</h3>
            <p class="text-sm text-slate-500">عرض وحذف المقارنات المحفوظة.</p>
        </div>
    </div>

    <div class="client-card overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3">العنوان</th>
                    <th class="p-3">التاريخ</th>
                    <th class="p-3">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="comparisonsTable"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        async function loadComparisons() {
            try {
                const res = await axios.get('/saved-comparisons');
                renderComparisons(res.data.data || []);
            } catch (err) {
                console.error(err);
                document.getElementById('comparisonsTable').innerHTML =
                    '<tr><td colspan="3" class="p-4 text-center text-red-500">فشل تحميل البيانات.</td></tr>';
            }
        }

        function renderComparisons(items) {
            const table = document.getElementById('comparisonsTable');
            if (!items.length) {
                table.innerHTML =
                    '<tr><td colspan="3" class="p-4 text-center text-slate-500">لا توجد مقارنات محفوظة.</td></tr>';
                return;
            }

            table.innerHTML = items.map(item => `
        <tr class="border-t">
            <td class="p-3">${item.title || 'بدون عنوان'}</td>
            <td class="p-3">${new Date(item.created_at).toLocaleString('ar-EG')}</td>
            <td class="p-3">
                <button onclick="deleteComparison(${item.id})" class="text-red-500">حذف</button>
            </td>
        </tr>
    `).join('');
        }

        async function deleteComparison(id) {
            try {
                await axios.delete('/saved-comparisons/' + id);
                clientNotify('تم حذف المقارنة', 'success');
                loadComparisons();
            } catch (err) {
                clientNotify('خطأ في الحذف', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', loadComparisons);
    </script>
@endpush
