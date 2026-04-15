@extends('layouts.admin')

@section('title', 'الرفوعات')
@section('heading', 'رفع ملف مورد')

@section('content')
    <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
   
        <form method="POST" action="{{ route('dashboard.uploads.store') }}" enctype="multipart/form-data" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="mb-1 block text-xs text-zinc-500">المورد</label>
                <select name="supplier_id" required class="w-full max-w-md rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <option value="">— اختر —</option>
                    @foreach ($suppliers as $s)
                        <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <label class="mb-1 block text-xs text-zinc-500">الملف</label>
            
                <div class="flex items-center gap-2">
                    <!-- أيقونة صغيرة -->
                    <svg xmlns="http://www.w3.org/2000/svg"
                         class="w-4 h-4 text-zinc-400"
                         fill="none"
                         viewBox="0 0 24 24"
                         stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1M12 12v-8m0 0L8 8m4-4l4 4"/>
                    </svg>
            
                    <!-- input -->
                    <input type="file"
                           name="file"
                           accept=".xlsx,.xls,.csv"
                           required
                           class="text-sm text-zinc-400">
                </div>
            </div>

            <div class="sm:col-span-2 lg:col-span-3">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500">رفع وبدء المعالجة</button>
            </div>
        </form>
    </div>

    <h3 class="mb-3 text-sm font-semibold text-white">سجل الرفوعات</h3>
    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">المورد</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">صفوف</th>
                    <th class="px-4 py-3">صفوف بعروض</th>
                    <th class="px-4 py-3">غير مطابق</th>
                    <th class="px-4 py-3">التاريخ</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($uploads as $up)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3 text-zinc-500">{{ $up->id }}</td>
                        <td class="px-4 py-3 text-white">{{ $up->supplier->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-zinc-800 px-2 py-0.5 text-xs">{{ $up->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-zinc-400">{{ $up->total_rows }}</td>
                        <td class="px-4 py-3 text-emerald-400">{{ $up->matched_count }}</td>
                        <td class="px-4 py-3 text-amber-400">{{ $up->unmatched_count }}</td>
                        <td class="px-4 py-3 text-xs text-zinc-500">{{ $up->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-zinc-500">لا توجد رفوعات بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $uploads->links() }}</div>
@endsection
