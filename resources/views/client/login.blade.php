@php $hideAuthFooter = true; @endphp
@extends('layouts.auth')

@section('title', 'تسجيل الدخول — MedRANKO')

@section('auth_title', 'تسجيل دخول العميل')

@section('auth_form')
    <div id="loginForm" class="space-y-6">
        <div>
            <label for="phone" class="mb-2 block text-xs font-semibold text-slate-300"> اسم المستخدم </label>
            <div class="relative">
                <input type="text" id="phone" autocomplete="username" dir="ltr"
                    placeholder="••••••••"
                    class="w-full rounded-xl border border-slate-800 bg-slate-950/60 py-3.5 pl-4 pr-10 text-right text-sm text-slate-100 placeholder:text-slate-500 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    required>
                <i
                    class="fa-solid fa-phone pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            </div>
        </div>

        <div>
            <label for="password" class="mb-2 block text-xs font-semibold text-slate-300">كلمة المرور</label>
            <div class="relative">
                <input type="password" id="password" autocomplete="current-password" placeholder="••••••••"
                    class="w-full rounded-xl border border-slate-800 bg-slate-950/60 py-3.5 pl-10 pr-10 text-sm text-slate-100 placeholder:text-slate-500 outline-none transition focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20"
                    required>
                <i
                    class="fa-solid fa-lock pointer-events-none absolute right-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <button type="button" id="togglePassword"
                    class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 transition hover:text-slate-200"
                    aria-label="إظهار كلمة المرور" title="إظهار/إخفاء كلمة المرور">
                    <i id="eyeOpenIcon" class="fa-solid fa-eye text-sm"></i>
                </button>
            </div>
        </div>

        <label class="flex cursor-pointer items-center gap-2 pt-1 text-xs font-medium text-slate-300">
            <input type="checkbox" id="rememberMe"
                class="remember-check h-4 w-4 rounded border-slate-700 bg-slate-900 focus:ring-blue-500">
            تذكرني
        </label>

        <button type="button" id="loginBtn"
            class="flex w-full items-center justify-center gap-2 rounded-xl border border-white/10 px-4 py-3.5 text-sm font-bold text-white shadow-lg shadow-blue-950/40 transition duration-200 hover:brightness-110 hover:shadow-blue-900/40 active:scale-[0.99]"
            style="background: linear-gradient(90deg, #0c1d3d, #1e3a6e, #0c1d3d);">
            <span>دخول</span>
            <i class="fa-solid fa-arrow-left text-xs"></i>
        </button>

        <p id="error" class="hidden mt-2 text-sm font-medium text-rose-400"></p>
    </div>
@endsection

<style>
    .remember-check {
        accent-color: #3b82f6;
    }
</style>

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
