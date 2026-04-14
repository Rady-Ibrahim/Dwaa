<?php

namespace App\Services;

use App\Models\ActivationCode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ActivationService
{
    public function activate(User $user, string $code): User
    {
        $record = ActivationCode::query()
            ->where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $record) {
            throw ValidationException::withMessages([
                'code' => ['الكود غير صحيح أو غير مفعّل'],
            ]);
        }

        if ($record->expires_at && $record->expires_at->isPast()) {
            throw ValidationException::withMessages([
                'code' => ['انتهت صلاحية هذا الكود'],
            ]);
        }

        if ($record->used_count >= $record->max_uses) {
            throw ValidationException::withMessages([
                'code' => ['استُنفد عدد استخدامات هذا الكود'],
            ]);
        }

        $record->increment('used_count');

        $base = $user->subscription_expires_at && $user->subscription_expires_at->isFuture()
            ? $user->subscription_expires_at
            : now();

        $user->update([
            'subscription_expires_at' => $base->copy()->addDays($record->duration_days),
            'activation_code' => $record->code,
        ]);

        return $user->fresh();
    }
}
