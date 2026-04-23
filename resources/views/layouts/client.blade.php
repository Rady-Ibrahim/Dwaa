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
        /* الأساسيات */
        .client-shell {
            min-height: 100vh;
            background: #0f172a;
            /* لون داكن أساسي */
            color: #e2e8f0;
            font-family: 'Inter', 'Noto Sans Arabic', sans-serif;
        }

        /* الهيدر العلوي - Topbar */
        .client-topbar {
            height: 70px;
            background: rgba(15, 23, 42, 0.8);
            /* خلفية شفافة داكنة */
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(12px);
            position: sticky;
            top: 0;
            z-index: 40;
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
        }

        .topbar-menu-btn {
            background: rgba(56, 189, 248, 0.1);
            border: 1px solid rgba(56, 189, 248, 0.2);
            color: #38bdf8;
            border-radius: 10px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            transition: all 0.2s;
        }

        .topbar-menu-btn:hover {
            background: #38bdf8;
            color: #0f172a;
        }

        .topbar-title {
            flex: 1;
            margin-right: 1.25rem;
            font-size: 1.15rem;
            font-weight: 700;
            color: #f8fafc;
            letter-spacing: -0.02em;
        }

        /* المنيو الجانبي - Sidebar */
        .client-side {
            position: fixed;
            top: 0;
            right: 0;
            width: 280px;
            height: 100vh;
            background: #070e1e;
            border-left: 1px solid rgba(255, 255, 255, 0.05);
            z-index: 50;
            transform: translateX(100%);
            transition: transform .3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
        }

        .client-side.show {
            transform: translateX(0);
            box-shadow: -10px 0 50px rgba(0, 0, 0, 0.5);
        }

        .sidebar-header {
            padding: 2rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .brand-logo-text {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .brand-logo-text img {
            max-width: 220px;
            height: auto;
            display: block;
        }

        /* الروابط الجانبية */
        .client-side-link {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 4px 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: #94a3b8;
            font-weight: 500;
            transition: all 0.2s;
        }

        .client-side-link:hover {
            background: rgba(255, 255, 255, 0.03);
            color: #38bdf8;
        }

        .client-side-link.active {
            background: rgba(56, 189, 248, 0.1);
            color: #38bdf8;
            box-shadow: inset 0 0 0 1px rgba(56, 189, 248, 0.2);
        }

        /* بروفايل المستخدم */
        .profile-trigger {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 5px 12px;
            border-radius: 99px;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
        }

        .profile-trigger:hover {
            border-color: rgba(56, 189, 248, 0.4);
            background: rgba(56, 189, 248, 0.05);
        }

        .profile-menu {
            position: absolute;
            top: 110%;
            left: 0;
            background: #1e293b;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 8px;
            width: 220px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3);
            display: none;
            z-index: 100;
        }

        .profile-menu.show {
            display: block;
            animation: slideDown 0.2s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Overlay */
        .sidebar-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.6);
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

        /* News Ticker Animation */
        @keyframes scroll {
            0% {
                transform: translateX(0);
            }
            100% {
                transform: translateX(-50%);
            }
        }

        .news-ticker {
            position: relative;
        }

        .ticker-content {
            display: flex;
            width: fit-content;
        }

        .ticker-content span {
            flex-shrink: 0;
        }
    </style>
    @stack('styles')
</head>

<body class="client-shell @yield('body_class')">
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <aside class="client-side" id="clientSidebar">
        <div class="sidebar-header">
            <div class="brand-logo-text">
                <img src="\images\brand\med-ranko-logo.jpeg" alt="MedRANKO logo">
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

    <!-- News Ticker -->
    @if($tickerAdvertisements->count() > 0)
        <div class="news-ticker bg-gradient-to-r from-[#8B1538] to-[#a61e45] text-white py-2 overflow-hidden">
            <div class="ticker-content flex items-center" style="animation: scroll 20s linear infinite;">
                @foreach($tickerAdvertisements as $advertisement)
                    <span class="mx-8 whitespace-nowrap">{{ $advertisement->message }}</span>
                @endforeach
                <!-- Duplicate for seamless scrolling -->
                @foreach($tickerAdvertisements as $advertisement)
                    <span class="mx-8 whitespace-nowrap">{{ $advertisement->message }}</span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="flex flex-col min-h-screen">
        <header class="client-topbar flex justify-between items-center px-6">
            <div class="flex items-center gap-4">
                <button type="button" id="sidebarToggleBtn" class="topbar-menu-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 6h16M4 12h16m-7 6h7" />
                    </svg>
                </button>
                <h2 class="topbar-title">@yield('title', 'الرئيسية')</h2>
            </div>

            <div class="flex items-center gap-3">
                <a href="/client/password"
                    class="w-10 h-10 rounded-xl bg-sky-500/10 border border-sky-500/20 flex items-center justify-center text-sky-400 hover:bg-sky-500 hover:text-white transition-all shadow-lg shadow-sky-900/20"
                    title="الإعدادات الشخصية">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                </a>

                <button onclick="clientLogout()"
                    class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/20 flex items-center justify-center text-rose-400 hover:bg-rose-500 hover:text-white transition-all shadow-lg shadow-rose-900/20"
                    title="تسجيل الخروج">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </button>
            </div>
        </header>

        <main class="flex-1">
            @yield('content')
        </main>
    </div>

    <div id="clientToastWrap" class="client-toast-wrap"></div>

    <script>
        // Sidebar Logic
        const sidebar = document.getElementById('clientSidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const toggleBtn = document.getElementById('sidebarToggleBtn');

        const toggleSidebar = () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        };

        toggleBtn.addEventListener('click', toggleSidebar);
        overlay.addEventListener('click', toggleSidebar);

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
</body>

</html>
