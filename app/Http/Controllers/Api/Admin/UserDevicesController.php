<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

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
                'id'   => $user->id,
                'name' => $user->name,
            ],
            'devices'       => $devices,
            'devices_count' => $devices->count(),
            'max_devices'   => 5,
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

        $device->delete();

        return response()->json(['message' => 'تم حذف الجهاز بنجاح']);
    }

    /**
     * DELETE /api/admin/users/{user}/devices
     * حذف جميع أجهزة مستخدم (reset كامل)
     */
    public function destroyAll(User $user)
    {
        $count = UserDevice::query()->where('user_id', $user->id)->delete();

        return response()->json([
            'message'       => 'تم حذف جميع الأجهزة',
            'deleted_count' => $count,
        ]);
    }
}
