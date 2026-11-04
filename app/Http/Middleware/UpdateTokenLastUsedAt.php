<?php

namespace App\Http\Middleware;

use App\Support\TokenUsage;
use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class UpdateTokenLastUsedAt
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();

        if ($token instanceof PersonalAccessToken) {
            TokenUsage::touch($token);
        }

        return $next($request);
    }
}
