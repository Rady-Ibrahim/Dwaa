<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\MergeUnlabeledJsonBody::class,
        ]);

        $middleware->alias([
            'is_active' => \App\Http\Middleware\IsActiveUser::class,
            'subscription_valid' => \App\Http\Middleware\SubscriptionValid::class,
            'role' => \App\Http\Middleware\EnsureRole::class,
            'client.auth' => \App\Http\Middleware\ClientAuth::class,
            'auth' => \App\Http\Middleware\Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (\Illuminate\Http\Request $request, \Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Sanitize exception messages before json_encode to prevent
        // Malformed UTF-8 characters from crashing the JSON response layer.
        $exceptions->renderable(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $rawMessage = $e->getMessage();

            try {
                $cleanMessage = mb_convert_encoding((string) $rawMessage, 'UTF-8', 'UTF-8');
            } catch (\Throwable $t) {
                $cleanMessage = (string) $rawMessage;
            }

            $cleanMessage = @iconv('UTF-8', 'UTF-8//IGNORE', $cleanMessage) ?? '';
            $cleanMessage = trim($cleanMessage);
            $cleanMessage = $cleanMessage !== '' ? $cleanMessage : 'An unexpected server error occurred.';

            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

            return response()->json([
                'status'     => 'error',
                'message'    => $cleanMessage,
                'exception'  => get_class($e),
            ], $statusCode, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        });

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }
            
            return redirect()->route('admin.login');
        });

        $exceptions->reportable(function (\Throwable $e) {
            Log::error('Unhandled Exception', [
                'message' => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'code'    => $e->getCode(),
                'trace'   => $e->getTraceAsString(),
            ]);
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('offers:prune-expired')->daily();
    })
    ->create();
