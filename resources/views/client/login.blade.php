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
                <div class="relative" style="position: relative;">
                    <input type="password" id="password"
                        class="w-full rounded-2xl border border-slate-300 px-4 py-3 focus:border-sky-500 focus:outline-none"
                        style="padding-left: 3.2rem;" required>
                    <button type="button" id="togglePassword"
                        class="text-slate-500 hover:text-slate-700"
                        style="position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%); display: inline-flex; align-items: center; justify-content: center; z-index: 2; padding: 0.2rem;"
                        aria-label="إظهار كلمة المرور" title="إظهار/إخفاء كلمة المرور">
                        <svg id="eyeOpenIcon" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12 18 19.5 12 19.5 2.25 12 2.25 12Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg id="eyeClosedIcon" xmlns="http://www.w3.org/2000/svg" class="hidden h-5 w-5" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3l18 18M10.58 10.58A3 3 0 0012 15a3 3 0 002.42-4.42M9.88 5.09A9.77 9.77 0 0112 4.5c6 0 9.75 7.5 9.75 7.5a17.57 17.57 0 01-4.27 5.3M6.53 6.53A17.59 17.59 0 002.25 12s3.75 7.5 9.75 7.5a9.7 9.7 0 004.12-.91" />
                        </svg>
                    </button>
                </div>
            </div>
            <div class="flex items-center justify-between text-sm">
                <label class="inline-flex cursor-pointer items-center gap-2">
                    <input type="checkbox" id="rememberMe" class="h-4 w-4 rounded border-slate-300 text-sky-600">
                    <span>تذكرني</span>
                </label>
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
        const phoneInput = document.getElementById('phone');
        const passwordInput = document.getElementById('password');
        const rememberInput = document.getElementById('rememberMe');
        const togglePasswordBtn = document.getElementById('togglePassword');
        const eyeOpenIcon = document.getElementById('eyeOpenIcon');
        const eyeClosedIcon = document.getElementById('eyeClosedIcon');
        const rememberedPhone = localStorage.getItem('client_login_phone') || '';
        const rememberedPassword = localStorage.getItem('client_login_password') || '';
        const rememberEnabled = localStorage.getItem('client_remember_login') === '1';

        if (rememberEnabled) {
            rememberInput.checked = true;
            phoneInput.value = rememberedPhone;
            passwordInput.value = rememberedPassword;
        }

        togglePasswordBtn?.addEventListener('click', function () {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeOpenIcon?.classList.toggle('hidden', isPassword);
            eyeClosedIcon?.classList.toggle('hidden', !isPassword);
        });

        // Fallback for stale JS bundles on server.
        if (typeof window.setClientToken !== 'function') {
            window.setClientToken = function (token, remember = false) {
                if (remember) {
                    localStorage.setItem('client_token', token);
                    sessionStorage.removeItem('client_token');
                } else {
                    sessionStorage.setItem('client_token', token);
                    localStorage.removeItem('client_token');
                }
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
            const remember = document.getElementById('rememberMe').checked;
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
                    setClientToken(res.data.token, remember);
                    if (remember) {
                        localStorage.setItem('client_remember_login', '1');
                        localStorage.setItem('client_login_phone', phone);
                        localStorage.setItem('client_login_password', password);
                    } else {
                        localStorage.removeItem('client_remember_login');
                        localStorage.removeItem('client_login_phone');
                        localStorage.removeItem('client_login_password');
                    }
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
