<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\PersonalAccessToken;
use Throwable;

final class PersonalAccessTokenSchema
{
    private static ?bool $hasDeviceLink = null;

    public static function hasDeviceLink(): bool
    {
        if (self::$hasDeviceLink !== null) {
            return self::$hasDeviceLink;
        }

        try {
            self::$hasDeviceLink = Schema::hasColumn('personal_access_tokens', 'user_device_id');
        } catch (Throwable $e) {
            Log::warning('personal_access_tokens_schema_check_failed', [
                'message' => $e->getMessage(),
            ]);
            self::$hasDeviceLink = true;
        }

        return self::$hasDeviceLink;
    }

    public static function linkToken(PersonalAccessToken $token, ?int $deviceId): void
    {
        if (! self::hasDeviceLink()) {
            return;
        }

        try {
            $token->forceFill(['user_device_id' => $deviceId])->save();
        } catch (Throwable $e) {
            Log::warning('unable_to_link_token_to_device', [
                'token_id' => $token->getKey(),
                'message' => $e->getMessage(),
            ]);
        }
    }

    public static function reset(): void
    {
        self::$hasDeviceLink = null;
    }
}
