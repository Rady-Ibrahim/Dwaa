<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubscriptionValid
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'غير مصرح'], Response::HTTP_UNAUTHORIZED);
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        if (! $user->subscription_expires_at || $user->subscription_expires_at->isPast()) {
            return response()->json([
                'message' => 'انتهت صلاحية الاشتراك',
                'expired_at' => $user->subscription_expires_at,
            ], 402);
        }

        return $next($request);
    }
}
