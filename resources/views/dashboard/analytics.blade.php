@extends('layouts.admin')

@section('title', 'التقارير')
@section('heading', 'تحليلات البحث والنشاط')
@section('subheading', 'بحث نصي/إكسل، ومقارنة ملفين — تُحدَّث مع الاستخدام')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
            <h3 class="mb-3 text-sm font-semibold text-white">أكثر استعلامات تكراراً</h3>
            <p class="mb-2 text-xs text-zinc-500">نص يدوي أو أسماء من إكسل (سطر بسطر) — بدون ملخص «ملف واحد».</p>
            <ul class="space-y-2 text-sm">
                @forelse ($mostSearched as $row)
                    <li class="flex justify-between gap-2 border-b border-zinc-800/80 py-1 text-zinc-300">
                        <span class="truncate">{{ $row->query }}</span>
                        <span class="shrink-0 text-teal-400">{{ $row->c }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">لا بيانات بعد.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
            <h3 class="mb-3 text-sm font-semibold text-amber-200">بحث بدون نتائج (صفر منتجات)</h3>
            <p class="mb-2 text-xs text-zinc-500">يساعد على معرفة أدوية مطلوبة وغير موجودة في الماستر.</p>
            <ul class="space-y-2 text-sm">
                @forelse ($noResults as $row)
                    <li class="flex justify-between gap-2 border-b border-zinc-800/80 py-1 text-zinc-300">
                        <span class="truncate">{{ $row->query }}</span>
                        <span class="shrink-0 text-amber-400">{{ $row->c }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">لا سجلات (أو لم يُسجَّل بعد).</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
            <h3 class="mb-3 text-sm font-semibold text-orange-200">بحث بمنتجات لكن بدون عروض حيّة</h3>
            <p class="mb-2 text-xs text-zinc-500">فرصة لجلب موردين لهذه الأصناف.</p>
            <ul class="space-y-2 text-sm">
                @forelse ($noOffers as $row)
                    <li class="flex justify-between gap-2 border-b border-zinc-800/80 py-1 text-zinc-300">
                        <span class="truncate">{{ $row->query }}</span>
                        <span class="shrink-0 text-orange-300">{{ $row->c }}</span>
                    </li>
                @empty
                    <li class="text-zinc-500">لا سجلات بعد.</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5 lg:col-span-2">
            <h3 class="mb-3 text-sm font-semibold text-white">نشاط المستخدمين: بحث + مقارنات</h3>
            <p class="mb-3 text-xs text-zinc-500">عدد سجلات البحث (نص/إكسل) وعدد عمليات مقارنة الملفين.</p>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[320px] text-sm">
                    <thead>
                        <tr class="border-b border-zinc-800 text-left text-xs text-zinc-500">
                            <th class="pb-2 pe-4 font-medium">المستخدم</th>
                            <th class="pb-2 pe-4 font-medium">الهاتف</th>
                            <th class="pb-2 pe-4 font-medium">بحث</th>
                            <th class="pb-2 font-medium">مقارنات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($activityByUser as $row)
                            <tr class="border-b border-zinc-800/60 text-zinc-300">
                                <td class="py-2 pe-4">{{ $row->user->name }}</td>
                                <td class="py-2 pe-4 font-mono text-xs text-zinc-400" dir="ltr">{{ $row->user->phone }}</td>
                                <td class="py-2 pe-4 text-teal-400">{{ $row->searches }}</td>
                                <td class="py-2 text-violet-300">{{ $row->comparisons }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="py-4 text-zinc-500">لا بيانات بعد.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
