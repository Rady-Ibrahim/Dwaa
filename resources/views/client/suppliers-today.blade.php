@extends('layouts.client')

@section('title', 'موردين اليوم')

@section('content')
    <div class="space-y-6">
        <div class="bg-[#121212]/90 backdrop-blur-xl border border-[#ef233c]/50 rounded-2xl p-5">
            <div class="flex items-center gap-3 mb-2">
                <div class="w-10 h-10 rounded-xl bg-[#ef233c]/15 flex items-center justify-center">🕐</div>
                <h4 class="text-xl font-bold text-white">موردين اليوم</h4>
            </div>
            <p class="text-sm text-slate-400">الموردون الذين حدّثوا عروضهم اليوم</p>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div
                class="bg-[#121212]/90 backdrop-blur-xl border border-[#ef233c]/40 rounded-2xl p-6 lg:col-span-1 flex flex-col items-center justify-center text-center">
                <p class="text-sm text-slate-400 mb-3">عدد الموردين المحدّثين اليوم</p>
                <p id="suppliersTodayCount" class="text-6xl font-black tabular-nums text-[#ff7585] leading-none">—</p>
                <p class="mt-3 text-xs text-slate-500">إجمالي من رفعوا أو حدّثوا ملفاتهم</p>
            </div>

            <div class="custom-table-card overflow-x-auto lg:col-span-2">
                <table class="w-full text-sm text-right">
                    <thead class="bg-[#0d0d0d] text-slate-300">
                        <tr>
                            <th class="p-4">المورد</th>
                            <th class="p-4">المنطقة</th>
                            <th class="p-4">تليفون</th>
                            <th class="p-4">عدد الملفات</th>
                            <th class="p-4">آخر تحديث</th>
                        </tr>
                    </thead>
                    <tbody id="suppliersTodayBody"></tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const suppliersTodayBody = document.getElementById('suppliersTodayBody');
        const suppliersTodayCount = document.getElementById('suppliersTodayCount');

        function escapeForAttr(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;');
        }

        async function loadSuppliersToday() {
            suppliersTodayBody.innerHTML =
                '<tr><td colspan="5" class="p-6 text-center text-slate-400">جاري التحميل...</td></tr>';

            try {
                const res = await axios.get('/suppliers/today');
                renderSuppliersToday(res.data);
            } catch (err) {
                console.error(err);
                suppliersTodayBody.innerHTML =
                    '<tr><td colspan="5" class="p-6 text-center text-rose-400">فشل تحميل البيانات.</td></tr>';
                suppliersTodayCount.textContent = '—';
            }
        }

        function renderSuppliersToday(data) {
            const rows = data?.suppliers || [];
            suppliersTodayCount.textContent = data?.count ?? 0;

            if (!rows.length) {
                suppliersTodayBody.innerHTML =
                    '<tr><td colspan="5" class="p-6 text-center text-slate-500">لا يوجد موردون حدّثوا عروضهم اليوم.</td></tr>';
                return;
            }

            suppliersTodayBody.innerHTML = rows.map(row => {
                const updated = row.last_upload_at ?
                    new Date(row.last_upload_at).toLocaleString('ar-EG') :
                    '-';

                return `
                    <tr class="border-t border-white/5 hover:bg-white/[0.02]">
                        <td class="p-4 font-semibold text-white">${escapeForAttr(row.name || '-')}</td>
                        <td class="p-4 text-slate-400">${escapeForAttr(row.area || '-')}</td>
                        <td class="p-4 font-mono text-xs text-slate-400" dir="ltr">${escapeForAttr(row.phone || '-')}</td>
                        <td class="p-4">
                            <span class="rounded-lg bg-emerald-500/15 px-2 py-0.5 font-mono text-emerald-300">${row.uploads_today ?? 0}</span>
                        </td>
                        <td class="p-4 text-xs text-slate-400">${escapeForAttr(updated)}</td>
                    </tr>`;
            }).join('');
        }

        document.addEventListener('DOMContentLoaded', loadSuppliersToday);
    </script>
@endpush
