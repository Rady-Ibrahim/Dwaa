<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserAdminController extends Controller
{
    public function index(Request $request)
    {
        return User::query()
            ->orderByDesc('id')
            ->paginate(30);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:admin,client'],
            'is_active' => ['boolean'],
            'subscription_expires_at' => ['nullable', 'date'],
        ]);

        $user = User::query()->create([
            'name' => $data['name'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'is_active' => $data['is_active'] ?? true,
            'subscription_expires_at' => $data['subscription_expires_at'] ?? null,
        ]);

        return response()->json($user, 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'max:20', 'unique:users,phone,'.$user->id],
            'password' => ['nullable', Password::defaults()],
            'role' => ['sometimes', 'in:admin,client'],
            'is_active' => ['sometimes', 'boolean'],
            'subscription_expires_at' => ['nullable', 'date'],
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return response()->json($user->fresh());
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'لا يمكن حذف حسابك الحالي'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'تم الحذف']);
    }
}
