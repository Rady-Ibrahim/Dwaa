<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Laravel\Sanctum\PersonalAccessToken;

final class TokenUsage
{
    private const UPDATE_INTERVAL_MINUTES = 5;

    public static function touch(PersonalAccessToken $token): void
    {
        if (! $token->exists) {
            return;
        }

        $lastUsedAt = $token->last_used_at;

        if ($lastUsedAt instanceof \DateTimeInterface
            && $lastUsedAt->gt(now()->subMinutes(self::UPDATE_INTERVAL_MINUTES))) {
            return;
        }

        try {
            $token->forceFill(['last_used_at' => now()])->saveQuietly();
        } catch (\Throwable $e) {
            Log::warning('Unable to update token last_used_at', [
                'token_id' => $token->getKey(),
                'message' => $e->getMessage(),
            ]);
        }
    }
}
