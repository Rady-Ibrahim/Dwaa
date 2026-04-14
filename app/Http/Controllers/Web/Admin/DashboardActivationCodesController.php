<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardActivationCodesController extends Controller
{
    public function index()
    {
        return view('dashboard.activation-codes', [
            'codes' => ActivationCode::query()->orderByDesc('id')->paginate(20),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'duration_days' => ['required', 'integer', 'min:1'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'code' => ['nullable', 'string', 'max:64', 'unique:activation_codes,code'],
        ]);

        $code = $data['code'] ?? strtoupper(Str::random(12));

        ActivationCode::query()->create([
            'code' => $code,
            'duration_days' => $data['duration_days'],
            'max_uses' => $data['max_uses'],
            'used_count' => 0,
            'is_active' => true,
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        return back()->with('status', 'تم إنشاء الكود: '.$code);
    }

    public function update(Request $request, ActivationCode $activationCode)
    {
        $data = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'expires_at' => ['nullable', 'date'],
        ]);

        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $activationCode->update($data);

        if ($request->has('is_active') && ! $request->boolean('is_active')) {
            $affectedByCode = User::query()
                ->where('role', User::ROLE_CLIENT)
                ->where('activation_code', $activationCode->code)
                ->whereNotNull('subscription_expires_at')
                ->update([
                    'subscription_expires_at' => now()->subSecond(),
                ]);

            // Backward compatibility: older activated users may not have activation_code stored.
            if ($affectedByCode === 0) {
                User::query()
                    ->where('role', User::ROLE_CLIENT)
                    ->whereNull('activation_code')
                    ->whereNotNull('subscription_expires_at')
                    ->where('subscription_expires_at', '>', now())
                    ->update([
                        'subscription_expires_at' => now()->subSecond(),
                    ]);
            }
        }

        return back()->with('status', 'تم تحديث الكود.');
    }
}
