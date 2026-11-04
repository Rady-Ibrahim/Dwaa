<?php

use App\Http\Middleware\Authenticate;
use App\Http\Middleware\ClientAuth;
use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\IsActiveUser;
use App\Http\Middleware\MergeUnlabeledJsonBody;
use App\Http\Middleware\SubscriptionValid;
use App\Http\Middleware\UpdateTokenLastUsedAt;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            MergeUnlabeledJsonBody::class,
        ]);

        $middleware->alias([
            'is_active' => IsActiveUser::class,
            'subscription_valid' => SubscriptionValid::class,
            'role' => EnsureRole::class,
            'client.auth' => ClientAuth::class,
            'token.usage' => UpdateTokenLastUsedAt::class,
            'auth' => Authenticate::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e) {
            return $request->is('api/*') || $request->expectsJson();
        });

        // Capture the exact DB account/host and failing SQL whenever MySQL
        // denies table access (1142), which is otherwise hard to trace across
        // multiple app servers and MySQL host variants.
        $exceptions->reportable(function (QueryException $e) {
            if ((int) ($e->errorInfo[1] ?? 0) !== 1142) {
                return;
            }

            $connection = $e->getConnectionName();

            $context = [
                'app_host' => gethostname(),
                'connection' => $connection,
                'connection_details' => $e->getConnectionDetails(),
                'configured_user' => config("database.connections.{$connection}.username"),
                'read_write_type' => $e->readWriteType,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ];

            try {
                $context['db_session'] = (array) DB::connection($connection)->selectOne(
                    'SELECT CURRENT_USER() AS current_user_account, USER() AS login_user, DATABASE() AS current_db, @@hostname AS server_hostname, @@server_id AS server_id, CONNECTION_ID() AS connection_id'
                );
            } catch (Throwable $dbException) {
                $context['db_session_error'] = $dbException->getMessage();
            }

            Log::critical('mysql_1142_permission_denied', $context);
        });

        // Sanitize exception messages before json_encode to prevent
        // Malformed UTF-8 characters from crashing the JSON response layer.
        $exceptions->renderable(function (Throwable $e, Request $request) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            $rawMessage = $e->getMessage();

            try {
                $cleanMessage = mb_convert_encoding((string) $rawMessage, 'UTF-8', 'UTF-8');
            } catch (Throwable $t) {
                $cleanMessage = (string) $rawMessage;
            }

            $cleanMessage = @iconv('UTF-8', 'UTF-8//IGNORE', $cleanMessage) ?? '';
            $cleanMessage = trim($cleanMessage);
            $cleanMessage = $cleanMessage !== '' ? $cleanMessage : 'An unexpected server error occurred.';

            $statusCode = 500;

            if ($e instanceof ValidationException) {
                $statusCode = $e->status;
            } elseif (method_exists($e, 'getStatusCode')) {
                $statusCode = $e->getStatusCode();
            }

            $payload = [
                'status' => 'error',
                'message' => $cleanMessage,
                'exception' => get_class($e),
            ];

            if ($e instanceof ValidationException) {
                $payload['errors'] = $e->errors();
            }

            return response()->json(
                $payload,
                $statusCode,
                [],
                JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE
            );
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }

            return redirect()->route('admin.login');
        });

        $exceptions->reportable(function (Throwable $e) {
            Log::error('Unhandled Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('offers:prune-expired')->daily();
        $schedule->command('sanctum:prune-tokens')->dailyAt('03:15')->withoutOverlapping();
    })
    ->create();
