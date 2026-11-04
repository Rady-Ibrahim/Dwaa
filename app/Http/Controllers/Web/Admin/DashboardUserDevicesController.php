<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use App\Support\PersonalAccessTokenSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        \Log::info('Device deleted successfully', [
            'device_id' => $device->id,
            'revoked_tokens' => $revokedTokens,
        ]);

        return response()->json([
            'message' => $revokedTokens > 0
                ? 'تم حذف الجهاز وإلغاء جلسته'
                : 'تم حذف الجهاز',
            'revoked_tokens' => $revokedTokens,
        ]);
    }

    public function destroyAll(User $user)
    {
        \Log::info('Device destroyAll called', ['user_id' => $user->id]);

        $count = DB::transaction(function () use ($user) {
            $user->tokens()->delete();

            return UserDevice::query()->where('user_id', $user->id)->delete();
        });

        \Log::info('All devices deleted', ['count' => $count]);

        return response()->json([
            'message' => 'تم حذف جميع الأجهزة وإلغاء كل الجلسات',
            'deleted_count' => $count,
        ]);
    }
}
