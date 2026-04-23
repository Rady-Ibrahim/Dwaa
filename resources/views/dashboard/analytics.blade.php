@extends('layouts.admin')

@section('title', 'التحليلات')
@section('heading', 'تحليلات البحث والنشاط')

@section('content')
    <div class="mb-8 grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-white/[0.06] bg-gradient-to-br from-[#8B1538]/20 to-[#18161c] p-6 shadow-lg">
            <p class="text-xs font-medium uppercase tracking-wider text-rose-200/70">إجمالي عمليات البحث</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-white">{{ number_format($overview['searches']) }}</p>
            <p class="mt-2 text-xs text-zinc-500">نص يدوي + صف إكسل (بدون ملخص الملف)</p>
        </div>
        <div class="rounded-2xl border border-white/[0.06] bg-gradient-to-br from-violet-950/40 to-[#18161c] p-6 shadow-lg">
            <p class="text-xs font-medium uppercase tracking-wider text-violet-300/80">مقارنات الملفين</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-white">{{ number_format($overview['comparisons']) }}</p>
            <p class="mt-2 text-xs text-zinc-500">عدد مرات تشغيل المقارنة</p>
        </div>
        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg">
            <p class="text-xs font-medium uppercase tracking-wider text-zinc-500">مستخدمون نشطو بحث</p>
            <p class="mt-3 text-3xl font-bold tabular-nums text-zinc-100">{{ number_format($overview['searching_users']) }}</p>
            <p class="mt-2 text-xs text-zinc-500">مستخدمون لهم سجل بحث مؤهل</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#8B1538]/20 text-rose-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                </span>
                <div>
                    <h3 class="font-semibold text-white">أكثر استعلامات تكراراً</h3>
                    <p class="text-xs text-zinc-500">نص يدوي أو أسماء من إكسل (سطر بسطر)</p>
                </div>
            </div>
            <ul class="space-y-4">
                @forelse ($mostSearched as $row)
                    @php $w = round($row->c / $maxSearched * 100); @endphp
                    <li>
                        <div class="mb-1 flex justify-between gap-2 text-sm">
                            <span class="truncate text-zinc-300">{{ $row->query }}</span>
                            <span class="shrink-0 font-mono text-sm tabular-nums text-rose-300">{{ $row->c }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/35">
                            <div class="h-full rounded-full" style="width: {{ $w }}%; background: linear-gradient(90deg, #8B1538, #fb7185);"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-zinc-500">لا بيانات بعد.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg ring-1 ring-amber-900/20">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-950/50 text-amber-400">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
                </span>
                <div>
                    <h3 class="font-semibold text-amber-100">بحث بدون نتائج</h3>
                    <p class="text-xs text-zinc-500">صفر منتجات — فرصة لتوسيع الماستر</p>
                </div>
            </div>
            <ul class="space-y-4">
                @forelse ($noResults as $row)
                    @php $w = round($row->c / $maxNoResults * 100); @endphp
                    <li>
                        <div class="mb-1 flex justify-between gap-2 text-sm">
                            <span class="truncate text-zinc-300">{{ $row->query }}</span>
                            <span class="shrink-0 font-mono text-sm tabular-nums text-amber-400">{{ $row->c }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/35">
                            <div class="h-full rounded-full bg-gradient-to-l from-amber-600 to-amber-400" style="width: {{ $w }}%;"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-8 text-center text-sm text-zinc-500">لا سجلات (أو لم يُسجَّل بعد).</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg ring-1 ring-orange-900/20 lg:col-span-2">
            <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-orange-950/40 text-orange-300">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m0-12h9.75m-9.75 0a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5z"/></svg>
                    </span>
                    <div>
                        <h3 class="font-semibold text-orange-100">منتجات ظهرت بدون عروض حيّة</h3>
                        <p class="text-xs text-zinc-500">فرصة لجذب موردين لهذه الأصناف</p>
                    </div>
                </div>
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                @forelse ($noOffers as $row)
                    @php $w = round($row->c / $maxNoOffers * 100); @endphp
                    <div>
                        <div class="mb-1 flex justify-between gap-2 text-sm">
                            <span class="truncate text-zinc-300">{{ $row->query }}</span>
                            <span class="shrink-0 font-mono text-sm tabular-nums text-orange-300">{{ $row->c }}</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-black/35">
                            <div class="h-full rounded-full bg-gradient-to-l from-orange-600 to-orange-400" style="width: {{ $w }}%;"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-6 text-center text-sm text-zinc-500 lg:col-span-2">لا سجلات بعد.</div>
                @endforelse
            </div>
        </div>

        <div class="rounded-2xl border border-white/[0.06] bg-[#18161c] p-6 shadow-lg lg:col-span-2">
            <div class="mb-4 flex items-center gap-3">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-zinc-800 text-zinc-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0z"/></svg>
                </span>
                <div>
                    <h3 class="font-semibold text-white">نشاط المستخدمين</h3>
                    <p class="text-xs text-zinc-500">بحث + مقارنات — أعلى النشاط أولاً</p>
                </div>
            </div>
            <div class="overflow-x-auto rounded-xl border border-white/[0.04]">
                <table class="w-full min-w-[520px] text-sm">
                    <thead>
                        <tr class="border-b border-white/[0.06] bg-black/20 text-right text-xs text-zinc-500">
                            <th class="px-4 py-3 font-medium">المستخدم</th>
                            <th class="px-4 py-3 font-medium">الهاتف</th>
                            <th class="px-4 py-3 font-medium">بحث</th>
                            <th class="px-4 py-3 font-medium">مقارنات</th>
                            <th class="px-4 py-3 font-medium">النشاط</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activityByUser as $row)
                            @php $total = $row->searches + $row->comparisons; $actW = round($total / $maxActivity * 100); @endphp
                            <tr class="border-b border-white/[0.04] text-zinc-300 transition hover:bg-white/[0.02]">
                                <td class="px-4 py-3 font-medium text-white">{{ $row->user->name }}</td>
                                <td class="px-4 py-3 font-mono text-xs text-zinc-500" dir="ltr">{{ $row->user->phone }}</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-lg bg-[#8B1538]/20 px-2 py-0.5 font-mono text-rose-200">{{ $row->searches }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="rounded-lg bg-violet-950/60 px-2 py-0.5 font-mono text-violet-200">{{ $row->comparisons }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="h-2 max-w-[120px] overflow-hidden rounded-full bg-black/35">
                                        <div class="h-full rounded-full bg-gradient-to-l from-[#8B1538] to-violet-500" style="width: {{ $actW }}%;"></div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-zinc-500">لا بيانات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
