<?php

namespace App\Modules\System\SocialAuth\Services;

use App\Modules\System\SocialAuth\Contracts\IdTokenVerifierInterface;
use Google_Client;

/**
 * Verifies a Google Identity Services id_token (JWT) server-side — signature,
 * audience (our OAuth client id) and expiry, per Google's own client library.
 * No client secret involved: id_token verification is a public-key check.
 */
class GoogleIdTokenVerifier implements IdTokenVerifierInterface
{
    public function verify(string $idToken): array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            throw new \RuntimeException('Đăng nhập Google chưa được cấu hình.');
        }

        $payload = (new Google_Client(['client_id' => $clientId]))->verifyIdToken($idToken);

        if (! $payload || empty($payload['email'])) {
            throw new \RuntimeException('Google token không hợp lệ hoặc đã hết hạn.');
        }

        if (empty($payload['email_verified'])) {
            throw new \RuntimeException('Email Google chưa được xác minh.');
        }

        return [
            'email' => $payload['email'],
            'name' => $payload['name'] ?? null,
            'avatar' => $payload['picture'] ?? null,
        ];
    }
}
