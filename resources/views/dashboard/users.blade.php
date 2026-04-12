@extends('layouts.admin')

@section('title', 'المستخدمون')
@section('heading', 'المستخدمون')

@section('content')
    <div class="mb-8 rounded-xl border border-zinc-800 bg-zinc-900/40 p-6">
        <h3 class="mb-4 text-sm font-semibold text-white">إنشاء مستخدم</h3>
        <form method="POST" action="{{ route('dashboard.users.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @csrf
            <div>
                <label class="mb-1 block text-xs text-zinc-500">الاسم</label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">الهاتف</label>
                <input name="phone" value="{{ old('phone') }}" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm" dir="ltr">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">كلمة المرور</label>
                <input type="password" name="password" required class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">الدور</label>
                <select name="role" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
                    <option value="client">عميل (صيدلي)</option>
                    <option value="admin">مسؤول</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs text-zinc-500">انتهاء الاشتراك (اختياري)</label>
                <input type="datetime-local" name="subscription_expires_at" value="{{ old('subscription_expires_at') }}" class="w-full rounded-lg border border-zinc-700 bg-zinc-950 px-3 py-2 text-sm">
            </div>
            <div class="flex items-end gap-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex items-center gap-2 text-sm text-zinc-400">
                    <input type="checkbox" name="is_active" value="1" checked class="rounded border-zinc-600"> حساب مفعّل
                </label>
            </div>
            <div class="sm:col-span-2 lg:col-span-3">
                <button type="submit" class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-medium text-white hover:bg-teal-500">إنشاء</button>
            </div>
        </form>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-800">
        <table class="min-w-full divide-y divide-zinc-800 text-sm">
            <thead class="bg-zinc-900/80 text-right text-xs uppercase text-zinc-500">
                <tr>
                    <th class="px-4 py-3">الاسم</th>
                    <th class="px-4 py-3">الهاتف</th>
                    <th class="px-4 py-3">الدور</th>
                    <th class="px-4 py-3">الحالة</th>
                    <th class="px-4 py-3">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-800">
                @forelse ($users as $u)
                    <tr class="hover:bg-zinc-900/40">
                        <td class="px-4 py-3 font-medium text-white">{{ $u->name }}</td>
                        <td class="px-4 py-3 text-zinc-400" dir="ltr">{{ $u->phone }}</td>
                        <td class="px-4 py-3 text-zinc-400">{{ $u->role }}</td>
                        <td class="px-4 py-3">
                            @if ($u->is_active)
                                <span class="text-emerald-400">نشط</span>
                            @else
                                <span class="text-red-400">موقوف</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap gap-2">
                                <form method="POST" action="{{ route('dashboard.users.update', $u) }}" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="is_active" value="{{ $u->is_active ? '0' : '1' }}">
                                    <button type="submit" class="text-xs text-teal-400 hover:underline">{{ $u->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                                </form>
                                <form method="POST" action="{{ route('dashboard.users.update', $u) }}" class="flex items-center gap-1">
                                    @csrf
                                    @method('PUT')
                                    <input type="password" name="password" placeholder="سر جديد" class="w-28 rounded border border-zinc-700 bg-zinc-950 px-2 py-1 text-xs">
                                    <button type="submit" class="text-xs text-zinc-400 hover:text-white">حفظ</button>
                                </form>
                                @if ($u->id !== auth()->id())
                                    <form method="POST" action="{{ route('dashboard.users.destroy', $u) }}" onsubmit="return confirm('حذف المستخدم؟');" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-400 hover:underline">حذف</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-4 py-8 text-center text-zinc-500">لا يوجد مستخدمون.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $users->links() }}</div>
@endsection
