<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\PersonalAccessTokenSchema;
use Illuminate\Support\Facades\DB;

class UserDevicesController extends Controller
{
    /**
     * GET /api/admin/users/{user}/devices
     * جلب أجهزة مستخدم معين
     */
    public function index(User $user)
    {
        $devices = UserDevice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_login_at')
            ->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
            ],
            'devices' => $devices,
            'devices_count' => $devices->count(),
            'max_devices' => $user->max_devices ?? 5,
        ]);
    }

    /**
     * DELETE /api/admin/users/{user}/devices/{device}
     * حذف جهاز معين من مستخدم
     */
    public function destroy(User $user, UserDevice $device)
    {
        // تأكد إن الجهاز فعلاً بيخص هذا المستخدم
        if ($device->user_id !== $user->id) {
            return response()->json(['message' => 'الجهاز لا ينتمي لهذا المستخدم'], 403);
        }

        $revokedTokens = 0;

        if (PersonalAccessTokenSchema::hasDeviceLink()) {
            $revokedTokens = $user->tokens()
                ->where('user_device_id', $device->id)
                ->delete();

            // Tokens issued before the device-link migration cannot be
            // matched to a device, so revoke those legacy sessions too.
            $revokedTokens += $user->tokens()
                ->whereNull('user_device_id')
                ->delete();
        } else {
            // Without the device link we cannot identify this device's token,
            // so revoke all user tokens rather than leave a live session.
            $revokedTokens = $user->tokens()->delete();
        }

        $device->delete();

        return response()->json([
            'message' => $revokedTokens > 0
                ? 'تم حذف الجهاز وإلغاء جلسته'
                : 'تم حذف الجهاز بنجاح',
            'revoked_tokens' => $revokedTokens,
        ]);
    }

    /**
     * DELETE /api/admin/users/{user}/devices
     * حذف جميع أجهزة مستخدم (reset كامل)
     */
    public function destroyAll(User $user)
    {
        $count = DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            return UserDevice::query()->where('user_id', $user->id)->delete();
        });

        return response()->json([
            'message' => 'تم حذف جميع الأجهزة وإلغاء كل الجلسات',
            'deleted_count' => $count,
        ]);
    }
}
