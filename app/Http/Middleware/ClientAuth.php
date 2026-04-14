<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ClientAuth
{
    private function extractTokenFromCookieHeader(?string $cookieHeader): ?string
    {
        if (! $cookieHeader) {
            return null;
        }

        foreach (explode(';', $cookieHeader) as $cookiePart) {
            [$name, $value] = array_pad(explode('=', trim($cookiePart), 2), 2, null);
            if ($name === 'client_token' && $value !== null && $value !== '') {
                return urldecode($value);
            }
        }

        return null;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $rawToken = $request->cookie('client_token');
        $token = is_string($rawToken) ? urldecode($rawToken) : null;
        $rawCookieHeader = $request->headers->get('cookie');

        if (! $token) {
            $token = $this->extractTokenFromCookieHeader($rawCookieHeader);
        }

        Log::info('ClientAuth middleware check', [
            'path' => $request->path(),
            'has_token' => $token ? true : false,
            'token' => $token ? substr($token, 0, 20) . '...' : null,
            'has_cookie_header' => (bool) $rawCookieHeader,
        ]);

        if (! $token) {
            Log::info('ClientAuth redirecting to login because token missing', [
                'path' => $request->path(),
            ]);
            return redirect()->route('client.login');
        }

        $accessToken = PersonalAccessToken::findToken($token);
        $tokenable = $accessToken?->tokenable;

        if (! $accessToken || ! $tokenable instanceof User || ! $tokenable->isClient()) {
            Log::info('ClientAuth redirecting to login because token invalid', [
                'path' => $request->path(),
            ]);

            return redirect()->route('client.login')->withCookie(cookie()->forget('client_token'));
        }

        return $next($request);
    }
}
