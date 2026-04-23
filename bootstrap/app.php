<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

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

        $exceptions->renderable(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }
            
            return redirect()->route('admin.login');
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('offers:prune-expired')->daily();
    })
    ->create();
