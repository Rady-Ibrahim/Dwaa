<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\PersonalAccessTokenSchema;
use App\Support\SafeCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    /**
     * القيمة الافتراضية لعدد الأجهزة المسجلة لكل عميل.
     */
    private const DEFAULT_MAX_DEVICES = 5;

    /**
     * مدة بقاء كوكي client_token على المتصفح (بالدقائق) — 14 يوم.
     */
    private const TOKEN_COOKIE_MINUTES = 60 * 24 * 14;

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
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:255'],
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
            $fingerprint = substr(md5($request->userAgent().'_fallback'), 0, 32);
        }

        $deviceName = $data['device_name'] ?? 'api';
        $deviceLabel = $data['device_name'] ?? $this->guessDeviceName($request);

        [$existingDevice, $newAccessToken, $isNewDevice] = DB::transaction(function () use ($user, $fingerprint, $data, $deviceName, $deviceLabel) {
            // Serialize device registration per user so concurrent logins
            // cannot both pass the max-device check.
            $lockedUser = User::query()->whereKey($user->id)->lockForUpdate()->first();

            if (! $lockedUser) {
                throw ValidationException::withMessages([
                    'phone' => ['تعذر العثور على الحساب، حاول مرة أخرى.'],
                ]);
            }

            $existingDevice = UserDevice::query()
                ->where('user_id', $lockedUser->id)
                ->where('device_fingerprint', $fingerprint)
                ->first();

            $isNewDevice = false;

            if ($existingDevice) {
                // جهاز معروف — نحدّث آخر دخول فقط
                $existingDevice->update(['last_login_at' => now()]);
            } else {
                // جهاز جديد — هل وصلنا للحد الأقصى؟
                $maxAllowed = $lockedUser->max_devices ?? self::DEFAULT_MAX_DEVICES;
                $registeredCount = UserDevice::query()->where('user_id', $lockedUser->id)->count();

                if ($registeredCount >= $maxAllowed) {
                    Log::warning('Login blocked — max registered devices reached', [
                        'user_id' => $lockedUser->id,
                        'phone' => $data['phone'],
                        'registered_devices' => $registeredCount,
                        'max_allowed' => $maxAllowed,
                        'fingerprint' => $fingerprint,
                    ]);

                    throw ValidationException::withMessages([
                        'phone' => [
                            'تم الوصول للحد الأقصى للأجهزة المسجلة ('
                            .$maxAllowed
                            .'). يرجى التواصل مع الإدارة لإدارة أجهزتك.',
                        ],
                    ]);
                }

                // سجّل الجهاز الجديد
                $existingDevice = UserDevice::query()->create([
                    'user_id' => $lockedUser->id,
                    'device_fingerprint' => $fingerprint,
                    'device_name' => $deviceLabel,
                    'first_seen_at' => now(),
                    'last_login_at' => now(),
                ]);

                $isNewDevice = true;
            }

            $newAccessToken = $user->createToken($deviceName);

            PersonalAccessTokenSchema::linkToken($newAccessToken->accessToken, (int) $existingDevice->id);

            return [$existingDevice, $newAccessToken, $isNewDevice];
        });

        if ($isNewDevice) {
            Log::info('New device registered', [
                'user_id' => $user->id,
                'device_id' => $existingDevice->id,
                'fingerprint' => $fingerprint,
            ]);
        }
        // ─────────────────────────────────────────────────────────────────────

        $token = $newAccessToken->plainTextToken;

        Log::info('API login successful', [
            'phone' => $data['phone'],
            'user_id' => $user->id,
            'device_id' => $existingDevice->id,
            'device_name' => $deviceName,
            'token_id' => $newAccessToken->accessToken->id,
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
                'created_at' => $user->created_at,
            ],
        ]);

        return $response->cookie($this->tokenCookie($token));
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()
            ->json(['message' => 'تم تسجيل الخروج'])
            ->withCookie(cookie()->forget('client_token'));
    }

    /**
     * تجديد التوكن تلقائياً: يُنشئ توكن جديد بدل القديم ويرجعه للواجهة.
     * يُستدعى من الواجهة عند انتهاء الجلسة أو بشكل استباقي.
     */
    public function refresh(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'غير مصرح'], Response::HTTP_UNAUTHORIZED);
        }

        $current = $user->currentAccessToken();

        if (! $current instanceof PersonalAccessToken || ! $current->exists) {
            return response()->json(['message' => 'غير مصرح'], Response::HTTP_UNAUTHORIZED);
        }

        $name = $current->name ?: 'web';
        $abilities = $current->abilities ?? ['*'];
        $cacheKey = 'sanctum.refresh.'.$current->id;

        $refreshed = DB::transaction(function () use ($current, $user, $name, $abilities, $cacheKey) {
            // Serialize concurrent refreshes of the same token across tabs.
            $locked = PersonalAccessToken::query()
                ->whereKey($current->id)
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                return null;
            }

            // A concurrent tab may have already rotated this token. Return
            // the same replacement instead of issuing another one.
            $cached = SafeCache::get($cacheKey);

            if (is_array($cached) && isset($cached['token']) && is_string($cached['token'])) {
                try {
                    return [
                        'token' => Crypt::decryptString($cached['token']),
                        'id' => $cached['id'] ?? $locked->id,
                    ];
                } catch (\Throwable) {
                    SafeCache::forget($cacheKey);
                }
            }

            $newAccessToken = $user->createToken($name, $abilities);

            PersonalAccessTokenSchema::linkToken(
                $newAccessToken->accessToken,
                $locked->user_device_id === null ? null : (int) $locked->user_device_id
            );

            // Keep the previous token valid briefly so in-flight requests
            // using it are not rejected mid-rotation.
            $locked->forceFill(['expires_at' => now()->addMinutes(2)])->save();

            $result = [
                'token' => $newAccessToken->plainTextToken,
                'id' => $newAccessToken->accessToken->id,
            ];

            SafeCache::put($cacheKey, [
                'token' => Crypt::encryptString($result['token']),
                'id' => $result['id'],
            ], now()->addMinutes(2));

            return $result;
        });

        if (! is_array($refreshed) || ! isset($refreshed['token'])) {
            return response()->json(['message' => 'غير مصرح'], Response::HTTP_UNAUTHORIZED);
        }

        Log::info('API token refreshed', [
            'user_id' => $user->id,
            'device' => $name,
            'token_id' => $refreshed['id'],
            'previous_token_id' => $current->id,
        ]);

        return response()
            ->json(['token' => $refreshed['token']])
            ->cookie($this->tokenCookie($refreshed['token']));
    }

    private function tokenCookie(string $token): Cookie
    {
        return cookie(
            'client_token',
            $token,
            self::TOKEN_COOKIE_MINUTES,
            '/',
            null,
            app()->environment('production'),
            false,
            false,
            'lax'
        );
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
