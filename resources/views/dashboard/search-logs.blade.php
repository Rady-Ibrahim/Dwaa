@extends('layouts.admin')

@section('title', 'سجل بحث العملاء')
@section('heading', 'سجل بحث العملاء')
@section('subheading', 'استعراض استعلامات البحث الخاصة بكل عميل')

@section('content')
    <div class="mb-6 rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
        <form method="GET" action="{{ route('dashboard.analytics.search-logs') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="mb-1 block text-xs text-zinc-500">المستخدم</label>
                <select name="user_id" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <option value="">كل المستخدمين</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected(request('user_id') == $user->id)>
                            {{ $user->name }} — {{ $user->phone }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">المصدر</label>
                <select name="source" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <option value="">كل المصادر</option>
                    <option value="text" @selected(request('source') === 'text')>بحث نصي</option>
                    <option value="excel_row" @selected(request('source') === 'excel_row')>صف إكسل</option>
                    <option value="excel_bulk" @selected(request('source') === 'excel_bulk')>ملخص ملف إكسل</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="بحث في الاستعلام"
                    class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                <button type="submit" class="shrink-0 rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-500">بحث</button>
                <a href="{{ route('dashboard.analytics.search-logs') }}"
                    class="shrink-0 rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-400 hover:bg-white/5">مسح</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">المستخدم</th>
                    <th class="px-4 py-3">الاستعلام</th>
                    <th class="px-4 py-3 text-center">المنتج</th>
                    <th class="px-4 py-3 text-center">المصدر</th>
                    <th class="px-4 py-3 text-center">النتائج</th>
                    <th class="px-4 py-3 text-center">عروض</th>
                    <th class="px-4 py-3 text-center">التوقيت</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($logs as $log)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3">
                            @if ($log->user)
                                <a href="{{ route('dashboard.analytics.users.search-logs', $log->user) }}"
                                    class="font-medium text-teal-400 hover:underline">
                                    {{ $log->user->name }}
                                </a>
                                <div class="font-mono text-xs text-zinc-500" dir="ltr">{{ $log->user->phone }}</div>
                            @else
                                <span class="text-zinc-600">محذوف</span>
                            @endif
                        </td>
                        <td class="max-w-[260px] px-4 py-3">
                            <span class="line-clamp-2 text-zinc-300" title="{{ $log->query }}">{{ $log->query }}</span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if ($log->product)
                                <span class="text-xs text-sky-300">{{ $log->product->name_ar ?: $log->product->name_en }}</span>
                            @else
                                <span class="text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            @php $sourceLabels = ['text' => 'نصي', 'excel_row' => 'إكسل سطر', 'excel_bulk' => 'إكسل ملف']; @endphp
                            <span class="rounded-lg bg-zinc-800 px-2 py-0.5 text-xs text-zinc-300">
                                {{ $sourceLabels[$log->source] ?? $log->source }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-zinc-300">{{ $log->results_count }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($log->had_offers === null)
                                <span class="text-zinc-600">—</span>
                            @else
                                <span class="rounded-lg px-2 py-0.5 text-xs {{ $log->had_offers ? 'bg-emerald-950/60 text-emerald-300' : 'bg-amber-950/50 text-amber-300' }}">
                                    {{ $log->had_offers ? 'نعم' : 'لا' }}
                                </span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center font-mono text-xs text-zinc-500">
                            {{ $log->created_at->format('Y-m-d H:i') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-zinc-500">لا سجلات بحث مطابقة.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
