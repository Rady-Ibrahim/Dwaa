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
    @include('partials.vite-assets')
    @stack('styles')
    <style>
        body {
            font-family: 'Tajawal', 'Instrument Sans', system-ui, sans-serif;
            background-image: url("{{ asset('images/image.png') }}");
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
        }
        ::selection {
            background: rgba(37, 99, 235, 0.15);
        }
    </style>
</head>
<body class="flex min-h-screen items-center justify-center p-6 text-slate-800 antialiased">

    <main class="mx-auto grid w-full max-w-6xl grid-cols-1 items-center gap-12 md:grid-cols-2">

        {{-- اليمين في RTL: بطاقة تسجيل الدخول --}}
        <section class="order-1 w-full">
            <div class="rounded-2xl bg-white p-8 shadow-xl md:p-10">
                <div class="mb-8 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-blue-700 shadow-lg shadow-blue-500/30 ring-4 ring-blue-50">
                        <svg class="h-8 w-8 text-white" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M12 12a4.5 4.5 0 1 0-4.5-4.5A4.5 4.5 0 0 0 12 12Zm0 2.25c-3.42 0-7.13 1.9-7.13 4.93V20.5h14.26v-1.32c0-3.03-3.71-4.93-7.13-4.93Z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-slate-800">@yield('auth_title', 'تسجيل الدخول')</h2>
                </div>

                @yield('auth_form')

                @if (empty($hideAuthFooter))
                    <div class="my-6 flex items-center gap-3">
                        <span class="h-px flex-1 bg-slate-200"></span>
                        <span class="text-sm font-medium text-slate-400">أو</span>
                        <span class="h-px flex-1 bg-slate-200"></span>
                    </div>

                    <a href="{{ route('client.password') }}" class="inline-flex w-full items-center justify-center gap-2 text-sm font-semibold text-blue-700 transition hover:text-blue-900 hover:underline">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>
                        </svg>
                        نسيت كلمة المرور؟
                    </a>
                @endif
            </div>

            <p class="mt-7 text-center text-xs text-slate-400">© 2024 MedRANKO جميع الحقوق محفوظة</p>
        </section>

        {{-- اليسار في RTL: قسم الهوية والمميزات --}}
        <aside class="order-2 w-full">
            @include('partials.auth-brand')
        </aside>

    </main>

    @stack('scripts')
</body>
</html>