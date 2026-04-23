<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — Med RANKO</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Tajawal:wght@400;500;600;700&display=swap" rel="stylesheet">
    @include('partials.vite-assets')
    <style>
        :root {
            --brand: #8B1538;
            --brand-hover: #a61e45;
            --brand-muted: rgba(139, 21, 56, 0.15);
            --surface: #0a090b;
            --surface-raised: #121014;
            --surface-card: #18161c;
            --border-subtle: rgba(255, 255, 255, 0.06);
        }

        body {
            font-family: 'Tajawal', system-ui, sans-serif;
        }

        details summary::-webkit-details-marker {
            display: none;
        }

        .discount-pill {
            transition: transform 0.16s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }

        .discount-pill:hover {
            transform: translateY(-1px);
        }

        .discount-pill[data-discount-tier="zero"]:hover {
            transform: none;
        }

        .discount-pill[data-discount-tier="max"] {
            animation: discount-pill-soft-pulse 3.5s ease-in-out infinite;
        }

        @keyframes discount-pill-soft-pulse {

            0%,
            100% {
                box-shadow: inset 0 1px 0 rgba(251, 191, 36, 0.2), 0 0 18px -10px rgba(245, 158, 11, 0.4);
            }

            50% {
                box-shadow: inset 0 1px 0 rgba(251, 191, 36, 0.28), 0 0 26px -8px rgba(245, 158, 11, 0.55);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            .discount-pill[data-discount-tier="max"] {
                animation: none;
            }

            .discount-pill {
                transition: none;
            }
        }
    </style>
</head>

<body class="min-h-screen antialiased text-zinc-100" style="background: var(--surface);">
    @php
        $logoUrl = asset('images/brand/med-ranko-logo.jpeg');
        $nav = [
            ['route' => 'dashboard', 'label' => 'الرئيسية', 'match' => 'dashboard'],
            ['route' => 'dashboard.analytics', 'label' => 'التحليلات', 'match' => 'dashboard.analytics'],
            ['route' => 'dashboard.products', 'label' => 'المنتجات', 'match' => 'dashboard.products'],
            ['route' => 'dashboard.suppliers', 'label' => 'الموردون', 'match' => 'dashboard.suppliers'],
            ['route' => 'dashboard.users', 'label' => 'المستخدمون', 'match' => 'dashboard.users'],
            [
                'route' => 'dashboard.activation-codes',
                'label' => 'أكواد التفعيل',
                'match' => 'dashboard.activation-codes*',
            ],
            ['route' => 'dashboard.uploads', 'label' => 'الرفوعات', 'match' => 'dashboard.uploads'],
            ['route' => 'dashboard.settings', 'label' => 'الإعدادات', 'match' => 'dashboard.settings'],
        ];
    @endphp

    {{-- شريط علوي — الجوال --}}
    <header
        class="sticky top-0 z-30 flex items-center justify-between gap-3 border-b px-4 py-3 backdrop-blur-md lg:hidden"
        style="background: rgba(10,9,11,0.92); border-color: var(--border-subtle);">
        <a href="{{ route('dashboard') }}" class="flex shrink-0 items-center overflow-hidden">
            <img src="{{ $logoUrl }}" alt="Med RANKO" class="object-contain" width="96" height="40"
                style="max-width:96px;max-height:40px;width:auto;height:auto;object-fit:contain;">
        </a>
        <details class="relative">
            <summary
                class="flex cursor-pointer list-none items-center gap-2 rounded-xl border px-3 py-2 text-sm font-medium text-zinc-200 transition hover:bg-white/5"
                style="border-color: var(--border-subtle);">
                <svg class="h-5 w-5 text-[var(--brand)]" fill="none" stroke="currentColor" stroke-width="2"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                </svg>
                القائمة
            </summary>
            <nav class="absolute end-0 top-full z-50 mt-2 w-64 min-w-[14rem] overflow-hidden rounded-2xl border py-2 shadow-2xl"
                style="background: var(--surface-card); border-color: var(--border-subtle);">
                @foreach ($nav as $item)
                    @php $na = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                        class="mx-2 flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $na ? 'text-rose-100 shadow-[inset_0_0_0_1px_rgba(139,21,56,0.35)]' : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-200' }}"
                        @if ($na) style="background: linear-gradient(135deg, var(--brand-muted), transparent);" @endif>{{ $item['label'] }}</a>
                @endforeach
                <div class="mx-2 my-2 border-t" style="border-color: var(--border-subtle);"></div>
                <form method="POST" action="{{ route('logout') }}" class="px-2">
                    @csrf
                    <button type="submit"
                        class="w-full rounded-xl border px-3 py-2.5 text-sm text-zinc-400 transition hover:bg-white/5 hover:text-zinc-200"
                        style="border-color: var(--border-subtle);">تسجيل الخروج</button>
                </form>
            </nav>
        </details>
    </header>

    <div class="flex min-h-0 min-h-[calc(100dvh-3.75rem)] lg:min-h-screen">
        <aside class="hidden w-[17rem] shrink-0 flex-col overflow-x-hidden border-e px-5 py-8 lg:flex"
            style="background: var(--surface-raised); border-color: var(--border-subtle);">
            <a href="{{ route('dashboard') }}"
                class="mb-6 flex justify-center overflow-hidden rounded-xl border bg-white p-2 shadow-lg transition hover:shadow-xl"
                style="border-color: var(--border-subtle);">
                <img src="{{ $logoUrl }}" alt="Med RANKO — رتّب صح ووفر أكتر" class="object-contain" width="100"
                    height="42" style="max-width:100px;max-height:42px;width:auto;height:auto;object-fit:contain;">
            </a>
            <nav class="flex flex-1 flex-col gap-0.5 text-sm">
                @foreach ($nav as $item)
                    @php $da = request()->routeIs($item['match']); @endphp
                    <a href="{{ route($item['route']) }}"
                        class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ $da ? 'text-rose-100 shadow-[inset_0_0_0_1px_rgba(139,21,56,0.35)]' : 'text-zinc-400 hover:bg-white/5 hover:text-zinc-200' }}"
                        @if ($da) style="background: linear-gradient(135deg, var(--brand-muted), transparent);" @endif>{{ $item['label'] }}</a>
                @endforeach
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit"
                    class="w-full rounded-xl border px-3 py-2.5 text-sm text-zinc-400 transition hover:border-[var(--brand)]/40 hover:bg-[var(--brand-muted)] hover:text-rose-100"
                    style="border-color: var(--border-subtle);">تسجيل الخروج</button>
            </form>
        </aside>

        <div class="flex min-w-0 flex-1 flex-col">
            <header class="hidden items-center justify-between gap-4 border-b px-6 py-5 lg:flex"
                style="border-color: var(--border-subtle); background: linear-gradient(180deg, rgba(24,22,28,0.6) 0%, transparent 100%);">
                <div>
                    <h2 class="text-lg font-semibold tracking-tight text-white">@yield('heading')</h2>
                    @hasSection('subheading')
                        <p class="mt-0.5 text-sm text-zinc-500">@yield('subheading')</p>
                    @endif
                </div>
                <div class="flex items-center gap-3 rounded-2xl border px-4 py-2 text-sm"
                    style="border-color: var(--border-subtle); background: var(--surface-card);">
                    <span class="text-zinc-500">مرحباً،</span>
                    <span class="font-medium text-zinc-200">{{ auth()->user()->name ?? '' }}</span>
                </div>
            </header>

            {{-- عنوان الصفحة على الجوال تحت الشريط --}}
            <div class="border-b px-4 py-4 lg:hidden" style="border-color: var(--border-subtle);">
                <h2 class="text-base font-semibold text-white">@yield('heading')</h2>
                @hasSection('subheading')
                    <p class="mt-0.5 text-xs text-zinc-500">@yield('subheading')</p>
                @endif
            </div>

            <main class="flex-1 p-4 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-2xl border px-4 py-3 text-sm text-rose-100"
                        style="border-color: rgba(139,21,56,0.4); background: var(--brand-muted);">
                        {{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-2xl border border-red-500/30 bg-red-950/40 px-4 py-3 text-sm text-red-200">
                        <ul class="list-inside list-disc space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
</body>

</html>
