<?php

namespace App\Support;

use Closure;
use DateInterval;
use DateTimeInterface;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

final class SafeCache
{
    public static function get(string $key, mixed $default = null): mixed
    {
        try {
            return Cache::get($key, $default);
        } catch (Throwable $e) {
            self::report('get', $key, $e);

            return $default;
        }
    }

    public static function put(string $key, mixed $value, DateTimeInterface|DateInterval|int $ttl): bool
    {
        try {
            return Cache::put($key, $value, $ttl);
        } catch (Throwable $e) {
            self::report('put', $key, $e);

            return false;
        }
    }

    public static function forever(string $key, mixed $value): bool
    {
        try {
            return Cache::forever($key, $value);
        } catch (Throwable $e) {
            self::report('forever', $key, $e);

            return false;
        }
    }

    public static function forget(string $key): bool
    {
        try {
            return Cache::forget($key);
        } catch (Throwable $e) {
            self::report('forget', $key, $e);

            return false;
        }
    }

    public static function lock(string $key, int $seconds): ?Lock
    {
        try {
            return Cache::lock($key, $seconds);
        } catch (Throwable $e) {
            self::report('lock', $key, $e);

            return null;
        }
    }

    public static function release(?Lock $lock, string $key): void
    {
        if ($lock === null) {
            return;
        }

        try {
            $lock->release();
        } catch (Throwable $e) {
            self::report('release', $key, $e);
        }
    }

    public static function remember(string $key, DateTimeInterface|DateInterval|int $ttl, Closure $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (Throwable $e) {
            self::report('remember', $key, $e);

            return $callback();
        }
    }

    private static function report(string $operation, string $key, Throwable $e): void
    {
        Log::warning('cache_operation_failed', [
            'operation' => $operation,
            'key' => $key,
            'message' => $e->getMessage(),
        ]);
    }
}
