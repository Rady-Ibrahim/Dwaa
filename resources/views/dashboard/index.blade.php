@extends('layouts.admin')

@section('title', 'الرئيسية')
@section('heading', 'لوحة التحكم')
@section('subheading', 'نظرة شاملة على النظام والتحليلات')

@section('content')
    @php
        $cardBase = 'group relative overflow-hidden rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg transition hover:border-[#8B1538]/25 hover:shadow-xl';
    @endphp

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="{{ $cardBase }}">
            <div class="absolute -start-4 -top-4 h-24 w-24 rounded-full bg-[#8B1538]/10 blur-2xl transition group-hover:bg-[#8B1538]/20"></div>
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">العملاء</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $stats['users'] }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-[#8B1538]/15 text-[#e11d48]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                </span>
            </div>
        </div>
        <div class="{{ $cardBase }}">
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">الموردون</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $stats['suppliers'] }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-950/50 text-emerald-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008H17.25v-.008zm0 3.75h.008v.008H17.25V18z"/></svg>
                </span>
            </div>
        </div>
        <div class="{{ $cardBase }}">
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">غير مطابق (معلق)</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-amber-400">{{ $stats['pending_unmatched'] }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-amber-950/40 text-amber-500">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                </span>
            </div>
        </div>
        <div class="{{ $cardBase }}">
            <div class="relative flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">رفوعات اليوم</p>
                    <p class="mt-2 text-3xl font-bold tabular-nums text-white">{{ $stats['uploads_today'] }}</p>
                </div>
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-sky-950/50 text-sky-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                </span>
            </div>
        </div>
    </div>

    {{-- تحليلات مختصرة --}}
    <div class="mt-10">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h3 class="text-lg font-semibold text-white">لمحة تحليلات</h3>
                <p class="mt-1 text-sm text-zinc-500">عمليات البحث والمقارنة — يُحدَّث تلقائياً مع استخدام التطبيق</p>
            </div>
            <a href="{{ route('dashboard.analytics') }}" class="inline-flex w-fit max-w-full shrink-0 items-center justify-center gap-2 self-start rounded-xl px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-95 sm:self-auto" style="background: linear-gradient(135deg, #a61e45, #8B1538); box-shadow: 0 8px 28px -8px rgba(139, 21, 56, 0.55);">
                التقرير التفصيلي
                <svg class="h-4 w-4 rtl:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            </a>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 lg:col-span-1">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">ملخص النشاط</p>
                <div class="mt-6 space-y-5">
                    <div class="flex items-center justify-between gap-4 border-b border-white/[0.06] pb-4">
                        <span class="text-sm text-zinc-400">عمليات بحث (نص / إكسل سطر)</span>
                        <span class="text-2xl font-bold tabular-nums text-rose-200">{{ number_format($totalSearches) }}</span>
                    </div>
                    <div class="flex items-center justify-between gap-4 border-b border-white/[0.06] pb-4">
                        <span class="text-sm text-zinc-400">مقارنات ملفين</span>
                        <span class="text-2xl font-bold tabular-nums text-violet-200">{{ number_format($totalComparisons) }}</span>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 lg:col-span-1">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">بحث — آخر 7 أيام</p>
                <div class="mt-6 flex h-44 items-end justify-between gap-2">
                    @foreach ($searchTrend as $d)
                        @php $barPx = $trendMax > 0 ? (int) round($d['count'] / $trendMax * 120) : 0; $barPx = max($d['count'] > 0 ? 8 : 4, $barPx); @endphp
                        <div class="flex flex-1 flex-col items-center justify-end gap-2">
                            <div class="w-full max-w-[2rem] rounded-t-lg transition-all duration-500" style="height: {{ $barPx }}px; background: linear-gradient(180deg, #e11d48, #8B1538);"></div>
                            <span class="text-[10px] text-zinc-500">{{ $d['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 lg:col-span-1">
                <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">أكثر الاستعلامات</p>
                <ul class="mt-4 space-y-3">
                    @forelse ($topQueries as $row)
                        @php $pct = round($row->c / $maxTop * 100); @endphp
                        <li>
                            <div class="mb-1 flex justify-between gap-2 text-xs">
                                <span class="truncate text-zinc-300">{{ $row->query }}</span>
                                <span class="shrink-0 font-mono tabular-nums text-rose-300/90">{{ $row->c }}</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-black/40">
                                <div class="h-full rounded-full transition-all duration-500" style="width: {{ $pct }}%; background: linear-gradient(90deg, #8B1538, #fb7185);"></div>
                            </div>
                        </li>
                    @empty
                        <li class="text-sm text-zinc-500">لا بيانات بعد.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <p class="mt-10 text-center text-xs text-zinc-600">واجهة الإدارة متصلة بـ API التطبيق — إدارة متقدمة عبر Postman أو توسيع الواجهة لاحقاً.</p>
@endsection
