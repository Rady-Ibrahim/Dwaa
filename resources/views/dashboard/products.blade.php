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
                <label class="mb-1 block text-xs text-zinc-500">بحث (اسم، كود، تطبيع)</label>
                <input type="search" name="q" value="{{ request('q') }}" placeholder="اكتب للبحث…" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="auto">
            </div>
            <div class="flex gap-2">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-500">تطبيق</button>
                <a href="{{ route('dashboard.products') }}" class="rounded-lg border border-zinc-600 px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800">إعادة ضبط</a>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">المورد</th>
                    <th class="px-4 py-3">الكود</th>
                    <th class="px-4 py-3">الاسم (عربي)</th>
                    <th class="px-4 py-3">الاسم (إنجليزي)</th>
                    <th class="px-4 py-3">التطبيع</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($products as $p)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3 text-zinc-500">{{ $p->id }}</td>
                        <td class="px-4 py-3 text-zinc-300">
                            @if ($p->supplier)
                                {{ $p->supplier->name }}
                            @else
                                <span class="text-zinc-600">— مرجعي —</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-teal-300/90" dir="ltr">{{ $p->code }}</td>
                        <td class="px-4 py-3 text-white">{{ $p->name_ar ?? '—' }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $p->name_en ?? '—' }}</td>
                        <td class="max-w-xs truncate px-4 py-3 text-xs text-zinc-500" dir="ltr" title="{{ $p->normalized_name }}">{{ $p->normalized_name }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-10 text-center text-zinc-500">لا توجد منتجات تطابق التصفية.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $products->links() }}</div>
@endsection
