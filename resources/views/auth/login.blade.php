<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — Med RANKO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    <style>
        body { font-family: 'Tajawal', system-ui, sans-serif; }
        :root { --brand: #8B1538; --brand-hover: #a61e45; }
    </style>
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-12 text-zinc-100">
    <div class="pointer-events-none absolute inset-0 bg-[#0a090b]"></div>
    <div class="pointer-events-none absolute -start-[20%] top-0 h-[480px] w-[480px] rounded-full bg-[#8B1538]/20 blur-[100px]"></div>
    <div class="pointer-events-none absolute -end-[10%] bottom-0 h-[360px] w-[360px] rounded-full bg-violet-950/40 blur-[90px]"></div>

    <div class="relative w-full max-w-md">
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex justify-center overflow-hidden rounded-2xl border border-white/10 bg-white p-3 shadow-2xl shadow-black/40">
                <img src="{{ asset('images/brand/med-ranko-logo.jpeg') }}" alt="Med RANKO" class="object-contain" width="120" height="48" style="max-width:120px;max-height:48px;width:auto;height:auto;object-fit:contain;">
            </div>
            <p class="text-sm font-medium text-rose-200/90">رتّب صح .. ووفر أكتر</p>
            <h1 class="mt-3 text-xl font-semibold text-white">تسجيل دخول المسؤول</h1>
            <p class="mt-1 text-sm text-zinc-500">لوحة التحكم — منصة الأسعار</p>
        </div>

        <div class="rounded-3xl border border-white/[0.08] bg-[#121014]/90 p-8 shadow-2xl backdrop-blur-sm">
            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-400">الهاتف</label>
                    <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-xl border border-white/10 bg-[#0a090b] px-4 py-3 text-sm text-white placeholder-zinc-600 outline-none transition focus:border-[var(--brand)] focus:ring-2 focus:ring-[#8B1538]/30" autocomplete="username" placeholder="01xxxxxxxxx">
                </div>
                <div>
                    <label class="mb-1.5 block text-sm font-medium text-zinc-400">كلمة المرور</label>
                    <input type="password" name="password" required class="w-full rounded-xl border border-white/10 bg-[#0a090b] px-4 py-3 text-sm text-white outline-none transition focus:border-[var(--brand)] focus:ring-2 focus:ring-[#8B1538]/30" autocomplete="current-password">
                </div>
                <label class="flex cursor-pointer items-center gap-2 text-sm text-zinc-400">
                    <input type="checkbox" name="remember" value="1" class="rounded border-zinc-600 text-[var(--brand)] focus:ring-[#8B1538]/40">
                    تذكرني
                </label>
                <button type="submit" class="w-full rounded-xl py-3.5 text-sm font-semibold text-white shadow-lg transition hover:opacity-95 active:scale-[0.99]" style="background: linear-gradient(135deg, var(--brand-hover), var(--brand)); box-shadow: 0 12px 40px -12px rgba(139, 21, 56, 0.65);">
                    دخول
                </button>
            </form>
        </div>
        <p class="mt-8 text-center text-xs text-zinc-600">© Med RANKO</p>
    </div>
</body>
</html>
