@extends('layouts.client')

@section('title', 'الإعدادات')

@section('content')
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="client-card p-6">
            <h3 class="mb-4 text-lg font-semibold">بيانات الحساب</h3>
            <div class="space-y-3 text-sm">
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">الاسم:</span> <span id="profileName"
                        class="font-semibold">-</span></div>
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">رقم الهاتف:</span> <span id="profilePhone"
                        class="font-semibold">-</span></div>
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">الحالة:</span> <span id="profileStatus"
                        class="font-semibold">-</span></div>
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">تاريخ التسجيل:</span> <span id="profileCreatedAt"
                        class="font-semibold">-</span></div>
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">آخر تسجيل دخول:</span> <span id="profileLastLogin"
                        class="font-semibold">-</span></div>
                <div class="rounded-xl bg-slate-50 p-3"><span class="text-slate-500">التفعيل/الاشتراك حتى:</span> <span
                        id="profileActivatedUntil" class="font-semibold">-</span></div>
            </div>
        </div>

        <div class="client-card p-6">
            <h3 class="mb-4 text-lg font-semibold">تغيير كلمة المرور</h3>
            <form onsubmit="changePassword(event)">
                <div class="mb-4">
                    <label class="block text-sm font-medium">كلمة المرور الحالية</label>
                    <input type="password" id="currentPassword"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-sky-500 focus:outline-none" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">كلمة المرور الجديدة</label>
                    <input type="password" id="newPassword"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-sky-500 focus:outline-none" required>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium">تأكيد كلمة المرور الجديدة</label>
                    <input type="password" id="confirmPassword"
                        class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-sky-500 focus:outline-none" required>
                </div>
                <button type="submit"
                    class="w-full rounded-xl bg-sky-600 p-2.5 text-white transition hover:bg-sky-500">تغيير</button>
            </form>
            <p id="message" class="mt-2 hidden"></p>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function formatDate(value) {
            if (!value) return 'غير متاح';
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return 'غير متاح';
            return date.toLocaleString('ar-EG');
        }

        async function loadProfile() {
            try {
                const res = await axios.get('/me');
                const user = res.data?.user || {};
                document.getElementById('profileName').textContent = user.name || '-';
                document.getElementById('profilePhone').textContent = user.phone || '-';
                document.getElementById('profileStatus').textContent = user.is_active ? 'مفعل' : 'غير مفعل';
                document.getElementById('profileCreatedAt').textContent = formatDate(user.created_at);
                document.getElementById('profileActivatedUntil').textContent = user.subscription_expires_at ?
                    formatDate(user.subscription_expires_at) :
                    (user.is_active ? 'مفعل (بدون تاريخ انتهاء)' : 'غير مفعل');

                const lastLogin = localStorage.getItem('client_last_login_at');
                document.getElementById('profileLastLogin').textContent = formatDate(lastLogin);
            } catch (err) {
                clientNotify('تعذر تحميل بيانات الحساب', 'error');
            }
        }

        async function changePassword(event) {
            event.preventDefault();
            const current = document.getElementById('currentPassword').value;
            const newPass = document.getElementById('newPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const messageEl = document.getElementById('message');

            if (newPass !== confirm) {
                messageEl.textContent = 'كلمة المرور غير متطابقة';
                messageEl.className = 'text-red-500 mt-2';
                messageEl.classList.remove('hidden');
                return;
            }

            try {
                await axios.post('/password', {
                    current_password: current,
                    password: newPass,
                    password_confirmation: confirm
                });
                messageEl.textContent = 'تم تغيير كلمة المرور بنجاح';
                messageEl.className = 'text-green-500 mt-2';
                messageEl.classList.remove('hidden');
            } catch (err) {
                messageEl.textContent = 'خطأ في تغيير كلمة المرور';
                messageEl.className = 'text-red-500 mt-2';
                messageEl.classList.remove('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', loadProfile);
    </script>
@endpush
