@extends('layouts.client')

@section('title', 'تفعيل الحساب')

@section('content')
    <div class="client-card mx-auto max-w-md p-6">
        <h3 class="text-lg font-semibold mb-4">تفعيل الحساب</h3>
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
                setTimeout(() => window.location.href = '/client', 2000);
            } catch (err) {
                messageEl.textContent = 'رمز التفعيل غير صحيح';
                messageEl.className = 'text-red-500 mt-2';
                messageEl.classList.remove('hidden');
            }
        }
    </script>
@endpush
