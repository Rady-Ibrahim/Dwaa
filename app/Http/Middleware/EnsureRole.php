<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();
        if (! $user || $user->role !== $role) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'غير مصرح لهذه العملية'], Response::HTTP_FORBIDDEN);
            }

            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
