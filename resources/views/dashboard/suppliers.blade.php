@extends('layouts.admin')

@section('title', 'الموردون')
@section('heading', 'الموردون')

@section('content')
    <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <h3 class="mb-4 text-sm font-semibold text-white">إضافة مورد</h3>
        <form method="POST" action="{{ route('dashboard.suppliers.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs text-zinc-500">الاسم</label>
                <input name="name" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">هاتف 1</label>
                <input name="phone1" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">هاتف 2</label>
                <input name="phone2" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">المنطقة</label>
                <input name="area" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm text-zinc-400">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-zinc-600"> نشط
                </label>
            </div>
            <div class="flex items-end">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white hover:bg-teal-500">حفظ</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">الاسم</th>
                    <th class="px-4 py-3 text-center">هاتف 1</th>
                    <th class="px-4 py-3 text-center">هاتف 2</th>
                    <th class="px-4 py-3">المنطقة</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($suppliers as $s)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3 text-white">{{ $s->name }}</td>
                        <td class="px-4 py-3 text-zinc-400">
                            @php
                                $digitsPhone1 = preg_replace('/\D+/', '', (string) ($s->phone1 ?? ''));
                                $formattedPhone1 = strlen($digitsPhone1) === 11
                                    ? substr($digitsPhone1, 0, 4).' '.substr($digitsPhone1, 4, 3).' '.substr($digitsPhone1, 7)
                                    : ($s->phone1 ?: '—');
                            @endphp
                            <div class="flex w-full justify-center">
                                <span class="inline-block text-center font-mono tracking-wide" dir="ltr">{{ $formattedPhone1 }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-400">
                            @php
                                $digitsPhone2 = preg_replace('/\D+/', '', (string) ($s->phone2 ?? ''));
                                $formattedPhone2 = strlen($digitsPhone2) === 11
                                    ? substr($digitsPhone2, 0, 4).' '.substr($digitsPhone2, 4, 3).' '.substr($digitsPhone2, 7)
                                    : ($s->phone2 ?: '—');
                            @endphp
                            <div class="flex w-full justify-center">
                                <span class="inline-block text-center font-mono tracking-wide" dir="ltr">{{ $formattedPhone2 }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-zinc-400">{{ $s->area ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <a href="{{ route('dashboard.suppliers.edit', $s) }}" class="text-xs text-teal-400 hover:underline">تعديل</a>
                            <form method="POST" action="{{ route('dashboard.suppliers.destroy', $s) }}" class="inline ms-2" onsubmit="return confirm('حذف المورد؟');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-400 hover:underline">حذف</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">لا يوجد موردون.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
