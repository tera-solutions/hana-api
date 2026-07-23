<?php

namespace App\Modules\System\SocialAuth\Services;

use App\Modules\System\SocialAuth\Contracts\IdTokenVerifierInterface;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Verifies a Microsoft Entra ID (Azure AD) id_token (JWT) server-side against
 * Microsoft's published JWKS — signature, audience and expiry. `tenant`
 * defaults to `common` (personal + work/school accounts); the issuer is only
 * checked to be a `login.microsoftonline.com/{tenant-guid}/v2.0` shape since
 * the actual tenant GUID varies per signer even when we requested `common`.
 */
class MicrosoftIdTokenVerifier implements IdTokenVerifierInterface
{
    public function verify(string $idToken): array
    {
        $clientId = config('services.microsoft.client_id');

        if (! $clientId) {
            throw new \RuntimeException('Đăng nhập Microsoft chưa được cấu hình.');
        }

        try {
            $claims = (array) JWT::decode($idToken, JWK::parseKeySet($this->fetchSigningKeys()));
        } catch (\Throwable $e) {
            throw new \RuntimeException('Microsoft token không hợp lệ hoặc đã hết hạn.');
        }

        if (($claims['aud'] ?? null) !== $clientId) {
            throw new \RuntimeException('Microsoft token không hợp lệ hoặc đã hết hạn.');
        }

        if (! preg_match('#^https://login\.microsoftonline\.com/[^/]+/v2\.0$#', $claims['iss'] ?? '')) {
            throw new \RuntimeException('Microsoft token không hợp lệ hoặc đã hết hạn.');
        }

        $email = $claims['email'] ?? $claims['preferred_username'] ?? null;

        if (! $email) {
            throw new \RuntimeException('Tài khoản Microsoft không có email.');
        }

        return [
            'email' => $email,
            'name' => $claims['name'] ?? null,
            // Entra ID tokens carry no profile-picture claim (would need a
            // separate Graph API call with an access token, not just id_token).
            'avatar' => null,
        ];
    }

    private function fetchSigningKeys(): array
    {
        $tenant = config('services.microsoft.tenant', 'common');

        return Cache::remember("oidc_keys:microsoft:{$tenant}", now()->addHours(12), function () use ($tenant) {
            $discovery = Http::get("https://login.microsoftonline.com/{$tenant}/v2.0/.well-known/openid-configuration")
                ->throw()
                ->json();

            return Http::get($discovery['jwks_uri'])->throw()->json();
        });
    }
}
