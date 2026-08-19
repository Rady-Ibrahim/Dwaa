@extends('layouts.client')

@section('title', 'المقارنات المحفوظة')

@section('content')
    <div class="space-y-6">
        <div
            class="bg-[#111111]/95 backdrop-blur-xl border border-blue-500/35 rounded-2xl p-5 shadow-[0_18px_45px_rgba(37,99,235,0.08)]">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-xl font-semibold text-white">المقارنات المحفوظة</h3>
                    <p class="text-sm text-slate-400">ابحث في المقارنات المحفوظة وادخل لأي مقارنة لعرض المنتجات المطابقة.</p>
                </div>
                <div class="flex gap-3">
                    <input id="searchInput" type="text" placeholder="ابحث باسم المقارنة"
                        class="w-full lg:w-80 rounded-xl bg-[#0b0b0b] border border-blue-500/25 px-4 py-3 text-sm text-white placeholder:text-slate-500 shadow-inner shadow-black/30 transition duration-200 focus:outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-500/20">
                    <button onclick="loadComparisons(1)"
                        class="px-5 py-3 rounded-xl text-white font-bold transition-all duration-200 hover:brightness-110 active:scale-[0.99]"
                        style="background: linear-gradient(135deg, #1d4ed8 0%, #2563eb 52%, #1e3a8a 100%); border: 1px solid rgba(147, 197, 253, 0.25); box-shadow: 0 12px 25px rgba(37, 99, 235, 0.35);">
                        بحث
                    </button>
                </div>
            </div>
        </div>

        <div
            class="bg-[#121212]/90 backdrop-blur-2xl border border-blue-500/30 rounded-[1.5rem] overflow-hidden shadow-[0_18px_40px_rgba(0,0,0,0.35)]">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-right border-collapse">
                    <thead>
                        <tr
                            class="bg-[#0d0d0d] text-slate-300 uppercase text-xs tracking-widest border-b border-blue-500/20">
                            <th class="p-5 font-bold">اسم المقارنة</th>
                            <th class="p-5 font-bold">التاريخ</th>
                            <th class="p-5 font-bold text-center">المنتجات المطابقة</th>
                            <th class="p-5 font-bold text-center w-40">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody id="comparisonsTable" class="divide-y divide-blue-500/10"></tbody>
                </table>
            </div>

            <div id="comparisonsPagination" class="p-6 bg-[#0d0d0d]/70 border-t border-blue-500/20"></div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let lastLoadedPage = 1;

        async function loadComparisons(page = 1) {
            const tableBody = document.getElementById('comparisonsTable');
            try {
                lastLoadedPage = page;
                const q = document.getElementById('searchInput')?.value.trim() || '';
                const res = await axios.get('/saved-comparisons', {
                    params: {
                        page,
                        q
                    }
                });
                renderComparisons(res.data);
            } catch (err) {
                console.error(err);
                tableBody.innerHTML = `
                <tr>
                    <td colspan="4" class="p-10 text-center">
                        <div class="text-red-400 bg-red-400/10 py-3 px-6 rounded-full inline-block border border-red-400/20">
                            فشل تحميل البيانات، يرجى المحاولة لاحقاً.
                        </div>
                    </td>
                </tr>`;
            }
        }

        function renderComparisons(response) {
            const items = response.data || [];
            const table = document.getElementById('comparisonsTable');
            const pagination = document.getElementById('comparisonsPagination');

            if (!items.length) {
                table.innerHTML = `
                <tr>
                    <td colspan="4" class="p-16 text-center text-slate-500 italic">
                        لا توجد مقارنات محفوظة حالياً..
                    </td>
                </tr>`;
                pagination.innerHTML = '';
                return;
            }

            table.innerHTML = items.map(item => {
                const date = new Date(item.created_at);
                const formattedDate = date.toLocaleDateString('ar-EG', {
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric'
                });
                const formattedTime = date.toLocaleTimeString('ar-EG', {
                    hour: '2-digit',
                    minute: '2-digit'
                });

                return `
                <tr class="hover:bg-white/[0.02] transition-all duration-300 group">
                    <td class="p-5">
                        <div class="flex flex-col">
                            <span class="text-white font-semibold text-base group-hover:text-[#ff7787] transition-colors">
                                ${item.title || 'مقارنة بدون عنوان'}
                            </span>
                            <span class="text-[10px] text-slate-500 uppercase mt-1 tracking-tighter tracking-widest">ID: #${item.id}</span>
                        </div>
                    </td>
                    <td class="p-5">
                        <div class="flex flex-col text-xs">
                            <span class="text-slate-300">${formattedDate}</span>
                            <span class="text-slate-500 text-[10px]">${formattedTime}</span>
                        </div>
                    </td>
                    <td class="p-5 text-center">
                        <span class="inline-flex items-center justify-center px-3 py-1 rounded-full bg-[#ef233c]/10 text-[#ff7b8b] border border-[#ef233c]/20 font-mono text-xs">
                            ${item.payload?.pairs?.length || 0}
                        </span>
                    </td>
                    <td class="p-5">
                        <div class="flex items-center justify-center gap-2">
                            <a href="/client/saved-comparisons/${item.id}" 
                               class="flex-1 text-center py-2 px-3 rounded-xl transition-all duration-300 text-xs font-bold"
                               style="background: linear-gradient(135deg, rgba(30, 64, 175, 0.95), rgba(37, 99, 235, 0.95)); border: 1px solid rgba(147, 197, 253, 0.18); color: #dbeafe; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);">
                                عرض
                            </a>
                            <button onclick="confirmDelete(${item.id})" 
                                    class="flex-1 py-2 px-3 rounded-xl transition-all duration-300 text-xs font-bold"
                                    style="background: linear-gradient(135deg, rgba(30, 64, 175, 0.95), rgba(37, 99, 235, 0.95)); border: 1px solid rgba(147, 197, 253, 0.18); color: #dbeafe; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);">
                                حذف
                            </button>
                        </div>
                    </td>
                </tr>
            `;
            }).join('');

            renderPagination(response);
        }

        function renderPagination(response) {
            const pagination = document.getElementById('comparisonsPagination');
            pagination.innerHTML = `
            <div class="flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-xs text-slate-500 bg-slate-800/50 px-3 py-1.5 rounded-lg border border-white/5">
                    صفحة <span class="text-white font-bold">${response.current_page}</span> من <span class="text-white font-bold">${response.last_page}</span>
                </span>
                <div class="flex items-center gap-2">
                    <button ${!response.prev_page_url ? 'disabled' : ''} 
                            onclick="loadComparisons(${response.current_page - 1})"
                            class="px-5 py-2 rounded-xl text-xs font-bold transition-all ${!response.prev_page_url ? 'opacity-20 cursor-not-allowed' : 'active:scale-95'}"
                            style="${!response.prev_page_url ? '' : 'background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(37, 99, 235, 0.96)); border: 1px solid rgba(147, 197, 253, 0.18); color: #dbeafe; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);'}">
                        السابق
                    </button>
                    <button ${!response.next_page_url ? 'disabled' : ''} 
                            onclick="loadComparisons(${response.current_page + 1})"
                            class="px-5 py-2 rounded-xl text-xs font-bold transition-all ${!response.next_page_url ? 'opacity-20 cursor-not-allowed' : 'active:scale-95'}"
                            style="${!response.next_page_url ? '' : 'background: linear-gradient(135deg, rgba(15, 23, 42, 0.96), rgba(37, 99, 235, 0.96)); border: 1px solid rgba(147, 197, 253, 0.18); color: #dbeafe; box-shadow: 0 8px 18px rgba(37, 99, 235, 0.18);'}">
                        التالي
                    </button>
                </div>
            </div>`;
        }

        // إضافة تأكيد قبل الحذف لزيادة الاحترافية
        async function confirmDelete(id) {
            if (confirm('هل أنت متأكد من حذف هذه المقارنة؟')) {
                try {
                    await axios.delete('/saved-comparisons/' + id);
                    if (window.clientNotify) clientNotify('تم حذف المقارنة بنجاح', 'success');
                    loadComparisons(lastLoadedPage);
                } catch (err) {
                    if (window.clientNotify) clientNotify('حدث خطأ أثناء الحذف', 'error');
                }
            }
        }

        document.addEventListener('DOMContentLoaded', () => loadComparisons(1));
    </script>
@endpush
