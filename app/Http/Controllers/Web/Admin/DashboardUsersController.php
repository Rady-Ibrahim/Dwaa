<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class DashboardUsersController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:admin,client'],
            'is_active' => ['nullable', 'boolean'],
            'subscription_expires_at' => ['nullable', 'date'],
        ]);

        User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
            'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
        ]);

        return back()->with('status', 'تم إنشاء المستخدم.');
    }

    public function update(Request $request, User $user)
    {
        $rules = [
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,'.$user->id],
            'role' => ['sometimes', 'in:admin,client'],
            'is_active' => ['sometimes', 'boolean'],
            'subscription_expires_at' => ['nullable', 'date'],
        ];

        $data = $request->validate($rules);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $user->update($data);

        return back()->with('status', 'تم تحديث المستخدم.');
    }

    public function editPassword(User $user)
    {
        return view('dashboard.users-password', [
            'user' => $user,
        ]);
    }

    public function updatePassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $user->update([
            'password' => Hash::make($data['password']),
        ]);

        return redirect()
            ->route('dashboard.users')
            ->with('status', 'تم تحديث كلمة مرور المستخدم.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['user' => 'لا يمكن حذف حسابك الحالي.']);
        }

        $user->delete();

        return back()->with('status', 'تم حذف المستخدم.');
    }
}
