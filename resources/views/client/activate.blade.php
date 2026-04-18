@extends('layouts.client')

@section('title', 'تفعيل الحساب')

@section('content')
    <div class="max-w-2xl mx-auto py-10">
        <div
            class="relative overflow-hidden bg-slate-900/50 backdrop-blur-xl border border-white/10 rounded-3xl p-8 shadow-2xl">
            <div class="absolute -top-24 -left-24 w-48 h-48 bg-sky-500/10 blur-[100px] pointer-events-none"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-12 h-12 rounded-2xl bg-sky-500/20 flex items-center justify-center text-2xl">🚀</div>
                    <div>
                        <h3 class="text-xl font-bold text-white">تفعيل اشتراك الحساب</h3>
                    </div>
                </div>

                <div id="activationInfo"
                    class="hidden mb-6 rounded-2xl border border-emerald-500/20 bg-emerald-500/10 p-4 text-emerald-400 flex items-center gap-3">
                    <span class="text-lg">✅</span>
                    <span class="text-sm font-medium"></span>
                </div>

                <form onsubmit="activate(event)" class="space-y-6">
                    <div class="space-y-2">
                        <label class="block text-sm font-semibold text-slate-300 mr-1">رمز التفعيل (Activation Code)</label>
                        <input type="text" id="code"
                            class="w-full bg-slate-950/50 rounded-2xl border border-white/5 p-4 text-white placeholder:text-slate-600 focus:border-sky-500/50 focus:ring-4 focus:ring-sky-500/10 focus:outline-none transition-all text-center text-xl tracking-[0.5em] font-mono"
                            placeholder="XXXX-XXXX" required>
                    </div>

                    <button type="submit"
                        class="w-full relative group overflow-hidden rounded-2xl bg-sky-600 p-4 font-bold text-white transition-all hover:bg-sky-500 hover:shadow-[0_0_30px_rgba(14,165,233,0.3)] active:scale-[0.98]">
                        <span class="relative z-10 flex items-center justify-center gap-2">
                            تأكيد التفعيل
                            <svg xmlns="http://www.w3.org/2000/svg"
                                class="h-5 w-5 group-hover:translate-x-[-4px] transition-transform" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                            </svg>
                        </span>
                    </button>
                    <p id="message" class="hidden text-center mt-4 text-sm font-bold"></p>
                </form>


            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const activationInfo = document.getElementById('activationInfo');
        const activationInfoText = activationInfo.querySelector('span:last-child');
        const activationCodeInput = document.getElementById('code');

        function formatDate(value) {
            if (!value) return null;
            const date = new Date(value);
            if (Number.isNaN(date.getTime())) return null;
            return date.toLocaleDateString('ar-EG', {
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        }

        async function loadActivationState() {
            try {
                const res = await axios.get('/me');
                const user = res.data?.user || {};
                const expiresAt = user.subscription_expires_at ? new Date(user.subscription_expires_at) : null;
                const isSubscriptionActive = user.is_active && expiresAt && expiresAt.getTime() > Date.now();

                if (isSubscriptionActive) {
                    const until = formatDate(user.subscription_expires_at);
                    activationInfoText.textContent = until ?
                        `حسابك نشط حالياً. ينتهي الاشتراك في ${until}` :
                        'حسابك مفعل بالكامل بنظام الاشتراك الدائم.';
                    activationInfo.classList.remove('hidden');
                    activationInfo.classList.add('flex');
                    activationCodeInput.placeholder = "الحساب نشط بالفعل";
                } else {
                    activationInfo.classList.add('hidden');
                    activationInfo.classList.remove('flex');
                    activationCodeInput.placeholder = "XXXX-XXXX";
                }
            } catch (err) {
                console.error('State load failed', err);
            }
        }

        async function activate(event) {
            event.preventDefault();
            const code = activationCodeInput.value;
            const messageEl = document.getElementById('message');
            const submitBtn = event.target.querySelector('button');

            // Loading state
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-70');
            messageEl.classList.add('hidden');

            try {
                await axios.post('/activate', {
                    code
                });

                messageEl.textContent = '✅ اكتمل التفعيل! يتم الآن تحديث بياناتك...';
                messageEl.className = 'text-emerald-400 text-center mt-4 font-bold text-lg block';
                messageEl.classList.remove('hidden');
                messageEl.style.display = 'block';

                window.clientNotify('تم التفعيل بنجاح', 'success');

                setTimeout(() => window.location.reload(), 2500);
            } catch (err) {
                messageEl.textContent = '❌ عذراً، هذا الرمز غير صالح أو تم استخدامه مسبقاً.';
                messageEl.className = 'text-rose-400 text-center mt-4 font-bold block';
                messageEl.classList.remove('hidden');
                window.clientNotify('فشل في التفعيل', 'error');
            } finally {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-70');
            }
        }

        document.addEventListener('DOMContentLoaded', loadActivationState);
    </script>
@endpush
