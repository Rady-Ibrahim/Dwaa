@extends('layouts.admin')

@section('title', 'غير المطابق')
@section('heading', 'ربط الأصناف غير المطابقة')
@section('subheading', 'اختر منتجاً موحداً أو أنشئ منتجاً جديداً — يُحفظ كـ alias للمرات القادمة')

@section('content')
    <form method="GET" action="{{ route('dashboard.mapping') }}" class="mb-6 flex flex-wrap items-end gap-3">
        <div>
            <label class="mb-1 block text-xs text-zinc-500">تصفية برفع</label>
            <select name="upload_id" class="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" onchange="this.form.submit()">
                <option value="">كل الرفوعات</option>
                @foreach ($uploads as $up)
                    <option value="{{ $up->id }}" @selected(request('upload_id') == $up->id)>#{{ $up->id }} — {{ $up->supplier?->name }} ({{ $up->status }})</option>
                @endforeach
            </select>
        </div>
    </form>

    <div class="space-y-6">
        @forelse ($items as $item)
            <div class="rounded-xl border border-zinc-800 bg-zinc-900/40 p-5">
                <div class="mb-3 flex flex-wrap items-start justify-between gap-2">
                    <div>
                        <p class="font-mono text-sm text-white">{{ $item->raw_name }}</p>
                        <p class="text-xs text-zinc-500">رفع #{{ $item->upload_id }} — {{ $item->upload?->supplier?->name }}</p>
                    </div>
                    <form method="POST" action="{{ route('dashboard.mapping.ignore', $item) }}" onsubmit="return confirm('تجاهل هذا الصنف؟');">
                        @csrf
                        <button type="submit" class="text-xs text-zinc-500 hover:text-red-400">تجاهل</button>
                    </form>
                </div>

                <div class="mb-3 flex flex-wrap gap-2">
                    <input type="text" id="q-{{ $item->id }}" placeholder="بحث باسم أو كود..." class="flex-1 min-w-[200px] rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <button type="button" onclick="searchProducts({{ $item->id }})" class="rounded-lg bg-zinc-700 px-3 py-2 text-sm text-white hover:bg-zinc-600">بحث</button>
                </div>
                <div id="res-{{ $item->id }}" class="mb-3 flex flex-wrap gap-2 text-xs"></div>

                <form method="POST" action="{{ route('dashboard.mapping.link', $item) }}" class="mb-3 flex flex-wrap items-end gap-2">
                    @csrf
                    <div>
                        <label class="mb-1 block text-xs text-zinc-500">معرّف المنتج للربط</label>
                        <input type="number" name="product_id" id="pid-{{ $item->id }}" required class="w-32 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr" placeholder="ID">
                    </div>
                    <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">ربط بمنتج موجود</button>
                </form>

                <details class="text-sm">
                    <summary class="cursor-pointer text-zinc-400 hover:text-white">منتج جديد +</summary>
                    <form method="POST" action="{{ route('dashboard.mapping.create', $item) }}" class="mt-3 grid gap-3 sm:grid-cols-2">
                        @csrf
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">اسم عربي</label>
                            <input name="name_ar" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                        </div>
                        <div>
                            <label class="mb-1 block text-xs text-zinc-500">اسم إنجليزي (اختياري)</label>
                            <input name="name_en" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="mb-1 block text-xs text-zinc-500">كود المنتج (فريد)</label>
                            <input name="code" required class="w-full max-w-xs rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="rounded-lg border border-teal-600 px-4 py-2 text-sm text-teal-400">إنشاء وربط</button>
                        </div>
                    </form>
                </details>
            </div>
        @empty
            <p class="text-center text-zinc-500">لا توجد عناصر معلقة.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $items->links() }}</div>

    <script>
        async function searchProducts(itemId) {
            const q = document.getElementById('q-' + itemId).value.trim();
            if (q.length < 2) { alert('أدخل حرفين على الأقل'); return; }
            const box = document.getElementById('res-' + itemId);
            box.textContent = 'جاري البحث...';
            try {
                const r = await fetch('{{ url('/dashboard/mapping/products/search') }}?q=' + encodeURIComponent(q), {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                });
                const data = await r.json();
                box.innerHTML = '';
                if (!data.length) {
                    box.textContent = 'لا نتائج';
                    return;
                }
                data.forEach(p => {
                    const b = document.createElement('button');
                    b.type = 'button';
                    b.className = 'rounded border border-zinc-600 px-2 py-1 text-zinc-300 hover:bg-zinc-800';
                    b.textContent = '#' + p.id + ' — ' + (p.name_ar || p.name_en || '') + ' (' + (p.code || '') + ')';
                    b.onclick = () => {
                        document.getElementById('pid-' + itemId).value = p.id;
                    };
                    box.appendChild(b);
                });
            } catch (e) {
                box.textContent = 'خطأ في البحث';
            }
        }
    </script>
@endsection
