@php $hideAuthFooter = true; @endphp
@extends('layouts.auth')

@section('title', 'تسجيل الدخول — MedRANKO')

@section('auth_title', 'تسجيل دخول العميل')

@section('auth_form')
    <div id="loginForm" class="space-y-5">
        <div>
            <label for="phone" class="mb-1.5 block text-sm font-semibold text-slate-700">رقم الهاتف</label>
            <div class="relative">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"></path>
                    </svg>
                </span>
                <input type="tel" id="phone" inputmode="tel" autocomplete="username" dir="rtl"
                    placeholder="01xxxxxxxxx"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-4 pr-11 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500"
                    required>
            </div>
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-700">كلمة المرور</label>
            <div class="relative">
                <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z"></path>
                    </svg>
                </span>
                <input type="password" id="password" autocomplete="current-password" placeholder="••••••••"
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 py-3 pl-11 pr-11 text-sm text-slate-800 placeholder:text-slate-400 outline-none transition focus:bg-white focus:ring-2 focus:ring-blue-500"
                    required>
                <button type="button" id="togglePassword"
                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-blue-600"
                    aria-label="إظهار كلمة المرور" title="إظهار/إخفاء كلمة المرور">
                    <svg id="eyeOpenIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12s3.75-7.5 9.75-7.5S21.75 12 21.75 12 18 19.5 12 19.5 2.25 12 2.25 12Z"></path>
                        <circle cx="12" cy="12" r="3"></circle>
                    </svg>
                    <svg id="eyeClosedIcon" class="hidden h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18M10.58 10.58A3 3 0 0 0 12 15a3 3 0 0 0 2.42-4.42M9.88 5.09A9.77 9.77 0 0 1 12 4.5c6 0 9.75 7.5 9.75 7.5a17.57 17.57 0 0 1-4.27 5.3M6.53 6.53A17.59 17.59 0 0 0 2.25 12s3.75 7.5 9.75 7.5a9.7 9.7 0 0 0 4.12-.91"></path>
                    </svg>
                </button>
            </div>
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-600">
            <input type="checkbox" id="rememberMe"
                class="h-4 w-4 rounded border-slate-300 text-blue-600 accent-blue-600 focus:ring-blue-500">
            تذكرني
        </label>

        <button type="button" id="loginBtn"
            class="flex w-full items-center justify-center gap-2 rounded-xl bg-blue-600 py-3 font-bold text-white shadow-lg shadow-blue-500/30 transition hover:bg-blue-700 active:scale-[0.99]">
            <span>دخول</span>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M19 12H5"></path>
                <path d="m12 19-7-7 7-7"></path>
            </svg>
        </button>

        <p id="error" class="hidden mt-2 text-sm font-medium text-red-500"></p>
    </div>
@endsection

@push('scripts')
    <script>
        const clientApiLoginUrl = @json(url('/api/login'));
        const clientSearchUrl = @json(url('/client/search'));

        // رسالة توضيحية عند إعادة التوجيه من الصفحات بسبب انتهاء الجلسة أو وقف الحساب
        const loginNoticeParams = new URLSearchParams(window.location.search);
        let loginNotice = '';
        if (loginNoticeParams.has('expired')) {
            loginNotice = 'انتهت جلستك، يرجى تسجيل الدخول من جديد.';
        } else if (loginNoticeParams.has('blocked')) {
            loginNotice = 'تم إيقاف الحساب أو فقدان الصلاحية. يرجى التواصل مع الإدارة.';
        } else if (loginNoticeParams.has('subscription')) {
            loginNotice = 'انتهى اشتراكك. يرجى تجديده من صفحة التفعيل ثم تسجيل الدخول.';
        }
        if (loginNotice) {
            const loginErrorEl = document.getElementById('error');
            if (loginErrorEl) {
                loginErrorEl.textContent = loginNotice;
                loginErrorEl.classList.remove('hidden');
            }
        }

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

        togglePasswordBtn?.addEventListener('click', function() {
            const isPassword = passwordInput.type === 'password';
            passwordInput.type = isPassword ? 'text' : 'password';
            eyeOpenIcon?.classList.toggle('hidden', isPassword);
            eyeClosedIcon?.classList.toggle('hidden', !isPassword);
        });

        // Fallback for stale JS bundles on server.
        if (typeof window.setClientToken !== 'function') {
            window.setClientToken = function(token, remember = false) {
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

            // ── Device Fingerprint ─────────────────────────────────────────
            // UUID ثابت يُحفظ في localStorage لتعريف الجهاز بشكل دائم
            let deviceFingerprint = localStorage.getItem('_mranko_device_id');
            if (!deviceFingerprint) {
                deviceFingerprint = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, c => {
                    const r = Math.random() * 16 | 0;
                    return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
                });
                localStorage.setItem('_mranko_device_id', deviceFingerprint);
            }
            // ──────────────────────────────────────────────────────────────

            try {
                const res = await axios.post(clientApiLoginUrl, {
                    phone,
                    password,
                    device_name: navigator.userAgent.substring(0, 100),
                    device_fingerprint: deviceFingerprint,
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