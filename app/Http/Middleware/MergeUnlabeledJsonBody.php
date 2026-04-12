<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MergeUnlabeledJsonBody
{
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->isJson()) {
            return $next($request);
        }

        if ($request->request->count() > 0) {
            return $next($request);
        }

        $ct = strtolower((string) $request->header('Content-Type', ''));
        if (str_contains($ct, 'multipart/form-data')) {
            return $next($request);
        }

        $raw = $request->getContent();
        if ($raw === '' || $raw === false) {
            return $next($request);
        }

        $data = json_decode($raw, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $request->merge($data);
        }

        return $next($request);
    }
}
