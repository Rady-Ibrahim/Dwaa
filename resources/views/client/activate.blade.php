@extends('layouts.client')

@section('title', 'تفعيل الحساب')

@section('content')
    <div class="client-card mx-auto max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">تفعيل الحساب</h3>
        <div id="activationInfo" class="mb-4 hidden rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-700"></div>
        <form onsubmit="activate(event)">
            <div class="mb-4">
                <label class="block text-sm font-medium">رمز التفعيل</label>
                <input type="text" id="code"
                    class="w-full rounded-xl border border-slate-300 p-2.5 focus:border-sky-500 focus:outline-none" required>
            </div>
            <button type="submit" class="w-full rounded-xl bg-sky-600 p-2.5 text-white transition hover:bg-sky-500">تفعيل</button>
        </form>
        <p id="message" class="mt-2 hidden"></p>
    </div>
@endsection

@push('scripts')
    <script>
        const activationInfo = document.getElementById('activationInfo');
        const activationCodeInput = document.getElementById('code');

        function formatDate(value) {
            if (!value) return null;
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return null;
            return date.toLocaleString('ar-EG');
        }

        async function loadActivationState() {
            try {
                const res = await axios.get('/me');
                const user = res.data?.user || {};
                if (user.is_active) {
                    const until = formatDate(user.subscription_expires_at);
                    activationInfo.textContent = until ?
                        `الحساب مفعل بالفعل حتى ${until}` :
                        'الحساب مفعل بالفعل.';
                    activationInfo.classList.remove('hidden');
                    activationCodeInput.required = false;
                }
            } catch (err) {
                // Ignore and keep form usable.
            }
        }

        async function activate(event) {
            event.preventDefault();
            const code = document.getElementById('code').value;
            const messageEl = document.getElementById('message');

            try {
                await axios.post('/activate', {
                    code
                });
                messageEl.textContent = 'تم تفعيل الحساب بنجاح';
                messageEl.className = 'text-green-500 mt-2';
                messageEl.classList.remove('hidden');
                loadActivationState();
                setTimeout(() => window.location.href = '/client', 2000);
            } catch (err) {
                messageEl.textContent = 'رمز التفعيل غير صحيح';
                messageEl.className = 'text-red-500 mt-2';
                messageEl.classList.remove('hidden');
            }
        }

        document.addEventListener('DOMContentLoaded', loadActivationState);
    </script>
@endpush
