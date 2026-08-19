<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\Request;

class DashboardUserDevicesController extends Controller
{
    public function show(User $user)
    {
        $devices = UserDevice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_login_at')
            ->get();

        return view('dashboard.user-devices', [
            'user' => $user,
            'devices' => $devices,
            'max_devices' => $user->max_devices ?? 5,
        ]);
    }

    public function updateMaxDevices(Request $request, User $user)
    {
        $validated = $request->validate([
            'max_devices' => ['required', 'integer', 'min:1', 'max:50'],
        ]);

        $user->update(['max_devices' => $validated['max_devices']]);

        return response()->json([
            'message' => 'تم تحديث الحد الأقصى للأجهزة',
            'max_devices' => $user->max_devices,
        ]);
    }

    public function destroy(Request $request, User $user, UserDevice $device)
    {
        \Log::info('Device destroy called', [
            'user_id' => $user->id,
            'device_id' => $device->id,
            'device_user_id' => $device->user_id,
        ]);

        if ($device->user_id !== $user->id) {
            \Log::warning('Device does not belong to user');

            return response()->json(['message' => 'الجهاز لا ينتمي لهذا المستخدم'], 403);
        }

        $user->tokens()->delete();
        $device->delete();

        \Log::info('Device deleted successfully', ['device_id' => $device->id]);

        return response()->json(['message' => 'تم حذف الجهاز وإلغاء جلسته']);
    }

    public function destroyAll(User $user)
    {
        \Log::info('Device destroyAll called', ['user_id' => $user->id]);

        $user->tokens()->delete();
        $count = UserDevice::query()->where('user_id', $user->id)->delete();

        \Log::info('All devices deleted', ['count' => $count]);

        return response()->json([
            'message' => 'تم حذف جميع الأجهزة وإلغاء كل الجلسات',
            'deleted_count' => $count,
        ]);
    }
}
