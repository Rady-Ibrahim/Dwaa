@extends('layouts.client')

@section('title', 'المفضلة')

@section('content')
    <style>
        .fav-card {
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .fav-card:hover {
            border-color: rgba(244, 63, 94, 0.3);
            transform: translateY(-2px);
            background: rgba(15, 23, 42, 0.6);
        }

        .empty-state {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.4), rgba(30, 41, 59, 0.2));
        }

        .fav-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.75rem;
            background: transparent;
        }

        .fav-table thead th {
            color: #38bdf8;
            text-align: right;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .fav-table tbody tr {
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 1.5rem;
        }

        .fav-table td {
            padding: 1rem 1.25rem;
            color: #e2e8f0;
            vertical-align: middle;
        }

        .fav-table td:first-child,
        .fav-table td:nth-child(2),
        .fav-table td:nth-child(3),
        .fav-table td:nth-child(4) {
            text-align: right;
        }

        .fav-table td:last-child {
            text-align: center;
        }

        .fav-delete-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            border-radius: 14px;
            background: rgba(248, 113, 113, 0.14);
            color: #fda4af;
            transition: all 0.2s ease;
        }

        .fav-delete-btn:hover {
            background: rgba(248, 113, 113, 0.25);
            color: #fecaca;
        }

        .fav-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            padding: 0.45rem 0.85rem;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .fav-pill.price {
            background: rgba(56, 189, 248, 0.12);
            color: #38bdf8;
        }

        .fav-pill.discount {
            background: rgba(16, 185, 129, 0.12);
            color: #4ade80;
        }
    </style>

    <div class="space-y-6">
        <div
            class="flex items-center justify-between bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl p-6">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-2xl bg-rose-500/20 flex items-center justify-center text-2xl">⭐</div>
                <div>
                    <h3 class="text-xl font-bold text-white">قائمة المفضلة</h3>
                    <p class="text-slate-400 text-sm">المنتجات التي قمت بحفظها للرجوع إليها لاحقاً</p>
                </div>
            </div>
            <div id="favCount"
                class="px-4 py-1 bg-white/5 border border-white/10 rounded-full text-xs font-mono text-slate-400">
                0 منتجات
            </div>
        </div>

        <div id="favoritesTableContainer" class="custom-table-card overflow-hidden">
            <table class="fav-table w-full text-sm">
                <thead>
                    <tr>
                        <th class="text-right px-5 py-4">المورد</th>
                        <th class="text-right px-5 py-4">الصنف</th>
                        <th class="text-right px-5 py-4">السعر</th>
                        <th class="text-right px-5 py-4">الخصم</th>
                        <th class="text-center px-5 py-4">الإجراء</th>
                    </tr>
                </thead>
                <tbody id="favoritesTable">
                    <tr>
                        <td colspan="5" class="p-12 text-center text-slate-400">جاري جلب المفضلة...</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        async function loadFavorites() {
            try {
                const res = await axios.get('/favorites');
                const items = res.data.data || [];
                document.getElementById('favCount').textContent = `${items.length} منتجات`;
                renderFavorites(items);
            } catch (err) {
                console.error(err);
                const container = document.getElementById('favoritesTableContainer');
                container.innerHTML = `
                    <div class="p-12 text-center bg-rose-500/5 border border-rose-500/10 rounded-3xl text-rose-400">
                        حدث خطأ أثناء تحميل البيانات، يرجى المحاولة لاحقاً.
                    </div>
                `;
            }
        }

        function renderFavorites(items) {
            const container = document.getElementById('favoritesTableContainer');
            const tableBody = document.getElementById('favoritesTable');

            if (!items.length) {
                container.innerHTML = `
                    <div class="empty-state rounded-3xl p-20 text-center border border-dashed border-white/10">
                        <div class="text-6xl mb-4 opacity-20">📂</div>
                        <h4 class="text-lg font-bold text-slate-400">لا توجد منتجات مفضلة</h4>
                        <p class="text-slate-500 text-sm mt-2">يمكنك إضافة المنتجات إلى المفضلة من خلال صفحة البحث.</p>
                        <a href="/client/search" class="inline-block mt-6 px-6 py-2 bg-sky-600/10 hover:bg-sky-600/20 text-sky-400 rounded-xl text-sm transition-all border border-sky-600/20">
                            اذهب للبحث الآن
                        </a>
                    </div>
                `;
                return;
            }

            container.innerHTML = `
                <table class="fav-table w-full text-sm">
                    <thead>
                        <tr>
                            <th class="text-right px-5 py-4">المورد</th>
                            <th class="text-right px-5 py-4">الصنف</th>
                            <th class="text-right px-5 py-4">السعر</th>
                            <th class="text-right px-5 py-4">الخصم</th>
                            <th class="text-center px-5 py-4">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody id="favoritesTable"></tbody>
                </table>
            `;

            const refreshedBody = document.getElementById('favoritesTable');
            refreshedBody.innerHTML = items.map(item => {
                const name = item.product?.name_ar || item.product?.name_en || 'منتج غير مسمى';
                const code = item.product?.code || 'N/A';
                const offer = item.product?.offers?.[0] || null;
                const supplierName = offer?.supplier?.name || item.product?.supplier?.name || 'غير محدد';
                const priceText = offer ? `${offer.price} ج` : 'غير متاح';
                const discountText = offer ? `${offer.discount}%` : '-';

                return `
                    <tr data-product-id="${item.product_id}">
                        <td class="px-5 py-4">
                            <div class="text-slate-300 font-semibold">${supplierName}</div>
                            <div class="text-slate-500 text-xs">${code}</div>
                        </td>
                        <td class="px-5 py-4 font-semibold text-white">${name}</td>
                        <td class="px-5 py-4"><span class="fav-pill price">${priceText}</span></td>
                        <td class="px-5 py-4"><span class="fav-pill discount">${discountText}</span></td>
                        <td class="px-5 py-4">
                            <button onclick="removeFavorite(${item.product_id}, this)" class="fav-delete-btn" title="حذف من المفضلة">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        async function removeFavorite(productId, button) {
            try {
                if (button) {
                    button.disabled = true;
                }

                await axios.delete('/favorites/' + productId);

                // إزالة الصف من الـ DOM مباشرة بدون reload (مشكلة 2)
                const row = button?.closest('tr[data-product-id]');
                if (row) {
                    row.remove();
                }

                // تحديث العداد
                const countEl = document.getElementById('favCount');
                const currentCount = Math.max(0, Number(countEl.textContent.match(/\d+/)?.[0] || 0) - 1);
                countEl.textContent = `${currentCount} منتجات`;

                // لو الجدول فاضي بعد الحذف، نعرض حالة الفراغ
                // نستخدم querySelector على tbody بالـ id الصريح
                const tbody = document.getElementById('favoritesTable');
                if (tbody && tbody.querySelectorAll('tr[data-product-id]').length === 0) {
                    renderFavorites([]);
                }

                window.clientNotify('تم الحذف بنجاح', 'success');
            } catch (err) {
                window.clientNotify('فشل الحذف، حاول مجدداً', 'error');
            } finally {
                if (button) {
                    button.disabled = false;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', loadFavorites);
    </script>
@endpush
