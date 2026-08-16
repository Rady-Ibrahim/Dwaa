@extends('layouts.client')

@section('title', 'الترتيب')

@section('content')
    <style>
        .rank-tab {
            padding: 0.7rem 1.1rem;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 1px solid rgba(239, 35, 60, 0.35);
            background: rgba(15, 15, 15, 0.7);
            color: var(--text-soft);
        }

        .rank-tab.active {
            background: linear-gradient(135deg, rgba(239, 35, 60, 0.24), rgba(239, 35, 60, 0.10));
            border-color: rgba(239, 35, 60, 0.68);
            color: var(--accent-pale);
            box-shadow: 0 0 16px rgba(239, 35, 60, 0.12);
        }

        .rank-badge {
            width: 2rem;
            height: 2rem;
            border-radius: 10px;
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 0.85rem;
            background: rgba(255, 255, 255, 0.04);
            color: var(--text-soft);
            border: 1px solid rgba(239, 35, 60, 0.22);
        }

        .rank-badge.top-1 {
            background: linear-gradient(135deg, rgba(239, 35, 60, 0.25), rgba(239, 35, 60, 0.10));
            border-color: rgba(239, 35, 60, 0.6);
            color: var(--danger-soft);
        }

        .rank-badge.top-2 {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.12);
            color: var(--text-soft2);
        }

        .rank-badge.top-3 {
            background: rgba(239, 35, 60, 0.12);
            border-color: rgba(239, 35, 60, 0.35);
            color: var(--warn-soft);
        }
    </style>

    <div class="space-y-6">
        <div class="bg-[#121212]/90 backdrop-blur-xl border border-[#ef233c]/50 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-[#ef233c]/15 flex items-center justify-center">🏆</div>
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
            <button id="rankingRefreshBtn" class="rank-tab" onclick="refreshRanking()"
                title="إعادة احتساب مؤشر الجودة الآن">
                🔄 تحديث
            </button>
            <span id="rankingMeta" class="text-xs text-slate-500 mr-auto"></span>
        </div>

        <div class="custom-table-card overflow-x-auto">
            <table class="w-full text-sm text-right">
                <thead class="bg-[#0d0d0d] text-slate-300">
                    <tr>
                        <th class="p-4">#</th>
                        <th class="p-4">المورد</th>
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
                '<tr><td colspan="4" class="p-6 text-center text-slate-400">جاري تحميل الترتيب...</td></tr>';

            try {
                const res = await axios.get('/ranking', {
                    params: {
                        sort,
                        limit: 100
                    }
                });
                renderRanking(res.data);
            } catch (err) {
                console.error(err);
                rankingTableBody.innerHTML =
                    '<tr><td colspan="4" class="p-6 text-center text-rose-400">فشل تحميل الترتيب.</td></tr>';
                rankingMeta.textContent = '';
            }
        }

        function renderRanking(data) {
            const rows = data?.data || [];
            rankingMeta.textContent = data?.indexed_at ? 'آخر تحديث: ' + new Date(data.indexed_at).toLocaleString('ar-EG') :
                '';

            if (!rows.length) {
                rankingTableBody.innerHTML =
                    '<tr><td colspan="4" class="p-6 text-center text-slate-500">لا توجد بيانات ترتيب بعد.</td></tr>';
                return;
            }

            const sortByDiscount = data?.sort === 'discount';
            const values = rows.map(r => sortByDiscount ?
                (r.discount_quality_index ?? 0) :
                r.total_items_count);
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

        async function refreshRanking() {
            const btn = document.getElementById('rankingRefreshBtn');
            if (btn.disabled) return;
            btn.disabled = true;
            btn.textContent = '⏳ جاري التحديث...';
            try {
                const res = await axios.post('/ranking/refresh');
                if (window.clientNotify) clientNotify(res.data?.message || 'تم التحديث', 'success');
                await loadRanking(activeRankingSort);
            } catch (err) {
                if (window.clientNotify) {
                    clientNotify(err.response?.data?.message || 'فشل التحديث، حاول مرة أخرى', 'error');
                }
            } finally {
                btn.disabled = false;
                btn.textContent = '🔄 تحديث';
            }
        }

        document.addEventListener('DOMContentLoaded', () => loadRanking('items'));
    </script>
@endpush
