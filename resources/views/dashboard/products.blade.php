@extends('layouts.admin')

@section('title', 'المنتجات')
@section('heading', 'المنتجات')

@section('content')
    <div class="mb-6 rounded-xl border border-zinc-800 bg-zinc-900/40 p-4">
        <form method="GET" action="{{ route('dashboard.products') }}" class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
            <div class="min-w-[12rem] flex-1">
                <label class="mb-1 block text-xs text-zinc-500">المورد</label>
                <select name="supplier_id" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <option value="">— كل الموردين —</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}" @selected((string) request('supplier_id') === (string) $s->id)>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-[12rem] flex-1">
                <label class="mb-1 block text-xs text-zinc-500">بحث (اسم، كود، عربي/إنجليزي)</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="اكتب للبحث…" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="auto">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-500">تطبيق</button>
                <a href="{{ route('dashboard.products') }}" class="rounded-lg border border-zinc-600 px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto rounded-xl border border-zinc-800">
        <table class="min-w-[960px] w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs font-semibold uppercase tracking-wide text-zinc-500">
                <tr>
                    <th class="whitespace-nowrap px-3 py-3">#</th>
                    <th class="min-w-[8rem] px-3 py-3">المورد</th>
                    <th class="whitespace-nowrap px-3 py-3">الكود</th>
                    <th class="min-w-[10rem] px-3 py-3">الاسم (عربي)</th>
                    <th class="min-w-[8rem] px-3 py-3">الاسم (إنجليزي)</th>
                    <th class="whitespace-nowrap px-3 py-3 text-center">السعر</th>
                    <th class="whitespace-nowrap px-3 py-3 text-center">الخصم %</th>
                    <th class="min-w-[6rem] px-3 py-3">البونص</th>
                    <th class="whitespace-nowrap px-3 py-3 text-center">ينتهي</th>
                    <th class="whitespace-nowrap px-3 py-3 text-center">العرض</th>
                    <th class="min-w-[8rem] px-3 py-3">التطبيع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($products as $p)
                    @php
                        $offer = $p->offers->first();
                    @endphp
                    <tr class="hover:bg-zinc-900/40">
                        <td class="whitespace-nowrap px-3 py-3 text-zinc-500">{{ $p->id }}</td>
                        <td class="px-3 py-3 text-zinc-300">
                            @if ($p->supplier)
                                {{ $p->supplier->name }}
                            @else
                                <span class="text-zinc-600">— مرجعي —</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 font-mono text-xs text-teal-300/90" dir="ltr">{{ $p->code }}</td>
                        <td class="px-3 py-3 text-white">{{ $p->name_ar ?? '—' }}</td>
                        <td class="px-3 py-3 text-zinc-400" dir="ltr">{{ $p->name_en ?? '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-center font-medium tabular-nums text-zinc-100" dir="ltr">
                            @if ($offer)
                                {{ number_format((float) $offer->price, 2, '.', ',') }}
                                <span class="text-xs font-normal text-zinc-500">ج</span>
                            @else
                                <span class="text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-center">
                            <x-discount-pill :value="$offer ? (float) $offer->discount : null" />
                        </td>
                        <td class="max-w-[10rem] truncate px-3 py-3 text-zinc-400" title="{{ $offer?->bonus }}">{{ $offer?->bonus ? $offer->bonus : '—' }}</td>
                        <td class="whitespace-nowrap px-3 py-3 text-center text-xs tabular-nums text-zinc-400" dir="ltr">
                            @if ($offer)
                                {{ $offer->expires_at->format('Y-m-d H:i') }}
                            @else
                                <span class="text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-3 py-3 text-center">
                            @if ($offer)
                                @if ($offer->expires_at->isFuture())
                                    <span class="inline-flex rounded-full bg-emerald-500/15 px-2 py-0.5 text-[11px] font-medium text-emerald-300 ring-1 ring-emerald-500/25">نشط</span>
                                @else
                                    <span class="inline-flex rounded-full bg-zinc-700/50 px-2 py-0.5 text-[11px] font-medium text-zinc-400 ring-1 ring-zinc-600/50">منتهي</span>
                                @endif
                            @else
                                <span class="text-zinc-600">—</span>
                            @endif
                        </td>
                        <td class="max-w-[12rem] truncate px-3 py-3 text-xs text-zinc-500" dir="ltr" title="{{ $p->normalized_name }}">{{ $p->normalized_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="11" class="px-4 py-10 text-center text-zinc-500">لا توجد منتجات تطابق التصفية.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
