<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\SeedsAuthContext;
use Tests\TestCase;

/**
 * The login / OTP / SSO flows feed the system audit trail (spec 028). Successful auth
 * issues a Passport token (personal-access client / keys not set up in the test env),
 * so the covered cases here are the failure paths, which short-circuit before token
 * issuance.
 */
class AuthAuditTest extends TestCase
{
    use RefreshDatabase;
    use SeedsAuthContext;

    private function makeUserWithPassword(): User
    {
        $businessId = $this->makeBusinessId();

        return $this->makeUser(false, $this->makeRoleId($businessId), $businessId, [
            'username' => 'jdoe_'.uniqid(),
        ]);
    }

    public function test_failed_login_wrong_password_is_audited(): void
    {
        $user = $this->makeUserWithPassword();

        $this->postJson('/api/auth/login', [
            'username' => $user->username,
            'password' => 'definitely-wrong',
        ], ['device-code' => 'test-device'])
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'system',
            'entity' => 'User',
            'entity_id' => $user->id,
            'action' => 'login',
            'status' => 'failed',
        ]);
    }

    public function test_failed_login_unknown_user_is_audited(): void
    {
        $this->postJson('/api/auth/login', [
            'username' => 'no-such-user',
            'password' => 'whatever',
        ], ['device-code' => 'test-device'])
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'system',
            'entity' => 'User',
            'action' => 'login',
            'status' => 'failed',
            'entity_id' => null,
        ]);
    }

    public function test_login_requires_device_code(): void
    {
        $this->postJson('/api/auth/login', ['username' => 'x', 'password' => 'y'])
            ->assertJsonPath('code', 422);
    }

    public function test_failed_otp_unknown_user_is_audited(): void
    {
        $this->postJson('/api/auth/verify-otp', ['user_id' => 999999, 'otp_code' => '123456'])
            ->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'system',
            'entity' => 'User',
            'action' => 'login',
            'status' => 'failed',
            'entity_id' => null,
        ]);
        $this->assertStringContainsString('OTP', (string) DB::table('sys_activity_logs')
            ->where('action', 'login')->where('status', 'failed')->value('description'));
    }

    public function test_failed_sso_missing_access_id_is_audited(): void
    {
        // 16-byte IV (32 hex chars) so decryption fails quietly (null) and reaches the
        // missing-access_id branch, rather than emitting a short-IV warning.
        $this->postJson('/api/auth/check-auth', [
            'salt' => '00',
            'iv' => str_repeat('0', 32),
            'da' => '00',
        ])->assertJsonPath('success', false);

        $this->assertDatabaseHas('sys_activity_logs', [
            'module' => 'system',
            'entity' => 'User',
            'action' => 'login',
            'status' => 'failed',
            'entity_id' => null,
        ]);
        $this->assertStringContainsString('SSO', (string) DB::table('sys_activity_logs')
            ->where('action', 'login')->where('status', 'failed')->value('description'));
    }
}
