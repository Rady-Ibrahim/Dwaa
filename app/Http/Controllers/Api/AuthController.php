<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        Log::info('API login attempt', [
            'phone' => $request->input('phone'),
            'device_name' => $request->input('device_name'),
            'path' => $request->path(),
        ]);

        $data = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
        ]);

        $user = User::query()->where('phone', $data['phone'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            Log::warning('API login failed', [
                'phone' => $data['phone'],
                'reason' => 'invalid_credentials',
            ]);
            throw ValidationException::withMessages([
                'phone' => ['بيانات الدخول غير صحيحة'],
            ]);
        }

        if (! $user->isClient()) {
            Log::warning('API login blocked', [
                'phone' => $data['phone'],
                'reason' => 'non_client_role',
                'role' => $user->role,
            ]);
            throw ValidationException::withMessages([
                'phone' => ['تسجيل الدخول من هذه الصفحة متاح للصيادلة فقط'],
            ]);
        }

        $deviceName = $data['device_name'] ?? 'api';
        $token = $user->createToken($deviceName)->plainTextToken;
        Log::info('API login successful', [
            'phone' => $data['phone'],
            'user_id' => $user->id,
            'device_name' => $deviceName,
            'token_prefix' => substr($token, 0, 20) . '...',
        ]);

        $response = response()->json([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'subscription_expires_at' => $user->subscription_expires_at,
            ],
        ]);

        return $response->cookie('client_token', $token, 1440, '/', null, false, false, false, 'lax');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }
}
