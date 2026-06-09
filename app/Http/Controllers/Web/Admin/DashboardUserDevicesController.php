<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;

class DashboardUserDevicesController extends Controller
{
    public function show(User $user)
    {
        $devices = UserDevice::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_login_at')
            ->get();

        return view('dashboard.user-devices', [
            'user'        => $user,
            'devices'     => $devices,
            'max_devices' => 5,
        ]);
    }
}
