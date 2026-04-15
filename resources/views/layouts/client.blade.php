<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dwaa Client</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .client-card {
            border: 1px solid #e0f2fe;
            background: #fff;
            border-radius: 1rem;
            box-shadow: 0 2px 10px rgba(2, 132, 199, 0.08);
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .client-card:hover { transform: translateY(-1px); box-shadow: 0 6px 16px rgba(2, 132, 199, 0.12); }
        .brand-logo-text {
            font-weight: 800;
            letter-spacing: .5px;
            line-height: 1;
        }
        .client-toast-wrap {
            position: fixed;
            top: 1rem;
            left: 1rem;
            z-index: 1000;
            display: flex;
            flex-direction: column;
            gap: .5rem;
            width: min(90vw, 360px);
        }
        .client-toast {
            border-radius: .8rem;
            padding: .75rem .9rem;
            color: #0f172a;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            box-shadow: 0 10px 20px rgba(15, 23, 42, 0.12);
            font-size: .92rem;
            line-height: 1.35;
            animation: slideIn .2s ease;
        }
        .client-toast--success { border-right: 4px solid #16a34a; }
        .client-toast--error { border-right: 4px solid #dc2626; }
        .client-toast--info { border-right: 4px solid #0284c7; }
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-8px); }
            to { opacity: 1; transform: translateX(0); }
        }
    </style>
    @stack('styles')
</head>

<body class="min-h-screen bg-sky-50 text-slate-800">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 border-l border-sky-100 bg-white/95 shadow-lg backdrop-blur">
            <div class="p-5">
                <div class="brand-logo-text text-3xl">
                    <span class="text-slate-900">Med</span>
                    <span class="text-rose-700">RANKO</span>
                </div>
                <p class="mt-2 text-xs text-sky-600">رتب صح .. ووفر أكتر</p>
            </div>
            <nav class="mt-2 space-y-1 px-3 pb-4">
                <a href="/client"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">الرئيسية</a>
                <a href="/client/search"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">البحث</a>
                
                <a href="/client/compare"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">المقارنة</a>
                <a href="/client/favorites"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">المفضلة</a>
                <a href="/client/saved-comparisons"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">المقارنات
                    المحفوظة</a>
                <a href="/client/activate"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">تفعيل الحساب</a>
                <a href="/client/password"
                    class="block rounded-xl px-4 py-2.5 text-slate-700 transition hover:bg-sky-100 hover:text-sky-700">الإعدادات</a>
                <button onclick="clientLogout()"
                    class="block w-full rounded-xl px-4 py-2.5 text-right text-slate-700 transition hover:bg-rose-100 hover:text-rose-700">تسجيل
                    الخروج</button>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 flex flex-col">
            <!-- Header -->
            <header class="border-b border-sky-100 bg-white/80 p-4 shadow-sm backdrop-blur">
                <h2 class="text-xl font-semibold text-sky-700">@yield('title', 'الرئيسية')</h2>
            </header>

            <!-- Content -->
            <main class="flex-1 overflow-auto bg-gradient-to-b from-sky-50 to-white p-6">
                @yield('content')
            </main>
        </div>
    </div>

    <div id="clientToastWrap" class="client-toast-wrap"></div>

    @stack('scripts')
    <script>
        (function ensureClientToken() {
            const hasToken = !!(localStorage.getItem('client_token') || sessionStorage.getItem('client_token'));
            if (!hasToken) {
                window.location.replace('/client/login');
            }
        })();

        window.addEventListener('unhandledrejection', function (event) {
            const status = event.reason?.response?.status;
            if (status === 401) {
                clientLogout();
            }
        });

        window.addEventListener('error', function (event) {
            const status = event.error?.response?.status;
            if (status === 401) {
                clientLogout();
            }
        });

        if (window.axios) {
            window.axios.interceptors.response.use(
                response => response,
                error => {
                    if (error?.response?.status === 401) {
                        clientLogout();
                    }
                    return Promise.reject(error);
                }
            );
        }

        window.clientNotify = function (message, type = 'info') {
            const wrap = document.getElementById('clientToastWrap');
            if (!wrap || !message) return;

            const toast = document.createElement('div');
            toast.className = `client-toast client-toast--${type}`;
            toast.textContent = message;
            wrap.appendChild(toast);

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(-8px)';
                setTimeout(() => toast.remove(), 180);
            }, 3200);
        };
    </script>
</body>

</html>
