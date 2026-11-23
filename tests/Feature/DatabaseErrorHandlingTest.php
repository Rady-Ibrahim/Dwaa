<?php

namespace Tests\Feature;

use App\Jobs\ProcessUploadJob;
use App\Models\User;
use App\Support\PersonalAccessTokenSchema;
use App\Support\SafeCache;
use App\Support\SearchCache;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Mockery;
use PDOException;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class DatabaseErrorHandlingTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        PersonalAccessTokenSchema::reset();

        parent::tearDown();
    }

    public function test_mysql_1142_is_logged_in_detail_but_never_leaked_to_api_clients(): void
    {
        config(['app.debug' => true]);

        $this->registerFailingRoute('/api/__db_1142', 1142);

        Log::spy();

        $response = $this->getJson('/api/__db_1142');

        $response->assertStatus(500)
            ->assertJsonPath('status', 'error');

        $body = $response->getContent();
        $this->assertStringNotContainsString('SELECT', $body);
        $this->assertStringNotContainsString('secrets', $body);
        $this->assertStringNotContainsString('1142', $body);
        $this->assertStringNotContainsString('42000', $body);
        $this->assertStringNotContainsString('QueryException', $body);
        $this->assertStringNotContainsString('exception', $body);
        $this->assertStringNotContainsString('sqlite', $body);

        Log::shouldHaveReceived('critical')
            ->once()
            ->with('mysql_1142_permission_denied', Mockery::on(function (array $context) {
                return ($context['mysql_error_code'] ?? null) === 1142
                    && ($context['sql'] ?? null) === 'SELECT * FROM secrets WHERE id = ?'
                    && ($context['bindings'] ?? null) === [42]
                    && ($context['request_method'] ?? null) === 'GET'
                    && ($context['request_path'] ?? null) === 'api/__db_1142'
                    && (array_key_exists('db_session', $context) || array_key_exists('db_session_error', $context));
            }));
    }

    public function test_mysql_1142_is_not_shown_on_web_pages_even_with_debug_enabled(): void
    {
        config(['app.debug' => true]);

        $this->registerFailingRoute('/__db_1142_web', 1142);

        Log::spy();

        $response = $this->get('/__db_1142_web');

        $response->assertStatus(500);

        $body = $response->getContent();
        $this->assertStringNotContainsString('SELECT', $body);
        $this->assertStringNotContainsString('secrets', $body);
        $this->assertStringNotContainsString('1142', $body);
        $this->assertStringNotContainsString('QueryException', $body);
    }

    public function test_web_500_uses_safe_view_when_app_debug_is_disabled(): void
    {
        config(['app.debug' => false]);

        $this->registerFailingRoute('/__db_1142_web_prod', 1142);

        Log::spy();

        $this->get('/__db_1142_web_prod')
            ->assertStatus(500)
            ->assertSee('حدث خطأ غير متوقع', false)
            ->assertDontSee('secrets', false)
            ->assertDontSee('QueryException', false);
    }

    public function test_other_privilege_errors_are_logged_without_leaking_details(): void
    {
        $this->registerFailingRoute('/api/__db_privilege/{code}', null);

        Log::spy();

        foreach ([1143, 1044, 1045] as $code) {
            $this->getJson('/api/__db_privilege/'.$code)->assertStatus(500);
        }

        foreach ([1143, 1044, 1045] as $code) {
            Log::shouldHaveReceived('critical')
                ->with('mysql_privilege_error', Mockery::on(
                    fn (array $context) => ($context['mysql_error_code'] ?? null) === $code
                        && ($context['request_method'] ?? null) === 'GET'
                ));
        }
    }

    public function test_validation_errors_keep_their_status_and_error_bag(): void
    {
        Route::get('/api/__validation', fn () => throw ValidationException::withMessages([
            'field' => ['قيمة غير صالحة'],
        ]));

        $this->getJson('/api/__validation')
            ->assertStatus(422)
            ->assertJsonPath('errors.field.0', 'قيمة غير صالحة')
            ->assertJsonMissing(['exception' => 'Illuminate\Validation\ValidationException']);
    }

    public function test_refresh_survives_cache_failure_and_logs_it(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ]);
        $oldToken = $user->createToken('cache-failure-device')->plainTextToken;

        Log::spy();

        $this->mockThrowingCache();

        $response = $this->withToken($oldToken)->postJson('/api/refresh');

        $response->assertOk()->assertJsonStructure(['token']);
        $this->assertSame(2, $user->tokens()->count());

        Log::shouldHaveReceived('warning')
            ->with('cache_operation_failed', Mockery::on(
                fn (array $context) => ($context['operation'] ?? null) === 'get'
            ));
    }

    public function test_login_survives_schema_lookup_failure(): void
    {
        PersonalAccessTokenSchema::reset();

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ]);

        Log::spy();
        Schema::shouldReceive('hasColumn')
            ->andThrow(new RuntimeException('information_schema denied'));

        $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password',
            'device_name' => 'Schema Failure Phone',
            'device_fingerprint' => 'schema-failure-fingerprint',
        ])->assertOk()->assertJsonStructure(['token']);

        $this->assertSame(1, $user->tokens()->count());

        Log::shouldHaveReceived('warning')
            ->with('personal_access_tokens_schema_check_failed', Mockery::on(
                fn (array $context) => str_contains((string) ($context['message'] ?? ''), 'information_schema')
            ));
    }

    public function test_search_cache_version_and_invalidation_degrade_safely(): void
    {
        Log::spy();

        $this->mockThrowingCache();

        $this->assertSame(1, SearchCache::version());

        SearchCache::invalidate();

        Log::shouldHaveReceived('warning')
            ->with('cache_operation_failed', Mockery::on(
                fn (array $context) => ($context['operation'] ?? null) === 'forever'
            ));
    }

    public function test_safe_cache_remember_computes_fresh_value_when_cache_fails(): void
    {
        Log::spy();

        $this->mockThrowingCache();

        $value = SafeCache::remember('test:key', 60, fn () => 'computed');

        $this->assertSame('computed', $value);

        Log::shouldHaveReceived('warning')
            ->with('cache_operation_failed', Mockery::on(
                fn (array $context) => ($context['operation'] ?? null) === 'remember'
                    && ($context['key'] ?? null) === 'test:key'
            ));
    }

    public function test_database_queue_retry_after_exceeds_upload_job_timeout(): void
    {
        $timeout = (new ReflectionClass(ProcessUploadJob::class))->getDefaultProperties()['timeout'];

        $this->assertGreaterThan($timeout, config('queue.connections.database.retry_after'));
    }

    private function mockThrowingCache(): void
    {
        $repository = Cache::driver();

        Cache::shouldReceive('driver')->andReturn($repository);
        Cache::shouldReceive('get')->andThrow(new RuntimeException('cache unavailable'));
        Cache::shouldReceive('put')->andThrow(new RuntimeException('cache unavailable'));
        Cache::shouldReceive('forever')->andThrow(new RuntimeException('cache unavailable'));
        Cache::shouldReceive('forget')->andThrow(new RuntimeException('cache unavailable'));
        Cache::shouldReceive('remember')->andThrow(new RuntimeException('cache unavailable'));
    }

    private function registerFailingRoute(string $uri, ?int $fixedCode): void
    {
        Route::get($uri, function ($code = null) use ($fixedCode) {
            $mysqlCode = $fixedCode ?? (int) $code;

            $pdo = new PDOException(
                "SQLSTATE[42000]: Syntax error or access violation: {$mysqlCode} SELECT command denied to user 'app'@'localhost' for table 'secrets'"
            );
            $pdo->errorInfo = ['42000', $mysqlCode, 'SELECT command denied'];

            throw new QueryException(
                'sqlite',
                'SELECT * FROM secrets WHERE id = ?',
                [42],
                $pdo
            );
        });
    }
}
