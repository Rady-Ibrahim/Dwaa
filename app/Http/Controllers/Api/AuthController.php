<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * الحد الأقصى لعدد الأجهزة المسجلة بشكل دائم لكل عميل.
     */
    private const MAX_REGISTERED_DEVICES = 5;

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
                'is_active' => $user->is_active,
                'subscription_expires_at' => $user->subscription_expires_at,
                'created_at' => $user->created_at,
            ],
        ]);
    }

    public function login(Request $request)
    {
        Log::info('API login attempt', [
            'phone' => $request->input('phone'),
            'device_name' => $request->input('device_name'),
            'path' => $request->path(),
        ]);

        $data = $request->validate([
            'phone'              => ['required', 'string'],
            'password'           => ['required', 'string'],
            'device_name'        => ['nullable', 'string', 'max:255'],
            // UUID يُنشأ في المتصفح ويُحفظ في localStorage
            'device_fingerprint' => ['nullable', 'string', 'max:64'],
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

        // ── نظام الأجهزة الثابتة (مشكلة 0 + 1) ─────────────────────────────
        $fingerprint = trim((string) ($data['device_fingerprint'] ?? ''));

        // لو مفيش fingerprint (متصفح قديم / طلب مباشر) نستخدم User-Agent كـ fallback
        if ($fingerprint === '') {
            $fingerprint = substr(md5($request->userAgent() . '_fallback'), 0, 32);
        }

        // هل الجهاز مسجّل مسبقاً لهذا المستخدم؟
        $existingDevice = UserDevice::query()
            ->where('user_id', $user->id)
            ->where('device_fingerprint', $fingerprint)
            ->first();

        if (! $existingDevice) {
            // جهاز جديد — هل وصلنا للحد الأقصى؟
            $registeredCount = UserDevice::query()->where('user_id', $user->id)->count();

            if ($registeredCount >= self::MAX_REGISTERED_DEVICES) {
                Log::warning('Login blocked — max registered devices reached', [
                    'user_id'           => $user->id,
                    'phone'             => $data['phone'],
                    'registered_devices' => $registeredCount,
                    'max_allowed'       => self::MAX_REGISTERED_DEVICES,
                    'fingerprint'       => $fingerprint,
                ]);

                throw ValidationException::withMessages([
                    'phone' => [
                        'تم الوصول للحد الأقصى للأجهزة المسجلة ('
                        . self::MAX_REGISTERED_DEVICES
                        . '). يرجى التواصل مع الإدارة لإدارة أجهزتك.',
                    ],
                ]);
            }

            // سجّل الجهاز الجديد
            $existingDevice = UserDevice::query()->create([
                'user_id'            => $user->id,
                'device_fingerprint' => $fingerprint,
                'device_name'        => $data['device_name'] ?? $this->guessDeviceName($request),
                'first_seen_at'      => now(),
                'last_login_at'      => now(),
            ]);

            Log::info('New device registered', [
                'user_id'    => $user->id,
                'device_id'  => $existingDevice->id,
                'fingerprint' => $fingerprint,
            ]);
        } else {
            // جهاز معروف — نحدّث آخر دخول فقط
            $existingDevice->update(['last_login_at' => now()]);
        }
        // ─────────────────────────────────────────────────────────────────────

        $deviceName = $data['device_name'] ?? 'api';
        $token = $user->createToken($deviceName)->plainTextToken;

        Log::info('API login successful', [
            'phone'           => $data['phone'],
            'user_id'         => $user->id,
            'device_id'       => $existingDevice->id,
            'device_name'     => $deviceName,
            'token_prefix'    => substr($token, 0, 20) . '...',
        ]);

        $response = response()->json([
            'token' => $token,
            'user'  => [
                'id'                     => $user->id,
                'name'                   => $user->name,
                'phone'                  => $user->phone,
                'role'                   => $user->role,
                'is_active'              => $user->is_active,
                'subscription_expires_at' => $user->subscription_expires_at,
                'created_at'             => $user->created_at,
            ],
        ]);

        return $response->cookie('client_token', $token, 1440, '/', null, false, false, false, 'lax');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'تم تسجيل الخروج']);
    }

    /**
     * تخمين اسم وصفي للجهاز من الـ User-Agent.
     */
    private function guessDeviceName(Request $request): string
    {
        $ua = (string) $request->userAgent();

        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) {
            return 'iOS Device';
        }
        if (str_contains($ua, 'Android')) {
            return 'Android Device';
        }
        if (str_contains($ua, 'Windows')) {
            return 'Windows PC';
        }
        if (str_contains($ua, 'Mac')) {
            return 'Mac';
        }
        if (str_contains($ua, 'Linux')) {
            return 'Linux PC';
        }

        return 'Unknown Device';
    }
}
