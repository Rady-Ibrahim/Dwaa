<?php

namespace App\Support;

final class SearchCache
{
    private const VERSION_KEY = 'search:cache_version';

    public static function version(): int
    {
        return (int) SafeCache::get(self::VERSION_KEY, 1);
    }

    public static function invalidate(): void
    {
        SafeCache::forever(self::VERSION_KEY, self::version() + 1);
    }
}
