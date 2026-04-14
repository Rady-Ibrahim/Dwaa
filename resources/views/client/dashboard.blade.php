@extends('layouts.client')

@section('title', 'الرئيسية')

@section('content')
    <div class="mb-6 rounded-2xl bg-gradient-to-l from-sky-500 to-sky-400 p-6 text-white shadow">
        <div class="mb-2 text-3xl font-extrabold tracking-wide">
            <span class="text-white">Med</span>
            <span class="text-rose-100">RANKO</span>
        </div>
        <h3 class="text-2xl font-bold">مرحبًا بك في لوحة العميل</h3>
        <p class="mt-2 text-sm text-sky-100">واجهة سلسة لإدارة البحث والمقارنات والمفضلة بسرعة.</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
            <h4 class="text-lg font-semibold">آخر عملية بحث</h4>
            <p id="lastSearch" class="text-2xl text-sky-700">-</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
            <h4 class="text-lg font-semibold">عدد المنتجات المفضلة</h4>
            <p id="favoritesCount" class="text-2xl text-sky-700">0</p>
        </div>
        <div class="rounded-2xl border border-sky-100 bg-white p-4 shadow-sm transition hover:-translate-y-0.5 hover:shadow">
            <h4 class="text-lg font-semibold">عدد المقارنات المحفوظة</h4>
            <p id="savedCount" class="text-2xl text-sky-700">0</p>
        </div>
    </div>
    <div class="mt-6 rounded-2xl border border-sky-100 bg-white p-6 shadow-sm">
        <h4 class="text-lg font-semibold mb-3">ملخص العميل</h4>
        <p class="text-slate-600">آخر بحث: <span id="summaryLastSearch">-</span></p>
        <p class="text-slate-600">عدد عمليات البحث المحلية: <span id="searchCount">0</span></p>
    </div>
@endsection

@push('scripts')
    <script>
        async function loadDashboard() {
            try {
                const [favoritesRes, savedRes] = await Promise.all([
                    axios.get('/favorites'),
                    axios.get('/saved-comparisons')
                ]);

                document.getElementById('favoritesCount').textContent = favoritesRes.data.data.length;
                document.getElementById('savedCount').textContent = savedRes.data.data.length;
            } catch (err) {
                console.error(err);
            }

            const lastSearch = localStorage.getItem('client_last_search') || 'لا يوجد بحث سابق';
            const searchCount = localStorage.getItem('client_search_count') || 0;
            document.getElementById('lastSearch').textContent = lastSearch;
            document.getElementById('summaryLastSearch').textContent = lastSearch;
            document.getElementById('searchCount').textContent = searchCount;
        }

        document.addEventListener('DOMContentLoaded', loadDashboard);
    </script>
@endpush
