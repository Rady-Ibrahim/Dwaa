<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedRANKO | @yield('title')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Toast Notification Function - Define Early
        window.clientNotify = function(message, type = 'info') {
            const toastWrap = document.getElementById('clientToastWrap');
            if (!toastWrap) return;

            const colors = {
                'success': 'bg-emerald-500/90 text-white',
                'error': 'bg-rose-500/90 text-white',
                'info': 'bg-sky-500/90 text-white',
                'warning': 'bg-amber-500/90 text-white'
            };

            const toast = document.createElement('div');
            toast.className =
                `${colors[type] || colors['info']} px-6 py-3 rounded-lg shadow-lg mb-3 text-sm font-medium backdrop-blur-sm`;
            toast.textContent = message;
            toastWrap.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        };
    </script>
    <style>
        :root {
            color-scheme: dark;
            --client-bg: #041423;
            --client-bg-soft: #071d2f;
            --client-panel: rgba(15, 23, 42, 0.72);
            --client-panel-strong: rgba(7, 17, 30, 0.95);
            --client-border: rgba(148, 163, 184, 0.18);
            --client-text: #e2e8f0;
            --client-text-soft: #94a3b8;
            --client-accent: #38bdf8;
            --client-accent-2: #8b5cf6;
            --client-danger: #f43f5e;
            --client-success: #22c55e;
            --client-warning: #f59e0b;
            --client-topbar-bg: rgba(7, 16, 29, 0.82);
            --client-btn-bg: rgba(15, 23, 42, 0.8);
            --client-btn-border: rgba(148, 163, 184, 0.16);
            --client-btn-color: #e2e8f0;
            --client-sidebar-bg: linear-gradient(180deg, rgba(5, 14, 24, 0.98), rgba(7, 18, 31, 0.96));
            --client-sidebar-shadow: -18px 0 42px rgba(2, 6, 23, 0.45);
            --surface-rgb: 15, 23, 42;
            --border-rgb: 148, 163, 184;
            --overlay-rgb: 255, 255, 255;
            --text-main: #f8fafc;
            --text-soft2: #e2e8f0;
            --text-soft: #cbd5e1;
            --text-muted: #94a3b8;
            --accent-soft: #7dd3fc;
            --accent-pale: #e0f2fe;
            --danger-soft: #fda4af;
            --danger-pale: #fecaca;
            --warn-soft: #fcd34d;
            --success-soft: #86efac;
            --violet-soft: #d8b4fe;
            --option-bg: #0f172a;
            --option-color: #ffffff;
            --brand-red: #ff5d78;
            --brand-blue: #59d0ff;
        }

        html.theme-light {
            color-scheme: light;
            --client-bg: #eef2f7;
            --client-bg-soft: #e2e8f0;
            --client-panel: rgba(255, 255, 255, 0.78);
            --client-panel-strong: rgba(255, 255, 255, 0.97);
            --client-border: rgba(15, 23, 42, 0.14);
            --client-text: #0f172a;
            --client-text-soft: #475569;
            --client-accent: #0284c7;
            --client-accent-2: #7c3aed;
            --client-danger: #e11d48;
            --client-success: #16a34a;
            --client-warning: #d97706;
            --client-topbar-bg: rgba(255, 255, 255, 0.85);
            --client-btn-bg: rgba(255, 255, 255, 0.9);
            --client-btn-border: rgba(15, 23, 42, 0.12);
            --client-btn-color: #0f172a;
            --client-sidebar-bg: linear-gradient(180deg, #ffffff, #f8fafc);
            --client-sidebar-shadow: -18px 0 42px rgba(15, 23, 42, 0.12);
            --surface-rgb: 255, 255, 255;
            --border-rgb: 148, 163, 184;
            --overlay-rgb: 15, 23, 42;
            --text-main: #0f172a;
            --text-soft2: #334155;
            --text-soft: #475569;
            --text-muted: #64748b;
            --accent-soft: #0284c7;
            --accent-pale: #0369a1;
            --danger-soft: #e11d48;
            --danger-pale: #be123c;
            --warn-soft: #d97706;
            --success-soft: #16a34a;
            --violet-soft: #7c3aed;
            --option-bg: #ffffff;
            --option-color: #0f172a;
            --brand-red: #e11d48;
            --brand-blue: #0284c7;
        }

        * {
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body.client-shell {
            min-height: 100vh;
            background:
                radial-gradient(circle at top right, rgba(59, 130, 246, 0.13), transparent 24%),
                radial-gradient(circle at bottom left, rgba(168, 85, 247, 0.12), transparent 18%),
                var(--client-bg);
            color: var(--client-text);
            font-family: 'Inter', 'Noto Sans Arabic', sans-serif;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .client-topbar {
            position: sticky;
            top: 0;
            z-index: 40;
            height: 78px;
            padding: 0 1.5rem 0 1.25rem;
            background: var(--client-topbar-bg);
            border-bottom: 1px solid rgba(148, 163, 184, 0.12);
            backdrop-filter: blur(16px);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .client-topbar-inner {
            width: 100%;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .topbar-left,
        .topbar-right {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            min-width: 220px;
        }

        .topbar-brand {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.7rem;
            flex: 1;
            min-width: 0;
        }

        .brand-badge {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, rgba(244, 63, 94, 0.18), rgba(139, 92, 246, 0.18));
            border: 1px solid rgba(244, 63, 94, 0.35);
            color: var(--danger-pale);
            box-shadow: 0 10px 30px rgba(168, 85, 247, .15);
        }

        .brand-name {
            font-weight: 800;
            font-size: clamp(1.4rem, 2vw, 2.3rem);
            letter-spacing: -0.04em;
            color: var(--text-main);
        }

        .brand-name .brand-red {
            color: var(--brand-red);
        }

        .brand-name .brand-blue {
            color: var(--brand-blue);
        }

        .topbar-menu-btn,
        .topbar-action-btn {
            width: 42px;
            height: 42px;
            border-radius: 14px;
            display: grid;
            place-items: center;
            background: var(--client-btn-bg);
            border: 1px solid var(--client-btn-border);
            color: var(--client-btn-color);
            transition: all 0.2s ease;
        }

        .topbar-menu-btn:hover,
        .topbar-action-btn:hover {
            background: rgba(59, 130, 246, 0.14);
            border-color: rgba(96, 165, 250, 0.36);
            color: var(--accent-soft);
            transform: translateY(-1px);
        }

        .topbar-action-btn.primary {
            background: rgba(59, 130, 246, 0.12);
            border-color: rgba(59, 130, 246, 0.32);
            color: var(--accent-soft);
        }

        .topbar-action-btn.warning {
            background: rgba(244, 63, 94, 0.1);
            border-color: rgba(244, 63, 94, 0.3);
            color: var(--danger-soft);
        }

        .topbar-action-btn.theme-btn {
            background: rgba(148, 163, 184, 0.08);
            border-color: var(--client-btn-border);
            color: var(--text-soft);
        }

        .topbar-action-btn.theme-btn:hover {
            color: var(--warn-soft);
            border-color: rgba(245, 158, 11, 0.4);
        }

        .client-side {
            position: fixed;
            top: 0;
            right: 0;
            width: 290px;
            height: 100vh;
            background: var(--client-sidebar-bg);
            border-right: 1px solid rgba(148, 163, 184, 0.12);
            z-index: 50;
            transform: translateX(105%);
            transition: transform .3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            box-shadow: var(--client-sidebar-shadow);
        }

        @media (max-width: 980px) {
            .client-side {
                transform: translateX(105%);
            }

            .client-side.show {
                transform: translateX(0);
            }
        }

        .client-side.show {
            transform: translateX(0);
        }

        .sidebar-header {
            padding: 1.8rem 1.4rem 1.4rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.08);
        }

        .brand-logo-text {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 56px;
        }

        .brand-logo-text .brand-mini {
            font-weight: 900;
            font-size: 1.6rem;
            letter-spacing: -0.05em;
            color: var(--text-main);
        }

        .brand-logo-text .brand-mini .brand-red {
            color: var(--brand-red);
        }

        .brand-logo-text .brand-mini .brand-blue {
            color: var(--brand-blue);
        }

        .brand-logo-text img {
            max-width: 210px;
            height: auto;
            display: block;
            filter: drop-shadow(0 12px 25px rgba(14, 165, 233, 0.18));
        }

        .client-side-link {
            display: flex;
            align-items: center;
            gap: 0.9rem;
            margin: 0.38rem 0.8rem;
            padding: 0.88rem 1rem;
            border-radius: 14px;
            color: var(--text-soft);
            font-weight: 600;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .client-side-link:hover {
            background: rgba(148, 163, 184, 0.08);
            color: var(--text-main);
        }

        .client-side-link.active {
            background: linear-gradient(90deg, rgba(96, 165, 250, 0.16), rgba(59, 130, 246, 0.04));
            color: var(--accent-pale);
            border: 1px solid rgba(96, 165, 250, 0.25);
            box-shadow: inset 0 0 0 1px rgba(96, 165, 250, 0.08);
        }

        .client-side-link span:first-child {
            width: 1.5rem;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(2, 6, 23, 0.62);
            backdrop-filter: blur(4px);
            z-index: 45;
            display: none;
        }

        .sidebar-overlay.show {
            display: block;
        }

        .client-toast-wrap {
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 9999;
            max-width: 400px;
        }

        .news-ticker {
            position: relative;
            overflow: hidden;
            background: linear-gradient(90deg, #8B1538, #a61e45);
            color: #fff;
            padding: 0.5rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .ticker-content {
            display: flex;
            width: fit-content;
            animation: scroll 20s linear infinite;
        }

        .ticker-content span {
            flex-shrink: 0;
            margin: 0 2rem;
            white-space: nowrap;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .client-page-shell {
            width: min(100%, calc(100vw - 320px));
            max-width: 1220px;
            margin: 0 auto;
            padding: 1.5rem 1rem 2rem;
            position: relative;
            z-index: 1;
        }

        @media (max-width: 980px) {
            .client-page-shell {
                width: min(100%, calc(100vw - 2rem));
                padding-top: 1rem;
            }
        }

        @media (max-width: 640px) {
            .client-topbar {
                padding: 0 0.75rem;
            }

            .client-topbar-inner {
                gap: 0.5rem;
            }

            .topbar-left,
            .topbar-right {
                min-width: 0;
                gap: 0.5rem;
            }

            .topbar-menu-btn,
            .topbar-action-btn {
                width: 38px;
                height: 38px;
            }

            .brand-name {
                font-size: 1.3rem;
            }

            .topbar-left {
                flex: 0 0 auto;
            }

            .topbar-right {
                flex: 0 0 auto;
            }
        }
    </style>
    @stack('styles')
</head>

<body class="client-shell @yield('body_class')">
    @if (isset($tickerEnabled) && $tickerEnabled)
        <div class="news-ticker" aria-label="إعلانات">
            <div class="ticker-content" style="animation-duration: {{ $tickerSpeed }}s;">
                @if (isset($tickerAdvertisements) && $tickerAdvertisements->isNotEmpty())
                    @foreach ($tickerAdvertisements as $advertisement)
                        <span>{{ $advertisement->message }}</span>
                    @endforeach
                    @foreach ($tickerAdvertisements as $advertisement)
                        <span>{{ $advertisement->message }}</span>
                    @endforeach
                @else
                    <span>لا توجد إعلانات حالياً</span>
                    <span>يمكنك متابعة أحدث العروض من لوحة التحكم</span>
                    <span>تحديث العروض يتم مباشرة من الإدارة</span>
                    <span>لا توجد إعلانات حالياً</span>
                    <span>يمكنك متابعة أحدث العروض من لوحة التحكم</span>
                    <span>تحديث العروض يتم مباشرة من الإدارة</span>
                @endif
            </div>
        </div>
    @endif

    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="client-side" id="clientSidebar">
        <div class="sidebar-header">
            <div class="brand-logo-text">
                <div class="brand-mini"><span class="brand-red">Med</span><span class="brand-blue">RANKO</span></div>
            </div>
        </div>

        <nav class="flex-1 mt-4">
            <a href="/client" class="client-side-link {{ request()->is('client') ? 'active' : '' }}">
                <span>🏠</span> الرئيسية
            </a>
            <a href="/client/search" class="client-side-link {{ request()->is('client/search') ? 'active' : '' }}">
                <span>🔍</span> البحث المتقدم
            </a>
            <a href="/client/products" class="client-side-link {{ request()->is('client/products') ? 'active' : '' }}">
                <span>📦</span> كل المنتجات
            </a>
            <a href="/client/ranking" class="client-side-link {{ request()->is('client/ranking') ? 'active' : '' }}">
                <span>🏆</span> الترتيب
            </a>
            <a href="/client/suppliers-today"
                class="client-side-link {{ request()->is('client/suppliers-today') ? 'active' : '' }}">
                <span>🕐</span> موردين اليوم
            </a>
            <a href="/client/compare" class="client-side-link {{ request()->is('client/compare') ? 'active' : '' }}">
                <span>⚖️</span> المقارنة الذكية
            </a>
            <a href="/client/compare-platform"
                class="client-side-link {{ request()->is('client/compare-platform') ? 'active' : '' }}">
                <span>📑</span> مقارنة ملف مع المنصة
            </a>
            <a href="/client/saved-comparisons"
                class="client-side-link {{ request()->is('client/saved-comparisons') || request()->is('client/saved-comparisons/*') ? 'active' : '' }}">
                <span>💾</span> حفظ المقارنة
            </a>
            <a href="/client/favorites"
                class="client-side-link {{ request()->is('client/favorites') ? 'active' : '' }}">
                <span>⭐</span> المفضلة
            </a>
            <a href="/client/activate" class="client-side-link {{ request()->is('client/activate') ? 'active' : '' }}">
                <span>🚀</span> تفعيل الاشتراك
            </a>
            <a href="/client/password" class="client-side-link {{ request()->is('client/password') ? 'active' : '' }}">
                <span>⚙️</span> الإعدادات
            </a>
        </nav>


    </aside>

    <div class="flex flex-col min-h-screen">
        <header class="client-topbar">
            <div class="client-topbar-inner">
                <div class="topbar-left">
                    <button type="button" id="sidebarToggleBtn" class="topbar-menu-btn" aria-label="فتح القائمة">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7" />
                        </svg>
                    </button>
                </div>

                <div class="topbar-brand">
                    <div class="brand-name"><span class="brand-red">Med</span><span class="brand-blue">RANKO</span>
                    </div>
                </div>

                <div class="topbar-right">
                    <button type="button" id="themeToggle" class="topbar-action-btn theme-btn" title="تبديل المظهر"
                        aria-label="تبديل المظهر">
                        <svg id="themeIconSun" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"
                            stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <svg id="themeIconMoon" class="h-4 w-4 hidden" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                    </button>
                    <a href="/client/password" class="topbar-action-btn primary" title="الإعدادات">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </a>
                    <button onclick="clientLogout()" class="topbar-action-btn warning" title="تسجيل الخروج">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <div class="client-page-shell">
                @yield('content')
            </div>
        </main>
    </div>

    <div id="clientToastWrap" class="client-toast-wrap"></div>

    <script>
        // Sidebar Logic
        const sidebar = document.getElementById('clientSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        const toggleSidebar = () => {
            const isVisible = sidebar.classList.contains('show');
            sidebar.classList.toggle('show', !isVisible);
            overlay.classList.toggle('show', !isVisible);
        };

        const closeSidebar = () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
        };

        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', closeSidebar);

        document.querySelectorAll('.client-side-link').forEach(link => {
            link.addEventListener('click', closeSidebar);
        });

        // Theme Toggle Logic
        const themeToggleBtn = document.getElementById('themeToggle');
        const themeIconSun = document.getElementById('themeIconSun');
        const themeIconMoon = document.getElementById('themeIconMoon');

        const applyTheme = (theme) => {
            const light = theme === 'light';
            document.documentElement.classList.toggle('theme-light', light);
            if (themeIconSun) themeIconSun.classList.toggle('hidden', light);
            if (themeIconMoon) themeIconMoon.classList.toggle('hidden', !light);
        };

        const storedTheme = localStorage.getItem('client-theme');
        const systemPrefersLight = window.matchMedia('(prefers-color-scheme: light)').matches;
        applyTheme(storedTheme ? storedTheme : (systemPrefersLight ? 'light' : 'dark'));

        if (themeToggleBtn) {
            themeToggleBtn.addEventListener('click', () => {
                const next = document.documentElement.classList.contains('theme-light') ? 'dark' : 'light';
                localStorage.setItem('client-theme', next);
                applyTheme(next);
            });
        }

        // Profile Menu Logic
        const profileBtn = document.getElementById('profileMenuBtn');
        const profileMenu = document.getElementById('profileMenu');

        if (profileBtn && profileMenu) {
            profileBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                profileMenu.classList.toggle('show');
            });

            document.addEventListener('click', () => profileMenu.classList.remove('show'));
        }
    </script>
    @stack('scripts')

    <style id="clientLightThemeOverrides">
        html.theme-light [class*="text-white"] {
            color: #0f172a !important;
        }

        html.theme-light [class*="bg-sky-"][class*="text-white"],
        html.theme-light [class*="bg-blue-"][class*="text-white"],
        html.theme-light [class*="bg-red-"][class*="text-white"],
        html.theme-light [class*="bg-rose-"][class*="text-white"],
        html.theme-light [class*="bg-emerald-"][class*="text-white"],
        html.theme-light [class*="bg-green-"][class*="text-white"],
        html.theme-light [class*="bg-amber-"][class*="text-white"],
        html.theme-light [class*="bg-violet-"][class*="text-white"],
        html.theme-light [class*="bg-indigo-"][class*="text-white"],
        html.theme-light [class*="bg-purple-"][class*="text-white"] {
            color: #ffffff !important;
        }

        html.theme-light [class*="text-slate-200"],
        html.theme-light [class*="text-slate-300"] {
            color: #334155 !important;
        }

        html.theme-light [class*="text-slate-400"],
        html.theme-light [class*="text-slate-500"],
        html.theme-light [class*="text-slate-600"] {
            color: #475569 !important;
        }

        html.theme-light [class*="text-slate-700"],
        html.theme-light [class*="text-slate-800"] {
            color: #1e293b !important;
        }

        html.theme-light [class*="text-red-400"],
        html.theme-light [class*="text-red-500"] {
            color: #dc2626 !important;
        }

        html.theme-light [class*="text-rose-400"],
        html.theme-light [class*="text-rose-500"] {
            color: #e11d48 !important;
        }

        html.theme-light [class*="text-emerald-300"],
        html.theme-light [class*="text-emerald-400"],
        html.theme-light [class*="text-green-400"] {
            color: #059669 !important;
        }

        html.theme-light [class*="text-sky-300"],
        html.theme-light [class*="text-sky-400"] {
            color: #0284c7 !important;
        }

        html.theme-light [class*="text-amber-300"],
        html.theme-light [class*="text-amber-500"] {
            color: #d97706 !important;
        }

        html.theme-light [class*="text-blue-600"] {
            color: #2563eb !important;
        }

        html.theme-light [class*="bg-slate-6"],
        html.theme-light [class*="bg-slate-7"],
        html.theme-light [class*="bg-slate-8"],
        html.theme-light [class*="bg-slate-9"],
        html.theme-light [class*="bg-slate-950"] {
            background-color: #f1f5f9 !important;
        }

        html.theme-light [class*="bg-black"] {
            background-color: rgb(15 23 42 / 0.15) !important;
        }

        html.theme-light [class*="bg-white/"] {
            background-color: rgb(15 23 42 / 0.06) !important;
        }

        html.theme-light [class*="border-white/"] {
            border-color: rgb(15 23 42 / 0.12) !important;
        }

        html.theme-light [class*="border-slate-600"] {
            border-color: #cbd5e1 !important;
        }

        html.theme-light [class*="divide-white/"] {
            border-color: rgb(15 23 42 / 0.08) !important;
        }
    </style>
</body>

</html>
