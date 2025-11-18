<?php

namespace App\Services;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Ecdsa\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

class AppleClientSecret
{
    /**
     * Create a new class instance.
     */
    // public function __construct()
    // {
    //     //
    // }

      public static function generate()
    {
        $teamId = env('APPLE_TEAM_ID');
        $keyId = env('APPLE_KEY_ID');
        $clientId = env('APPLE_CLIENT_ID');
        $key = InMemory::file(env('APPLE_KEY_PATH'));

        $config = Configuration::forSymmetricSigner(new Sha256(), $key);

        $now = now()->timestamp;
        $token = $config->builder()
            ->issuedBy($teamId)
            ->issuedAt(\DateTimeImmutable::createFromMutable(now()))
            ->expiresAt(\DateTimeImmutable::createFromMutable(now()->addHours(24)))
            ->permittedFor('https://appleid.apple.com')
            ->relatedTo($clientId)
            ->withHeader('kid', $keyId)
            ->getToken($config->signer(), $config->signingKey());

        return $token->toString();
    }
}
