@extends('layouts.admin')

@section('title', 'سجل بحث — ' . $user->name)
@section('heading', 'سجل بحث: ' . $user->name)
@section('subheading', 'كل استعلامات البحث لهذا العميل — ' . $user->phone)

@section('content')
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <a href="{{ route('dashboard.analytics.search-logs') }}"
            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-400 hover:bg-white/5">→ كل السجلات</a>
        <a href="{{ route('dashboard.users') }}"
            class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-400 hover:bg-white/5">إدارة المستخدمين</a>
        <span class="ms-auto rounded-lg bg-zinc-800 px-3 py-1.5 text-xs text-zinc-300">إجمالي السجلات: {{ $logs->total() }}</span>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
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
                        <td class="max-w-[320px] px-4 py-3">
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
                        <td colspan="6" class="px-4 py-10 text-center text-zinc-500">لا سجلات بحث لهذا المستخدم.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection
