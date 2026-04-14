@extends('layouts.client')

@section('title', 'المفضلة')

@section('content')
    <div class="client-card overflow-hidden">
        <table class="w-full text-sm text-right">
            <thead class="bg-slate-50">
                <tr>
                    <th class="p-3">اسم المنتج</th>
                    <th class="p-3">كود</th>
                    <th class="p-3">الإجراء</th>
                </tr>
            </thead>
            <tbody id="favoritesTable"></tbody>
        </table>
    </div>
@endsection

@push('scripts')
    <script>
        async function loadFavorites() {
            try {
                const res = await axios.get('/favorites');
                renderFavorites(res.data.data || []);
            } catch (err) {
                console.error(err);
                document.getElementById('favoritesTable').innerHTML =
                    '<tr><td colspan="3" class="p-4 text-center text-red-500">فشل تحميل المفضلة.</td></tr>';
            }
        }

        function renderFavorites(items) {
            const table = document.getElementById('favoritesTable');
            if (!items.length) {
                table.innerHTML =
                    '<tr><td colspan="3" class="p-4 text-center text-slate-500">لا توجد منتجات مفضلة.</td></tr>';
                return;
            }

            table.innerHTML = items.map(item => `
        <tr class="border-t">
            <td class="p-3">${item.product?.name_ar || item.product?.name_en || 'غير معروف'}</td>
            <td class="p-3">${item.product?.code || '-'}</td>
            <td class="p-3">
                <button onclick="removeFavorite(${item.product_id})" class="text-red-500">حذف</button>
            </td>
        </tr>
    `).join('');
        }

        async function removeFavorite(productId) {
            try {
                await axios.delete('/favorites/' + productId);
                clientNotify('تم حذف المنتج من المفضلة', 'success');
                loadFavorites();
            } catch (err) {
                clientNotify('فشل الحذف', 'error');
            }
        }

        document.addEventListener('DOMContentLoaded', loadFavorites);
    </script>
@endpush
