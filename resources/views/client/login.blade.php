@extends('layouts.client-guest')

@section('title', 'تسجيل الدخول')

@section('content')
    <div class="bg-white p-8 rounded-3xl shadow-xl border border-sky-100">
        <div class="mb-6 text-center">
            <div class="text-4xl font-extrabold tracking-wide">
                <span class="text-slate-900">Med</span>
                <span class="text-rose-700">RANKO</span>
            </div>
            <p class="mt-2 text-sm text-sky-600">رتب صح .. ووفر أكتر</p>
        </div>
        <h3 class="text-2xl font-semibold mb-6 text-center text-sky-700">تسجيل دخول الصيدلي</h3>
        <div id="loginForm" class="space-y-5">
            <div>
                <label class="block text-sm font-medium mb-2">رقم الهاتف</label>
                <input type="text" id="phone"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none" required>
            </div>
            <div>
                <label class="block text-sm font-medium mb-2">كلمة المرور</label>
                <input type="password" id="password"
                    class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none" required>
            </div>
            <button type="button" id="loginBtn"
                class="w-full rounded-2xl bg-sky-600 text-white py-3 text-lg transition hover:bg-sky-500">دخول</button>
            <p id="error" class="text-red-500 mt-2 hidden"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const clientApiLoginUrl = @json(url('/api/login'));
        const clientSearchUrl = @json(url('/client/search'));

        // Fallback for stale JS bundles on server.
        if (typeof window.setClientToken !== 'function') {
            window.setClientToken = function (token) {
                sessionStorage.setItem('client_token', token);
                document.cookie = `client_token=${encodeURIComponent(token)}; path=/; max-age=86400; samesite=lax`;
            };
        }

        const btn = document.getElementById('loginBtn');
        if (btn) {
            btn.addEventListener('click', login);
        }

        async function login(event) {
            event.preventDefault();
            const phone = document.getElementById('phone').value.trim();
            const password = document.getElementById('password').value;
            const errorEl = document.getElementById('error');
            const btn = document.getElementById('loginBtn');

            btn.disabled = true;
            btn.textContent = 'جاري الدخول...';
            errorEl.classList.add('hidden');

            try {
                const res = await axios.post(clientApiLoginUrl, {
                    phone,
                    password,
                    device_name: 'web'
                });
                if (res.data?.token) {
                    setClientToken(res.data.token);
                    setTimeout(() => {
                        window.location.replace(clientSearchUrl);
                    }, 50);
                    return;
                }
                throw new Error('لم يتم استلام التوكن');
            } catch (err) {
                console.error('[client-login] login error', err);
                let message = err.response?.data?.message || err.message || 'خطأ في تسجيل الدخول';
                if (err.response?.data?.errors) {
                    const firstError = Object.values(err.response.data.errors)[0];
                    if (Array.isArray(firstError)) {
                        message = firstError[0];
                    }
                }
                errorEl.textContent = message;
                errorEl.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.textContent = 'دخول';
            }
        }
    </script>
@endpush
