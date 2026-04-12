<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دخول المسؤول — Dwaa</title>
    @include('partials.vite-assets')
</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-950 px-4 text-zinc-100">
    <div class="w-full max-w-md rounded-2xl border border-zinc-800 bg-zinc-900/80 p-8 shadow-xl">
        <h1 class="text-xl font-semibold text-white">تسجيل دخول المسؤول</h1>
        <p class="mt-1 text-sm text-zinc-500">لوحة التحكم الداخلية فقط</p>
        <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm text-zinc-400">الهاتف</label>
                <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white focus:border-teal-500 focus:outline-none" autocomplete="username">
            </div>
            <div>
                <label class="mb-1 block text-sm text-zinc-400">كلمة المرور</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm text-white focus:border-teal-500 focus:outline-none" autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-400">
                <input type="checkbox" name="remember" value="1" class="rounded border-zinc-600">
                تذكرني
            </label>
            <button type="submit" class="w-full rounded-lg bg-teal-600 py-2.5 text-sm font-medium text-white hover:bg-teal-500">دخول</button>
        </form>
    </div>
</body>
</html>
