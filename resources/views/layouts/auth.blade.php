<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'تسجيل الدخول — MedRANKO')</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @include('partials.vite-assets')
    @stack('styles')
    <style>
        .bg-medical-dark {
            background-image: url("{{ asset('images/image.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
        }
        body {
            font-family: 'Tajawal', 'Instrument Sans', system-ui, sans-serif;
        }
        ::selection {
            background: rgba(225, 29, 72, 0.2);
        }
    </style>
</head>
<body class="bg-medical-dark relative flex min-h-screen items-center justify-center overflow-hidden p-4 text-slate-200 antialiased lg:justify-start lg:pr-28">

    <main class="relative z-10 my-auto w-full max-w-md">
        <div class="rounded-3xl border border-slate-800/80 bg-[#0c101c]/80 p-8 shadow-2xl shadow-rose-950/30 backdrop-blur-xl sm:p-10">
            <div class="mb-8 text-center">
                <div class="mx-auto mb-4 flex h-20 w-20 items-center justify-center rounded-full border border-rose-600/30 bg-slate-900/90 text-rose-500 shadow-lg shadow-rose-950/50">
                    <i class="fa-solid fa-user text-2xl"></i>
                </div>
                <h2 class="text-2xl font-bold tracking-wide text-white">@yield('auth_title', 'تسجيل الدخول')</h2>
                <div class="mx-auto mt-2 h-1 w-10 rounded-full bg-rose-600"></div>
            </div>

            @yield('auth_form')

            @if (empty($hideAuthFooter))
                <div class="my-6 flex items-center gap-3">
                    <span class="h-px flex-1 bg-slate-800"></span>
                    <span class="text-sm font-medium text-slate-500">أو</span>
                    <span class="h-px flex-1 bg-slate-800"></span>
                </div>

                <a href="{{ route('client.password') }}" class="inline-flex w-full items-center justify-center gap-2 text-sm font-semibold text-rose-500 transition hover:text-rose-400 hover:underline">
                    <i class="fa-solid fa-lock text-sm"></i>
                    نسيت كلمة المرور؟
                </a>
            @endif
        </div>

        <p class="mt-6 text-center text-xs font-medium text-slate-500">© 2024 MedRANKO جميع الحقوق محفوظة</p>
    </main>

    @stack('scripts')
</body>
</html>
