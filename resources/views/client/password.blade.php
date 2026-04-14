@extends('layouts.client')

@section('title', 'تغيير كلمة المرور')

@section('content')
    <div class="client-card mx-auto max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">تغيير كلمة المرور</h3>
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
            <button type="submit" class="w-full rounded-xl bg-sky-600 p-2.5 text-white transition hover:bg-sky-500">تغيير</button>
        </form>
        <p id="message" class="mt-2 hidden"></p>
    </div>
@endsection

@push('scripts')
    <script>
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
    </script>
@endpush
