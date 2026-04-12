@extends('layouts.admin')

@section('title', 'أكواد التفعيل')
@section('heading', 'أكواد الاشتراك')

@section('content')
    <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <h3 class="mb-4 text-sm font-semibold text-white">إنشاء كود</h3>
        <form method="POST" action="{{ route('dashboard.activation-codes.store') }}" class="flex flex-wrap items-end gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-xs text-zinc-500">مدة (أيام)</label>
                <input type="number" name="duration_days" value="{{ old('duration_days', 30) }}" min="1" required class="w-32 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">أقصى استخدامات</label>
                <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="1" required class="w-32 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">ينتهي الكود في (اختياري)</label>
                <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" class="rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">كود مخصص (اختياري)</label>
                <input name="code" value="{{ old('code') }}" placeholder="فارغ = توليد تلقائي" class="w-48 rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
            </div>
            <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm text-white">إنشاء</button>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">الكود</th>
                    <th class="px-4 py-3">الأيام</th>
                    <th class="px-4 py-3">استخدام</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($codes as $c)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3 font-mono text-teal-300" dir="ltr">{{ $c->code }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $c->duration_days }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $c->used_count }} / {{ $c->max_uses }}</td>
                        <td class="px-4 py-3">
                            @if ($c->is_active)
                                <span class="text-emerald-400">نشط</span>
                            @else
                                <span class="text-zinc-500">موقوف</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <form method="POST" action="{{ route('dashboard.activation-codes.update', $c) }}" class="inline">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $c->is_active ? '0' : '1' }}">
                                <button type="submit" class="text-xs text-teal-400 hover:underline">{{ $c->is_active ? 'تعطيل' : 'تفعيل' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">لا توجد أكواد بعد.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $codes->links() }}</div>
@endsection
