<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TokenLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_reuses_the_same_replacement_for_concurrent_tabs(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ]);

        $oldToken = $user->createToken('test-device')->plainTextToken;

        $first = $this->withToken($oldToken)->postJson('/api/refresh');
        $first->assertOk();

        $second = $this->withToken($oldToken)->postJson('/api/refresh');
        $second->assertOk();

        $this->assertSame($first->json('token'), $second->json('token'));
        $this->assertSame(2, $user->tokens()->count());
    }

    public function test_login_links_token_to_device_and_enforces_the_device_limit(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
            'max_devices' => 1,
        ]);

        $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password',
            'device_name' => 'First Phone',
            'device_fingerprint' => 'first-fingerprint',
        ])->assertOk();

        $device = $user->devices()->firstOrFail();
        $token = $user->tokens()->firstOrFail();

        $this->assertSame($device->id, $token->user_device_id);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $token->id,
            'user_device_id' => $device->id,
        ]);

        $this->postJson('/api/login', [
            'phone' => $user->phone,
            'password' => 'password',
            'device_name' => 'Second Phone',
            'device_fingerprint' => 'second-fingerprint',
        ])->assertStatus(422);

        $this->assertSame(1, $user->devices()->count());
    }

    public function test_device_revocation_deletes_that_device_and_legacy_tokens(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
            'subscription_expires_at' => now()->addYear(),
        ]);

        $device = UserDevice::query()->create([
            'user_id' => $user->id,
            'device_fingerprint' => 'device-fingerprint',
            'device_name' => 'Test Device',
            'first_seen_at' => now(),
            'last_login_at' => now(),
        ]);

        $linked = $user->createToken('linked-device');
        $linked->accessToken->forceFill(['user_device_id' => $device->id])->save();

        $otherDevice = UserDevice::query()->create([
            'user_id' => $user->id,
            'device_fingerprint' => 'other-fingerprint',
            'device_name' => 'Other Device',
            'first_seen_at' => now(),
            'last_login_at' => now(),
        ]);

        $other = $user->createToken('other-device');
        $other->accessToken->forceFill(['user_device_id' => $otherDevice->id])->save();

        $legacy = $user->createToken('legacy-device');

        Sanctum::actingAs($admin);

        $this->deleteJson("/api/admin/users/{$user->id}/devices/{$device->id}")
            ->assertOk()
            ->assertJsonPath('revoked_tokens', 2);

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $linked->accessToken->id,
        ]);
        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $legacy->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $other->accessToken->id,
        ]);
        $this->assertDatabaseMissing('user_devices', [
            'id' => $device->id,
        ]);
        $this->assertDatabaseHas('user_devices', [
            'id' => $otherDevice->id,
        ]);
    }

    public function test_prune_command_deletes_only_inactive_tokens(): void
    {
        $user = User::factory()->create([
            'role' => User::ROLE_CLIENT,
            'is_active' => true,
        ]);

        $oldToken = $user->createToken('old-device');
        DB::table('personal_access_tokens')
            ->where('id', $oldToken->accessToken->id)
            ->update([
                'created_at' => now()->subDays(40),
                'updated_at' => now()->subDays(40),
                'last_used_at' => null,
            ]);

        $recentToken = $user->createToken('recent-device');

        $this->artisan('sanctum:prune-tokens', ['--dry-run' => true])
            ->expectsOutput('Would delete 1 personal access tokens.')
            ->assertSuccessful();

        $this->artisan('sanctum:prune-tokens')
            ->expectsOutput('Deleted 1 personal access tokens.')
            ->assertSuccessful();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $oldToken->accessToken->id,
        ]);
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $recentToken->accessToken->id,
        ]);
    }
}
