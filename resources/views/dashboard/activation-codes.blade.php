@extends('layouts.admin')

@section('title', 'إدارة أكواد التفعيل')
@section('heading', 'أكواد الاشتراك')

@section('content')
<div class="space-y-6">
    <div class="overflow-hidden rounded-2xl border border-zinc-800 bg-zinc-900/50 shadow-sm backdrop-blur-sm">
        <div class="border-b border-zinc-800 bg-zinc-800/30 px-6 py-4">
            <h3 class="flex items-center gap-2 text-sm font-bold text-white">
                <span class="flex h-6 w-6 items-center justify-center rounded-md bg-teal-500/20 text-teal-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                </span>
                توليد كود اشتراك جديد
            </h3>
        </div>
        
        <form method="POST" action="{{ route('dashboard.activation-codes.store') }}" class="p-6">
            @csrf
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                <div class="space-y-1">
                    <label class="text-xs font-medium text-zinc-400">مدة الصلاحية (أيام)</label>
                    <input type="number" name="duration_days" value="{{ old('duration_days', 30) }}" min="1" required 
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-2.5 text-sm text-white transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-medium text-zinc-400">عدد مرات الاستخدام</label>
                    <input type="number" name="max_uses" value="{{ old('max_uses', 1) }}" min="1" required 
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-2.5 text-sm text-white transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-medium text-zinc-400">تاريخ انتهاء الصلاحية</label>
                    <input type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" 
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-2.5 text-sm text-white transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none">
                </div>

                <div class="space-y-1">
                    <label class="text-xs font-medium text-zinc-400">كود مخصص <span class="text-[10px] text-zinc-600">(اختياري)</span></label>
                    <input name="code" value="{{ old('code') }}" placeholder="أتركه فارغاً للتوليد التلقائي" 
                        class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-4 py-2.5 text-sm text-white transition focus:border-teal-500 focus:ring-2 focus:ring-teal-500/20 outline-none" dir="ltr">
                </div>
            </div>

            <div class="mt-6 flex justify-end border-t border-zinc-800 pt-5">
                <button type="submit" class="flex items-center gap-2 rounded-xl bg-teal-600 px-6 py-2.5 text-sm font-bold text-white transition hover:bg-teal-500 active:scale-95 shadow-lg shadow-teal-900/20">
                    إنشاء الكود الآن
                </button>
            </div>
        </form>
    </div>

    <div class="rounded-2xl border border-zinc-800 bg-zinc-950 shadow-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-right text-sm">
                <thead>
                    <tr class="bg-zinc-900/50 text-zinc-400 border-b border-zinc-800">
                        <th class="px-6 py-4 font-semibold">الكود</th>
                        <th class="px-6 py-4 font-semibold text-center">المدة</th>
                        <th class="px-6 py-4 font-semibold text-center">الاستخدام</th>
                        <th class="px-6 py-4 font-semibold text-center">تاريخ الانتهاء</th>
                        <th class="px-6 py-4 font-semibold text-center">الحالة</th>
                        <th class="px-6 py-4 font-semibold">الإجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @forelse ($codes as $c)
                    <tr class="group transition hover:bg-zinc-900/30">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <span class="rounded-lg bg-zinc-800 p-2 text-teal-400 group-hover:bg-teal-500/10 transition">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                    </svg>
                                </span>
                                <span class="font-mono font-bold tracking-wider text-zinc-200" dir="ltr">{{ $c->code }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center rounded-full bg-zinc-800 px-2.5 py-0.5 text-xs font-medium text-zinc-300">
                                {{ $c->duration_days }} يوم
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex flex-col items-center gap-1">
                                <span class="text-xs text-zinc-400">{{ $c->used_count }} من {{ $c->max_uses }}</span>
                                <div class="h-1.5 w-20 overflow-hidden rounded-full bg-zinc-800">
                                    @php $percent = ($c->used_count / $c->max_uses) * 100; @endphp
                                    <div class="h-full {{ $percent >= 100 ? 'bg-red-500' : 'bg-teal-500' }}" style="width: {{ min($percent, 100) }}%"></div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center text-xs text-zinc-500">
                            {{ $c->expires_at ? $c->expires_at->format('Y/m/d') : 'لا يوجد' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if ($c->is_active)
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                    نشط
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-zinc-500/10 px-3 py-1 text-xs font-bold text-zinc-500">
                                    <span class="h-1.5 w-1.5 rounded-full bg-zinc-500"></span>
                                    موقوف
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <form method="POST" action="{{ route('dashboard.activation-codes.update', $c) }}">
                                @csrf
                                @method('PUT')
                                <input type="hidden" name="is_active" value="{{ $c->is_active ? '0' : '1' }}">
                                <button type="submit" 
                                    class="rounded-lg border {{ $c->is_active ? 'border-red-500/50 text-red-500 hover:bg-red-500/10' : 'border-emerald-500/50 text-emerald-500 hover:bg-emerald-500/10' }} px-4 py-1.5 text-xs font-bold transition">
                                    {{ $c->is_active ? 'إيقاف الآن' : 'تفعيل الكود' }}
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-zinc-500">
                            <div class="flex flex-col items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-zinc-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                                </svg>
                                <span>لا توجد أكواد اشتراك حالياً</span>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4 custom-pagination">
        {{ $codes->links() }}
    </div>
</div>
@endsection