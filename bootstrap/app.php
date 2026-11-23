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
use Symfony\Component\HttpKernel\Exception\HttpException;

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

        $mysqlPrivilegeCodes = [1142, 1143, 1044, 1045];

        $requestContext = function (): array {
            $request = request();

            return [
                'app_host' => gethostname(),
                'client_ip' => $request?->ip(),
                'request_method' => $request?->method(),
                'request_path' => $request?->path(),
                'route_name' => $request?->route()?->getName(),
                'route_action' => $request?->route()?->getActionName(),
            ];
        };

        $utf8 = function (string $value): string {
            try {
                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            } catch (Throwable) {
            }

            return trim(@iconv('UTF-8', 'UTF-8//IGNORE', $value) ?: '');
        };

        $safeStatus = function (Throwable $e): int {
            if ($e instanceof AuthenticationException) {
                return 401;
            }
            if ($e instanceof ValidationException) {
                return (int) ($e->status ?: 422);
            }
            if ($e instanceof HttpException) {
                return $e->getStatusCode();
            }

            return 500;
        };

        $safeMessage = function (int $status): string {
            return match (true) {
                $status === 401 => 'غير مصرح',
                $status === 403 => 'غير مسموح بهذا الإجراء.',
                $status === 404 => 'الصفحة أو المورد غير موجود.',
                $status === 429 => 'محاولات كثيرة، يرجى المحاولة لاحقاً.',
                $status >= 500 => 'حدث خطأ غير متوقع. يرجى المحاولة لاحقاً.',
                default => 'تعذر إكمال الطلب.',
            };
        };

        $reportPrivilegeError = function (QueryException $e, int $code) use ($requestContext) {
            $connection = $e->getConnectionName();

            $context = array_merge([
                'mysql_error_code' => $code,
                'connection' => $connection,
                'connection_details' => $e->getConnectionDetails(),
                'configured_user' => config("database.connections.{$connection}.username"),
                'read_write_type' => $e->readWriteType,
                'sql' => $e->getSql(),
                'bindings' => $e->getBindings(),
            ], $requestContext());

            try {
                $session = (array) DB::connection($connection)->selectOne(
                    'SELECT CURRENT_USER() AS current_user_account, USER() AS login_user, DATABASE() AS current_db, @@hostname AS server_hostname, @@server_id AS server_id, CONNECTION_ID() AS connection_id'
                );
                $context['db_session'] = $session;
                $context['client_host'] = $session['login_user'] ?? null;
            } catch (Throwable $dbException) {
                $context['db_session_error'] = $dbException->getMessage();
            }

            Log::critical($code === 1142 ? 'mysql_1142_permission_denied' : 'mysql_privilege_error', $context);
        };

        $exceptions->reportable(function (QueryException $e) use ($reportPrivilegeError, $mysqlPrivilegeCodes) {
            $code = (int) ($e->errorInfo[1] ?? 0);

            if (! in_array($code, $mysqlPrivilegeCodes, true)) {
                return;
            }

            try {
                $reportPrivilegeError($e, $code);
            } catch (Throwable) {
            }

            return false;
        });

        $exceptions->reportable(function (PDOException $e) use ($mysqlPrivilegeCodes, $requestContext) {
            if ($e instanceof QueryException) {
                return;
            }

            $code = (int) ($e->errorInfo[1] ?? 0);

            if (! in_array($code, $mysqlPrivilegeCodes, true)) {
                return;
            }

            try {
                Log::critical('mysql_privilege_error', array_merge([
                    'mysql_error_code' => $code,
                    'message' => $e->getMessage(),
                ], $requestContext()));
            } catch (Throwable) {
            }

            return false;
        });

        $exceptions->renderable(function (Throwable $e, Request $request) use ($safeStatus, $safeMessage, $utf8) {
            if (! ($request->is('api/*') || $request->expectsJson())) {
                return null;
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }

            $status = $safeStatus($e);

            if ($e instanceof ValidationException) {
                return response()->json([
                    'status' => 'error',
                    'message' => $utf8($e->getMessage()),
                    'errors' => $e->errors(),
                ], $status, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
            }

            return response()->json([
                'status' => 'error',
                'message' => $safeMessage($status),
            ], $status, [], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
        });

        $exceptions->renderable(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'غير مصرح'], 401);
            }

            return redirect()->route('admin.login');
        });

        $exceptions->renderable(function (Throwable $e, Request $request) use ($safeStatus, $safeMessage) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return null;
            }

            $status = $safeStatus($e);

            if ($status < 500) {
                return null;
            }

            return response()->view('errors.safe', ['message' => $safeMessage($status)], $status);
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
        $schedule->command('offers:prune-expired --chunk=1000 --sleep=100 --days=1')
            ->dailyAt('03:00')
            ->withoutOverlapping();
        $schedule->command('sanctum:prune-tokens')->dailyAt('03:15')->withoutOverlapping();
    })
    ->create();
