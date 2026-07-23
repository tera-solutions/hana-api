<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\System\Business\Models\Business;
use App\Modules\System\SocialAuth\Contracts\IdTokenVerifierInterface;
use App\Modules\System\SocialAuth\Services\GoogleIdTokenVerifier;
use App\Modules\System\SocialAuth\Services\MicrosoftIdTokenVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Swaps the real provider verifier for one that returns a fixed identity — no network calls in tests. */
    private function fakeVerifier(string $concreteClass, array $identity): void
    {
        $this->app->bind($concreteClass, fn () => new class($identity) implements IdTokenVerifierInterface
        {
            public function __construct(private array $identity) {}

            public function verify(string $idToken): array
            {
                return $this->identity;
            }
        });
    }

    /** Swaps the real provider verifier for one that always rejects the token. */
    private function failingVerifier(string $concreteClass, string $message): void
    {
        $this->app->bind($concreteClass, fn () => new class($message) implements IdTokenVerifierInterface
        {
            public function __construct(private string $message) {}

            public function verify(string $idToken): array
            {
                throw new \RuntimeException($this->message);
            }
        });
    }

    public function test_unmatched_email_provisions_a_new_business_and_user(): void
    {
        $email = 'new_'.uniqid().'@gmail.com';
        $this->fakeVerifier(GoogleIdTokenVerifier::class, [
            'email' => $email,
            'name' => 'Nguyen Van A',
            'avatar' => 'https://lh3.googleusercontent.com/a/avatar.jpg',
        ]);

        $response = $this->postJson('/api/auth/social-login', ['provider' => 'google', 'id_token' => 'fake-id-token'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', $email)
            ->assertJsonPath('data.refresh_token', null);

        $this->assertNotEmpty($response->json('data.token'));

        $user = User::where('email', $email)->firstOrFail();
        $this->assertTrue((bool) $user->is_admin);
        $this->assertTrue((bool) $user->is_active);
        $this->assertNotNull($user->business_id);

        $business = Business::findOrFail($user->business_id);
        $this->assertSame($user->id, $business->manager_id);
    }

    public function test_matched_email_logs_into_the_existing_account_without_provisioning(): void
    {
        $payload = [
            'full_name' => 'Existing Teacher',
            'email' => 'existing_'.uniqid().'@hana.edu.vn',
            'phone' => '09'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'Abc@1234',
            'password_confirmation' => 'Abc@1234',
            'school' => 'Existing Center',
        ];
        $this->postJson('/api/auth/register-school', $payload)->assertStatus(200);
        $existingUser = User::where('email', $payload['email'])->firstOrFail();
        $businessCountBefore = Business::count();

        $this->fakeVerifier(MicrosoftIdTokenVerifier::class, [
            'email' => $payload['email'],
            'name' => $payload['full_name'],
            'avatar' => null,
        ]);

        $this->postJson('/api/auth/social-login', ['provider' => 'microsoft', 'id_token' => 'fake-id-token'])
            ->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.id', $existingUser->id);

        $this->assertSame($businessCountBefore, Business::count());
    }

    public function test_suspended_account_cannot_social_login(): void
    {
        $payload = [
            'full_name' => 'Suspended Teacher',
            'email' => 'suspended_'.uniqid().'@hana.edu.vn',
            'phone' => '09'.str_pad((string) mt_rand(0, 99999999), 8, '0', STR_PAD_LEFT),
            'password' => 'Abc@1234',
            'password_confirmation' => 'Abc@1234',
            'school' => 'Suspended Center',
        ];
        $this->postJson('/api/auth/register-school', $payload)->assertStatus(200);
        User::where('email', $payload['email'])->update(['is_active' => false]);

        $this->fakeVerifier(GoogleIdTokenVerifier::class, [
            'email' => $payload['email'],
            'name' => $payload['full_name'],
            'avatar' => null,
        ]);

        $this->postJson('/api/auth/social-login', ['provider' => 'google', 'id_token' => 'fake-id-token'])
            ->assertStatus(200)
            ->assertJsonPath('success', false);
    }

    public function test_invalid_token_is_rejected(): void
    {
        $this->failingVerifier(GoogleIdTokenVerifier::class, 'Google token không hợp lệ hoặc đã hết hạn.');

        $this->postJson('/api/auth/social-login', ['provider' => 'google', 'id_token' => 'garbage'])
            ->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('msg', 'Google token không hợp lệ hoặc đã hết hạn.');
    }

    public function test_unsupported_provider_is_rejected(): void
    {
        $this->postJson('/api/auth/social-login', ['provider' => 'facebook', 'id_token' => 'x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['provider']);
    }

    public function test_missing_id_token_is_rejected(): void
    {
        $this->postJson('/api/auth/social-login', ['provider' => 'google'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['id_token']);
    }
}
