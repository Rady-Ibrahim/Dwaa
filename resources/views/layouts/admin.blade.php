<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة التحكم') — Dwaa</title>
    @include('partials.vite-assets')
</head>
<body class="min-h-screen bg-zinc-950 text-zinc-100 antialiased">
    <div class="flex min-h-screen">
        <aside class="hidden w-64 shrink-0 flex-col border-l border-zinc-800 bg-zinc-900/80 p-6 lg:flex">
            <div class="mb-10">
                <p class="text-xs font-medium uppercase tracking-widest text-teal-400/90">Dwaa</p>
                <h1 class="mt-1 text-lg font-semibold text-white">منصة الأسعار</h1>
            </div>
            <nav class="flex flex-1 flex-col gap-1 text-sm">
                <a href="{{ route('dashboard') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">الرئيسية</a>
                <a href="{{ route('dashboard.analytics') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.analytics') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">التقارير</a>
                <a href="{{ route('dashboard.products') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.products') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">المنتجات</a>
                <a href="{{ route('dashboard.suppliers') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.suppliers') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">الموردون</a>
                <a href="{{ route('dashboard.users') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.users') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">المستخدمون</a>
                <a href="{{ route('dashboard.activation-codes') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.activation-codes*') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">أكواد التفعيل</a>
                <a href="{{ route('dashboard.uploads') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.uploads') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">الرفوعات</a>
                <a href="{{ route('dashboard.mapping') }}" class="rounded-lg px-3 py-2 hover:bg-zinc-800 {{ request()->routeIs('dashboard.mapping') ? 'bg-zinc-800 text-white' : 'text-zinc-400' }}">غير المطابق</a>
            </nav>
            <form method="POST" action="{{ route('logout') }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full rounded-lg border border-zinc-700 px-3 py-2 text-sm text-zinc-300 hover:bg-zinc-800">تسجيل الخروج</button>
            </form>
        </aside>
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex items-center justify-between border-b border-zinc-800 bg-zinc-900/60 px-4 py-4 backdrop-blur lg:px-8">
                <div class="flex items-center gap-3 lg:hidden">
                    <span class="text-sm font-semibold text-white">Dwaa</span>
                </div>
                <div class="flex-1 text-center lg:text-right">
                    <h2 class="text-base font-semibold text-white">@yield('heading')</h2>
                    @hasSection('subheading')
                        <p class="text-xs text-zinc-500">@yield('subheading')</p>
                    @endif
                </div>
                <div class="text-left text-xs text-zinc-500">
                    {{ auth()->user()->name ?? '' }}
                </div>
            </header>
            <main class="flex-1 p-4 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-lg border border-teal-500/30 bg-teal-950/40 px-4 py-3 text-sm text-teal-200">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-4 rounded-lg border border-red-500/40 bg-red-950/40 px-4 py-3 text-sm text-red-200">
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
