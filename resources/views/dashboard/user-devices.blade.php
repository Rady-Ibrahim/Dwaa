@extends('layouts.admin')

@section('title', 'أجهزة ' . $user->name)
@section('heading', 'أجهزة المستخدم')
@section('subheading', $user->name . ' — ' . $user->phone)

@section('content')

    {{-- رابط الرجوع --}}
    <div class="mb-6">
        <a href="{{ route('dashboard.users') }}"
            class="inline-flex items-center gap-2 text-sm text-zinc-400 hover:text-zinc-200 transition">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
            </svg>
            العودة للمستخدمين
        </a>
    </div>

    {{-- بطاقة المستخدم --}}
    <div class="mb-6 rounded-2xl border p-5 flex items-center justify-between gap-4"
        style="border-color: var(--border-subtle); background: var(--surface-card);">
        <div class="flex items-center gap-4">
            <div class="flex h-12 w-12 items-center justify-center rounded-xl text-xl font-bold"
                style="background: var(--brand-muted); color: #fda4af;">
                {{ mb_substr($user->name, 0, 1) }}
            </div>
            <div>
                <p class="font-semibold text-white">{{ $user->name }}</p>
                <p class="text-sm text-zinc-400 font-mono" dir="ltr">{{ $user->phone }}</p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            {{-- عداد الأجهزة --}}
            <div class="rounded-xl border px-4 py-2 text-center min-w-[100px]"
                style="border-color: var(--border-subtle); background: var(--surface-raised);">
                <p class="text-2xl font-bold {{ $devices->count() >= $max_devices ? 'text-rose-400' : 'text-emerald-400' }}">
                    {{ $devices->count() }} / {{ $max_devices }}
                </p>
                <p class="text-xs text-zinc-500 mt-0.5">جهاز مسجّل</p>
            </div>
            {{-- زر مسح الكل --}}
            @if($devices->count() > 0)
                <button id="resetAllBtn"
                    class="inline-flex items-center gap-2 rounded-xl border border-red-500/30 bg-red-950/30 px-4 py-2 text-sm text-red-400 hover:bg-red-950/60 transition">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    مسح جميع الأجهزة
                </button>
            @endif
        </div>
    </div>

    {{-- تنبيه الكامل --}}
    @if($devices->count() >= $max_devices)
        <div class="mb-5 rounded-2xl border border-amber-500/30 bg-amber-950/20 px-4 py-3 text-sm text-amber-300 flex items-center gap-3">
            <svg class="h-5 w-5 shrink-0 text-amber-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>
            <span>وصل المستخدم للحد الأقصى ({{ $max_devices }} أجهزة). لن يتمكن من تسجيل الدخول من أي جهاز جديد حتى تحذف أحد الأجهزة.</span>
        </div>
    @endif

    {{-- جدول الأجهزة --}}
    <div class="overflow-hidden rounded-xl border" style="border-color: var(--border-subtle);" id="devicesTable">
        @if($devices->isEmpty())
            <div class="p-16 text-center text-zinc-500">
                <svg class="mx-auto h-12 w-12 mb-4 opacity-20" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/>
                </svg>
                <p class="text-sm">لا توجد أجهزة مسجّلة لهذا المستخدم</p>
                <p class="text-xs text-zinc-600 mt-1">سيُسجَّل الجهاز تلقائياً عند أول تسجيل دخول</p>
            </div>
        @else
            <table class="min-w-full divide-y text-sm" style="border-color: var(--border-subtle);">
                <thead class="text-right text-xs uppercase text-zinc-500"
                    style="background: var(--surface-raised);">
                    <tr>
                        <th class="px-5 py-3">#</th>
                        <th class="px-5 py-3">اسم الجهاز</th>
                        <th class="px-5 py-3">معرّف الجهاز</th>
                        <th class="px-5 py-3">أول دخول</th>
                        <th class="px-5 py-3">آخر دخول</th>
                        <th class="px-5 py-3 text-center">حذف</th>
                    </tr>
                </thead>
                <tbody class="divide-y" style="border-color: var(--border-subtle);" id="devicesTableBody">
                    @foreach($devices as $i => $device)
                        <tr class="transition hover:bg-white/[0.02]" id="device-row-{{ $device->id }}">
                            <td class="px-5 py-4 text-zinc-500 text-xs">{{ $i + 1 }}</td>
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    {{-- أيقونة الجهاز --}}
                                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg"
                                        style="background: var(--surface-raised); border: 1px solid var(--border-subtle);">
                                        @if(str_contains(strtolower($device->device_name ?? ''), 'ios') || str_contains(strtolower($device->device_name ?? ''), 'iphone') || str_contains(strtolower($device->device_name ?? ''), 'ipad'))
                                            <svg class="h-5 w-5 text-zinc-300" fill="currentColor" viewBox="0 0 24 24"><path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.8-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z"/></svg>
                                        @elseif(str_contains(strtolower($device->device_name ?? ''), 'android'))
                                            <svg class="h-5 w-5 text-emerald-400" fill="currentColor" viewBox="0 0 24 24"><path d="M17.523 15.341c-.3 0-.546-.246-.546-.546V9.205c0-.301.246-.546.546-.546.3 0 .546.245.546.546v5.59c0 .3-.246.546-.546.546zm-11.046 0c-.3 0-.546-.246-.546-.546V9.205c0-.301.246-.546.546-.546.3 0 .546.245.546.546v5.59c0 .3-.246.546-.546.546zm1.909-9.728L7.24 4.31a.268.268 0 01.381-.38l1.192 1.192A5.6 5.6 0 0112 4.547c.77 0 1.5.155 2.167.427L15.38 3.93a.269.269 0 01.381.381l-1.146 1.147A5.515 5.515 0 0117.09 8.5H6.91a5.515 5.515 0 012.476-2.887zM10.182 7a.545.545 0 110-1.09.545.545 0 010 1.09zm3.636 0a.545.545 0 110-1.09.545.545 0 010 1.09zM6.477 9.59h11.046v6.682a1.09 1.09 0 01-1.09 1.09h-.546v2.092a.818.818 0 11-1.637 0v-2.091H9.75v2.091a.818.818 0 11-1.636 0v-2.091h-.546a1.09 1.09 0 01-1.09-1.09V9.59z"/></svg>
                                        @elseif(str_contains(strtolower($device->device_name ?? ''), 'windows'))
                                            <svg class="h-5 w-5 text-sky-400" fill="currentColor" viewBox="0 0 24 24"><path d="M0 3.449L9.75 2.1v9.451H0m10.949-9.602L24 0v11.4H10.949M0 12.6h9.75v9.451L0 20.699M10.949 12.6H24V24l-12.9-1.801"/></svg>
                                        @else
                                            <svg class="h-5 w-5 text-zinc-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3"/></svg>
                                        @endif
                                    </div>
                                    <span class="font-medium text-white">{{ $device->device_name ?: 'جهاز غير معروف' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4">
                                <code class="rounded-md px-2 py-1 text-xs font-mono text-zinc-400"
                                    style="background: var(--surface-raised);">
                                    {{ Str::limit($device->device_fingerprint, 20) }}
                                </code>
                            </td>
                            <td class="px-5 py-4 text-zinc-400 text-xs">
                                {{ $device->first_seen_at?->format('Y/m/d H:i') ?? '—' }}
                            </td>
                            <td class="px-5 py-4 text-zinc-300 text-xs">
                                @if($device->last_login_at)
                                    <span title="{{ $device->last_login_at->format('Y/m/d H:i:s') }}">
                                        {{ $device->last_login_at->diffForHumans() }}
                                    </span>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <button
                                    onclick="deleteDevice({{ $user->id }}, {{ $device->id }})"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-red-500/20 bg-red-950/20 text-red-400 hover:bg-red-950/50 transition"
                                    title="حذف هذا الجهاز">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round"
                                            d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- إرشادات --}}
    <div class="mt-6 rounded-2xl border px-5 py-4 text-sm text-zinc-500"
        style="border-color: var(--border-subtle); background: var(--surface-raised);">
        <p class="font-medium text-zinc-400 mb-2">📋 ملاحظات</p>
        <ul class="space-y-1 list-disc list-inside">
            <li>كل مستخدم مسموح له بتسجيل الدخول من <strong class="text-zinc-300">{{ $max_devices }} أجهزة</strong> فقط بشكل دائم.</li>
            <li>لما جهاز يُحذف، يُفرَج مكانه ويقدر المستخدم يسجّل من جهاز جديد.</li>
            <li>الجهاز يُسجَّل تلقائياً عند أول تسجيل دخول من نفس المتصفح.</li>
            <li>حذف الجهاز <strong class="text-zinc-300">لا يلغي</strong> الجلسة الحالية — لو عاوز تقطع الجلسة احذف التوكن من إعدادات المستخدم.</li>
        </ul>
    </div>

@endsection

@push('scripts')
<script>
    // نستخدم fetch مباشرة بدل axios عشان axios عنده baseURL=/api
    const deviceBaseUrl = '{{ url("/dashboard/users/{$user->id}/devices") }}';
    const csrfToken     = document.querySelector('meta[name="csrf-token"]').content;

    async function apiFetch(url, method) {
        const res = await fetch(url, {
            method: method || 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept':       'application/json',
                'Content-Type': 'application/json',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            const data = await res.json().catch(function() { return {}; });
            throw new Error(data.message || 'HTTP ' + res.status);
        }

        return res.json().catch(function() { return {}; });
    }

    // تعريف صريح على window عشان onclick inline يلاقيها
    window.deleteDevice = function(userId, deviceId) {
        if (!confirm('حذف هذا الجهاز؟ سيتمكن المستخدم من تسجيل الدخول من جهاز جديد بدلاً منه.')) return;

        apiFetch(deviceBaseUrl + '/' + deviceId)
            .then(function(result) {
                var row = document.getElementById('device-row-' + deviceId);
                if (row) row.remove();
                updateCounter(-1);
                showToast('تم حذف الجهاز وإلغاء جلسته', 'success');
            })
            .catch(function(err) {
                showToast(err.message || 'فشل حذف الجهاز', 'error');
            });
    };

    var resetBtn = document.getElementById('resetAllBtn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            if (!confirm('حذف جميع الأجهزة المسجلة؟ سيحتاج المستخدم لتسجيل الدخول من جديد.')) return;

            apiFetch(deviceBaseUrl)
                .then(function(res) {
                    document.querySelectorAll('[id^="device-row-"]').forEach(function(r) { r.remove(); });

                    var tbody = document.getElementById('devicesTableBody');
                    if (tbody) {
                        var tableDiv = tbody.closest('div');
                        if (tableDiv) {
                            tableDiv.innerHTML = '<div class="p-16 text-center text-zinc-500"><p class="text-sm">تم مسح جميع الأجهزة المسجلة</p></div>';
                        }
                    }

                    resetBtn.style.display = 'none';

                    var counter = document.querySelector('.text-2xl');
                    if (counter) {
                        counter.textContent = '0 / {{ $max_devices }}';
                        counter.classList.remove('text-rose-400', 'text-emerald-400');
                        counter.classList.add('text-emerald-400');
                    }

                    showToast('تم حذف ' + (res.deleted_count || '') + ' جهاز وإلغاء جلساتهم', 'success');
                })
                .catch(function(err) {
                    showToast(err.message || 'فشل مسح الأجهزة', 'error');
                });
        });
    }

    function updateCounter(delta) {
        var counter = document.querySelector('.text-2xl');
        if (!counter) return;

        var parts   = counter.textContent.trim().split('/');
        var current = Math.max(0, parseInt(parts[0]) + delta);
        var max     = parseInt(parts[1]) || {{ $max_devices }};
        counter.textContent = current + ' / ' + max;
        counter.classList.remove('text-rose-400', 'text-emerald-400');
        counter.classList.add(current >= max ? 'text-rose-400' : 'text-emerald-400');
    }

    function showToast(message, type) {
        var existing = document.getElementById('devToast');
        if (existing) existing.remove();

        var toast  = document.createElement('div');
        toast.id   = 'devToast';
        var bg     = type === 'success' ? 'rgba(5,46,22,0.95)'  : 'rgba(69,10,10,0.95)';
        var border = type === 'success' ? 'rgba(34,197,94,0.3)' : 'rgba(239,68,68,0.3)';
        var color  = type === 'success' ? '#4ade80'             : '#f87171';

        toast.style.cssText = 'position:fixed;bottom:1.5rem;left:50%;transform:translateX(-50%);background:' + bg + ';border:1px solid ' + border + ';color:' + color + ';padding:0.75rem 1.5rem;border-radius:12px;font-size:0.875rem;z-index:9999;box-shadow:0 10px 25px rgba(0,0,0,0.5);white-space:nowrap;';
        toast.textContent = message;
        document.body.appendChild(toast);
        setTimeout(function() { toast.remove(); }, 3500);
    }
</script>
@endpush
