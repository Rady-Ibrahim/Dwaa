@extends('layouts.admin')

@section('title', 'تغيير كلمة المرور')
@section('heading', 'تغيير كلمة المرور')
@section('subheading', 'تحديث كلمة مرور المستخدم: '.$user->name)

@section('content')
    <div class="mx-auto max-w-2xl rounded-2xl border border-zinc-800 bg-zinc-900/40 p-6">
        <div class="mb-6 rounded-xl border border-zinc-800 bg-zinc-950/60 p-4 text-sm text-zinc-300">
            <p><span class="text-zinc-500">الاسم:</span> {{ $user->name }}</p>
            <p class="mt-1" dir="ltr"><span class="text-zinc-500">الهاتف:</span> {{ $user->phone }}</p>
        </div>

        <form method="POST" action="{{ route('dashboard.users.password.update', $user) }}" class="space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="mb-1 block text-xs text-zinc-500">كلمة المرور الجديدة</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>

            <div>
                <label class="mb-1 block text-xs text-zinc-500">تأكيد كلمة المرور</label>
                <input type="password" name="password_confirmation" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500">حفظ كلمة المرور</button>
                <a href="{{ route('dashboard.users') }}" class="rounded-lg border border-zinc-700 px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800">رجوع</a>
            </div>
        </form>
    </div>
@endsection
