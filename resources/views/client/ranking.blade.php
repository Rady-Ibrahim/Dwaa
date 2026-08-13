@extends('layouts.client')

@section('title', 'الترتيب')

@section('content')
    <style>
        .rank-tab {
            padding: 0.65rem 1.25rem;
            border-radius: 14px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(148, 163, 184, 0.16);
            background: rgba(15, 23, 42, 0.6);
            color: #cbd5e1;
        }

        .rank-tab.active {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.2), rgba(139, 92, 246, 0.12));
            border-color: rgba(96, 165, 250, 0.45);
            color: #e0f2fe;
            box-shadow: 0 0 24px rgba(59, 130, 246, 0.18);
        }

        .rank-badge {
            width: 2rem;
            height: 2rem;
            border-radius: 12px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.85rem;
            background: rgba(148, 163, 184, 0.12);
            color: #cbd5e1;
            border: 1px solid rgba(148, 163, 184, 0.16);
        }

        .rank-badge.top-1 {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.25), rgba(251, 191, 36, 0.1));
            border-color: rgba(245, 158, 11, 0.5);
            color: #fcd34d;
        }

        .rank-badge.top-2 {
            background: rgba(148, 163, 184, 0.2);
            border-color: rgba(203, 213, 225, 0.35);
            color: #e2e8f0;
        }

        .rank-badge.top-3 {
            background: rgba(217, 119, 6, 0.2);
            border-color: rgba(217, 119, 6, 0.45);
            color: #fbbf24;
        }
    </style>

    <div class="space-y-6">
        <div class="bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl p-6">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-amber-500/20 flex items-center justify-center">🏆</div>
                <h4 class="text-xl font-bold text-white">ترتيب الموردين</h4>
            </div>
            <p class="text-sm text-slate-400">قارن الموردين حسب حجم الكتالوج أو جودة الخصومات المتاحة</p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <button id="rankTabItems" class="rank-tab active" onclick="loadRanking('items')">
                📦 حسب عدد الأصناف
            </button>
            <button id="rankTabDiscount" class="rank-tab" onclick="loadRanking('discount')">
                💰 حسب مؤشر الخصم
            </button>
            <span id="rankingMeta" class="text-xs text-slate-500 mr-auto"></span>
        </div>

        <div class="custom-table-card overflow-hidden">
            <table class="w-full text-sm text-right">
                <thead class="bg-slate-950/50 text-slate-400">
                    <tr>
                        <th class="p-4">#</th>
                        <th class="p-4">المورد</th>
                        <th class="p-4">المنطقة</th>
                        <th class="p-4">تليفون</th>
                        <th class="p-4">عدد الأصناف</th>
                        <th class="p-4">مؤشر الخصم</th>
                    </tr>
                </thead>
                <tbody id="rankingTableBody"></tbody>
            </table>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let activeRankingSort = 'items';

        const rankingTableBody = document.getElementById('rankingTableBody');
        const rankingMeta = document.getElementById('rankingMeta');
        const rankTabItems = document.getElementById('rankTabItems');
        const rankTabDiscount = document.getElementById('rankTabDiscount');

        function escapeForAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        function formatIndex(value) {
            if (value === null || value === undefined) return '—';
            return Number(value).toFixed(2);
        }

        async function loadRanking(sort = 'items') {
            activeRankingSort = sort;
            rankTabItems.classList.toggle('active', sort === 'items');
            rankTabDiscount.classList.toggle('active', sort === 'discount');

            rankingTableBody.innerHTML =
                '<tr><td colspan="6" class="p-6 text-center text-slate-400">جاري تحميل الترتيب...</td></tr>';

            try {
                const res = await axios.get('/ranking', {
                    params: { sort, limit: 100 }
                });
                renderRanking(res.data);
            } catch (err) {
                console.error(err);
                rankingTableBody.innerHTML =
                    '<tr><td colspan="6" class="p-6 text-center text-rose-400">فشل تحميل الترتيب.</td></tr>';
                rankingMeta.textContent = '';
            }
        }

        function renderRanking(data) {
            const rows = data?.data || [];
            rankingMeta.textContent = data?.indexed_at ? 'آخر تحديث: ' + new Date(data.indexed_at).toLocaleString('ar-EG') : '';

            if (!rows.length) {
                rankingTableBody.innerHTML =
                    '<tr><td colspan="6" class="p-6 text-center text-slate-500">لا توجد بيانات ترتيب بعد.</td></tr>';
                return;
            }

            const sortByDiscount = data?.sort === 'discount';
            const values = rows.map(r => sortByDiscount
                ? (r.discount_quality_index ?? 0)
                : r.total_items_count);
            const max = Math.max(...values, 1);

            rankingTableBody.innerHTML = rows.map((row, idx) => {
                const badgeClass = idx === 0 ? 'top-1' : idx === 1 ? 'top-2' : idx === 2 ? 'top-3' : '';
                const count = row.total_items_count ?? 0;
                const index = row.discount_quality_index;
                const value = sortByDiscount ? (index ?? 0) : count;
                const width = Math.min(Math.round((value / max) * 100), 100);

                return `
                    <tr class="border-t border-white/5 hover:bg-white/[0.02]">
                        <td class="p-4">
                            <span class="rank-badge ${badgeClass}">${idx + 1}</span>
                        </td>
                        <td class="p-4 font-semibold text-white">${escapeForAttr(row.supplier?.name || '-')}</td>
                        <td class="p-4 text-slate-400">${escapeForAttr(row.supplier?.area || '-')}</td>
                        <td class="p-4 font-mono text-xs text-slate-400" dir="ltr">${escapeForAttr(row.supplier?.phone || '-')}</td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="font-mono tabular-nums ${sortByDiscount ? 'text-slate-400' : 'text-sky-300'}">${count}</span>
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-black/40">
                                    <div class="h-full rounded-full bg-sky-400/80 transition-all" style="width:${sortByDiscount ? 0 : width}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="p-4">
                            <div class="flex items-center gap-2">
                                <span class="${index !== null ? 'text-green-400 font-bold tabular-nums' : 'text-slate-500'}">${formatIndex(index)}${index !== null ? '%' : ''}</span>
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-black/40">
                                    <div class="h-full rounded-full bg-green-400/80 transition-all" style="width:${!sortByDiscount || index === null ? 0 : width}%"></div>
                                </div>
                            </div>
                        </td>
                    </tr>`;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', () => loadRanking('items'));
    </script>
@endpush
