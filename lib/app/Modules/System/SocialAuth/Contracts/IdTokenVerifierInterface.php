<?php

namespace App\Modules\System\SocialAuth\Contracts;

interface IdTokenVerifierInterface
{
    /**
     * Verify a provider id_token (signature, audience, expiry) and return the
     * verified identity it carries.
     *
     * @return array{email: string, name: ?string, avatar: ?string}
     *
     * @throws \RuntimeException when the token is invalid, expired, or for the wrong audience.
     */
    public function verify(string $idToken): array;
}
