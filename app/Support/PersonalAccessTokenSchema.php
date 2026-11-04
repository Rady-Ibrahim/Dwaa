<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

final class PersonalAccessTokenSchema
{
    public static function hasDeviceLink(): bool
    {
        static $hasDeviceLink;

        return $hasDeviceLink ??= Schema::hasColumn('personal_access_tokens', 'user_device_id');
    }
}
