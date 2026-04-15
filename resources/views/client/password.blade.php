@extends('layouts.client')

@section('title', 'الإعدادات الشخصية')

@section('content')
    <style>
        .settings-card {
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 1.25rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.02);
            transition: all 0.3s ease;
        }

        .info-row:hover {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.05);
        }

        /* كلاس مخصص لضمان ظهور الأرقام بالإنجليزية وتنسيق ltr */
        .en-numbers {
            font-family: 'Inter', sans-serif;
            direction: ltr;
            display: inline-block;
        }

        .input-wrapper {
            position: relative;
        }

        .form-input {
            width: 100%;
            background: rgba(15, 23, 42, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 14px;
            padding: 0.85rem 3.5rem 0.85rem 1rem;
            color: white;
            transition: all 0.3s;
        }

        .form-input:focus {
            outline: none;
            border-color: #38bdf8;
            box-shadow: 0 0 0 4px rgba(56, 189, 248, 0.1);
        }

        .eye-toggle {
            position: absolute;
            top: 50%;
            right: 1.25rem;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 1.2rem;
            opacity: 0.5;
            transition: opacity 0.2s, color 0.2s;
            z-index: 10;
            user-select: none;
        }

        .eye-toggle:hover {
            opacity: 1;
            color: #38bdf8;
        }
    </style>

    <div class="grid gap-8 lg:grid-cols-2 items-start">

        <div class="space-y-6">
            <div class="settings-card p-8 shadow-2xl shadow-black/20">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-sky-500/20 flex items-center justify-center text-2xl shadow-inner">
                        👤</div>
                    <div>
                        <h3 class="text-xl font-bold text-white">بيانات الحساب</h3>
                        <p class="text-slate-400 text-xs">معلومات العضوية في نظام MedRANKO</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="info-row">
                        <span class="text-slate-400 text-sm">اسم المستخدم</span>
                        <span id="profileName" class="text-white font-bold text-sm">-</span>
                    </div>
                    <div class="info-row">
                        <span class="text-slate-400 text-sm">رقم الهاتف</span>
                        <span id="profilePhone" class="text-white font-bold text-sm en-numbers">-</span>
                    </div>
                    <div class="info-row">
                        <span class="text-slate-400 text-sm">حالة الاشتراك</span>
                        <span id="profileStatus"
                            class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">-</span>
                    </div>

                    <div class="info-row">
                        <span class="text-slate-400 text-sm">تاريخ الانضمام</span>
                        <span id="profileCreatedAt" class="text-white font-medium text-xs en-numbers">-</span>
                    </div>
                    <div class="info-row">
                        <span class="text-slate-400 text-sm">آخر دخول</span>
                        <span id="profileLastLogin" class="text-white font-medium text-xs en-numbers">-</span>
                    </div>

                    <div class="info-row border-t border-white/5 mt-4 pt-4 bg-sky-500/5">
                        <span class="text-sky-400 text-sm font-bold">صلاحية التفعيل حتى</span>
                        <span id="profileActivatedUntil" class="text-sky-300 font-black text-sm en-numbers">-</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-6">
            <div class="settings-card p-8 border-rose-500/10">
                <div class="flex items-center gap-4 mb-8">
                    <div
                        class="w-12 h-12 rounded-2xl bg-rose-500/20 flex items-center justify-center text-2xl shadow-inner">
                        🔐</div>
                    <div>
                        <h3 class="text-xl font-bold text-white">تعديل كلمة المرور</h3>
                        <p class="text-slate-400 text-xs">تحديث بيانات الأمان الخاصة بك</p>
                    </div>
                </div>

                <form onsubmit="changePassword(event)" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-300 mr-2">كلمة المرور الحالية</label>
                        <div class="input-wrapper">
                            <input type="password" id="currentPassword" class="form-input" placeholder="••••••••" required>
                            <span class="eye-toggle" onclick="togglePass('currentPassword', this)">👁️</span>
                        </div>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-300 mr-2">الكلمة الجديدة</label>
                            <div class="input-wrapper">
                                <input type="password" id="newPassword" class="form-input" placeholder="••••••••" required>
                                <span class="eye-toggle" onclick="togglePass('newPassword', this)">👁️</span>
                            </div>
                        </div>
                        <div class="space-y-2">
                            <label class="block text-sm font-semibold text-slate-300 mr-2">تأكيد الكلمة</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirmPassword" class="form-input" placeholder="••••••••"
                                    required>
                                <span class="eye-toggle" onclick="togglePass('confirmPassword', this)">👁️</span>
                            </div>
                        </div>
                    </div>

                    <div class="pt-4">
                        <button type="submit" id="submitBtn"
                            class="w-full bg-sky-600 hover:bg-sky-500 text-white font-black py-4 rounded-2xl transition-all flex items-center justify-center gap-3 active:scale-[0.98] shadow-lg shadow-sky-900/20">
                            <span>حفظ التعديلات الأمنية</span>
                        </button>
                    </div>
                </form>
                <div id="message" class="mt-4 text-center text-sm font-bold hidden"></div>
            </div>
        </div>

    </div>
@endsection

@push('scripts')
    <script>
        function togglePass(inputId, icon) {
            const input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text";
                icon.textContent = "🙈";
                icon.classList.add('text-sky-400');
            } else {
                input.type = "password";
                icon.textContent = "👁️";
                icon.classList.remove('text-sky-400');
            }
        }

        // دالة التنسيق المحدثة لإخراج أرقام إنجليزية متناسقة
        function formatDate(value) {
            if (!value) return 'N/A';
            const date = new Date(value);
            return date.toLocaleString('en-GB', {
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }).replace(',', '');
        }

        async function loadProfile() {
            try {
                const res = await axios.get('/me');
                const user = res.data?.user || {};

                document.getElementById('profileName').textContent = user.name || 'مستخدم MedRANKO';
                document.getElementById('profilePhone').textContent = user.phone || '-';

                // ملء التواريخ الجديدة
                document.getElementById('profileCreatedAt').textContent = formatDate(user.created_at);

                // جلب آخر دخول من التخزين المحلي أو الـ API حسب منطق نظامك
                const lastLogin = localStorage.getItem('client_last_login_at');
                document.getElementById('profileLastLogin').textContent = formatDate(lastLogin);

                const statusEl = document.getElementById('profileStatus');
                if (user.is_active) {
                    statusEl.textContent = 'نشط (PRO)';
                    statusEl.className =
                        'px-3 py-1 rounded-full text-[10px] font-black bg-emerald-500/10 text-emerald-400 border border-emerald-500/20';
                } else {
                    statusEl.textContent = 'غير نشط';
                    statusEl.className =
                        'px-3 py-1 rounded-full text-[10px] font-black bg-rose-500/10 text-rose-400 border border-rose-500/20';
                }

                document.getElementById('profileActivatedUntil').textContent = user.subscription_expires_at ?
                    formatDate(user.subscription_expires_at) : (user.is_active ? 'Lifetime' : 'Activate Account');
            } catch (err) {
                window.clientNotify('تعذر تحميل بيانات الملف الشخصي', 'error');
            }
        }

        async function changePassword(event) {
            event.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const messageEl = document.getElementById('message');
            const btn = document.getElementById('submitBtn');

            if (newPass !== confirm) {
                messageEl.textContent = '❌ كلمتا المرور غير متطابقتين';
                messageEl.className = 'text-rose-400 mt-4 font-bold block';
                messageEl.classList.remove('hidden');
                return;
            }

            btn.disabled = true;
            btn.innerHTML = 'جاري الحماية...';

            try {
                await axios.post('/password', {
                    current_password: current,
                    password: newPass,
                    password_confirmation: confirm
                });
                messageEl.textContent = '✅ تم تحديث كلمة المرور بنجاح';
                messageEl.className = 'text-emerald-400 mt-4 font-bold block';
                messageEl.classList.remove('hidden');
                event.target.reset();
                window.clientNotify('تم التحديث الأمني', 'success');
            } catch (err) {
                messageEl.textContent = '❌ فشل التحديث، تأكد من كلمة المرور الحالية';
                messageEl.className = 'text-rose-400 mt-4 font-bold block';
                messageEl.classList.remove('hidden');
            } finally {
                btn.disabled = false;
                btn.innerHTML = '<span>حفظ التعديلات الأمنية</span>';
            }
        }

        document.addEventListener('DOMContentLoaded', loadProfile);
    </script>
@endpush
