<?php

declare(strict_types=1);

namespace Mrmaniak\Seat\IdentityProvider\OAuth;

use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Ecdsa\Sha256 as ES256;
use Lcobucci\JWT\Signer\Ecdsa\Sha384 as ES384;
use Lcobucci\JWT\Signer\Ecdsa\Sha512 as ES512;
use Lcobucci\JWT\Signer\Rsa\Sha256 as RS256;
use Lcobucci\JWT\Signer\Rsa\Sha384 as RS384;
use Lcobucci\JWT\Signer\Rsa\Sha512 as RS512;

/**
 * Factory for creating JWT signers based on algorithm name.
 *
 * Supports RSA (RS256, RS384, RS512) and ECDSA (ES256, ES384, ES512) algorithms.
 */
class SignerFactory
{
    /**
     * Get the appropriate JWT signer for an algorithm.
     */
    public static function create(?string $algorithm): Signer
    {
        return match ($algorithm) {
            'RS256' => new RS256(),
            'RS384' => new RS384(),
            'RS512' => new RS512(),
            'ES256' => new ES256(),
            'ES384' => new ES384(),
            'ES512' => new ES512(),
            default => new RS256(), // Fallback to RS256
        };
    }
}
