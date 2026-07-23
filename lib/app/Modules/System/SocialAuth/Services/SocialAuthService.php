<?php

namespace App\Modules\System\SocialAuth\Services;

use App\Models\User;
use App\Modules\System\Onboarding\Services\OnboardingService;
use App\Modules\System\SocialAuth\Contracts\IdTokenVerifierInterface;
use Illuminate\Support\Str;

/**
 * Social login (Google / Microsoft): verifies the provider's id_token,
 * matches an existing account by its verified email, or self-provisions a
 * new business + owner account the same way `OnboardingService` does for a
 * manual sign-up (project decision: unmatched social emails auto-register,
 * matching this app's existing self-service onboarding model).
 *
 * Issues a Passport Personal Access Token rather than a password-grant token
 * — there's no plaintext password to exchange at `/oauth/token`, and PATs
 * are already configured for a 6-month expiry (`AuthServiceProvider`), so no
 * refresh token is needed: the FE just re-runs the provider's sign-in popup
 * once the PAT eventually expires.
 */
class SocialAuthService
{
    /** @var array<string, class-string<IdTokenVerifierInterface>> */
    private const VERIFIERS = [
        'google' => GoogleIdTokenVerifier::class,
        'microsoft' => MicrosoftIdTokenVerifier::class,
    ];

    public function __construct(private readonly OnboardingService $onboarding) {}

    /**
     * @throws \RuntimeException
     */
    public function login(string $provider, string $idToken): array
    {
        $verifierClass = self::VERIFIERS[$provider] ?? null;

        if (! $verifierClass) {
            throw new \RuntimeException('Nhà cung cấp đăng nhập không được hỗ trợ.');
        }

        $identity = app($verifierClass)->verify($idToken);

        $user = User::where('email', $identity['email'])->first();

        if (! $user) {
            $user = $this->provisionUser($identity);
        }

        if (! $user->is_active) {
            throw new \RuntimeException('Tài khoản đã ngưng hoạt động hoặc chưa được kích hoạt !');
        }

        return $this->issueToken($user);
    }

    private function provisionUser(array $identity): User
    {
        $name = $identity['name'] ?: Str::before($identity['email'], '@');

        $result = $this->onboarding->register([
            'full_name' => $name,
            'email' => $identity['email'],
            'phone' => null,
            'password' => Str::random(40),
            'avatar' => $identity['avatar'] ?? null,
            'school' => "Trung tâm của {$name}",
        ]);

        return $result['user'];
    }

    private function issueToken(User $user): array
    {
        $result = $user->createToken('social-login');

        return [
            'verify_auth' => $user->verify_auth ?? 0,
            'user' => $user,
            'token' => $result->accessToken,
            'refresh_token' => null,
            'expires_in' => $result->token->expires_at
                ? now()->diffInSeconds($result->token->expires_at, false)
                : null,
            'access_id' => null,
        ];
    }
}
