@extends('layouts.admin')

@section('title', 'الرئيسية')
@section('heading', 'لوحة التحكم')
@section('subheading', 'نظرة سريعة على النظام')

@section('content')
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
            <p class="text-xs text-zinc-500">عملاء</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['users'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
            <p class="text-xs text-zinc-500">موردون</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['suppliers'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
            <p class="text-xs text-zinc-500">غير مطابق (معلق)</p>
            <p class="mt-2 text-3xl font-semibold text-amber-400">{{ $stats['pending_unmatched'] }}</p>
        </div>
        <div class="rounded-xl border border-zinc-800 bg-zinc-900/50 p-5">
            <p class="text-xs text-zinc-500">رفوعات اليوم</p>
            <p class="mt-2 text-3xl font-semibold text-white">{{ $stats['uploads_today'] }}</p>
        </div>
    </div>
    <p class="mt-8 text-sm text-zinc-500">استخدم الـ API من التطبيق مع Bearer token؛ إدارة البيانات التفصيلية متاحة عبر نفس الـ endpoints أو عبر تطوير الواجهة لاحقاً.</p>
@endsection
