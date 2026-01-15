<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\CryptKey;

class OidcKeypair extends Model
{
    protected $table = 'oidc_keypair';

    private static ?self $cachedActiveKeypair = null;

    protected $fillable = [
        'public_key',
        'private_key',
        'algorithm',
        'key_id',
        'is_active',
        'expires_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'private_key',
    ];

    /**
     * Get or generate the active keypair (memoized for the request lifecycle).
     */
    public static function getActiveKeypair(): self
    {
        if (self::$cachedActiveKeypair !== null) {
            return self::$cachedActiveKeypair;
        }

        $keypair = self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$keypair) {
            $keypair = self::generateKeypair();
        }

        self::$cachedActiveKeypair = $keypair;

        return $keypair;
    }

    /**
     * Clear the cached active keypair (useful for testing or key rotation).
     */
    public static function clearActiveKeypairCache(): void
    {
        self::$cachedActiveKeypair = null;
    }

    /**
     * OpenSSL configuration for each supported algorithm.
     */
    private const ALGORITHM_CONFIG = [
        'RS256' => [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ],
        'RS384' => [
            'digest_alg' => 'sha384',
            'private_key_bits' => 3072,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ],
        'RS512' => [
            'digest_alg' => 'sha512',
            'private_key_bits' => 4096,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ],
        'ES256' => [
            'digest_alg' => 'sha256',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ],
        'ES384' => [
            'digest_alg' => 'sha384',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp384r1',
        ],
        'ES512' => [
            'digest_alg' => 'sha512',
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'secp521r1',
        ],
    ];

    /**
     * Generate a new keypair based on the configured algorithm.
     *
     * @param bool $active Whether the keypair should be active (default: true for auto-generation)
     */
    public static function generateKeypair(bool $active = true): self
    {
        $algorithm = config('seat-identity-provider.oidc.algorithm', 'RS256');

        if (!isset(self::ALGORITHM_CONFIG[$algorithm])) {
            throw new \InvalidArgumentException("Unsupported algorithm: {$algorithm}");
        }

        $config = self::ALGORITHM_CONFIG[$algorithm];
        $privateKey = openssl_pkey_new($config);
        openssl_pkey_export($privateKey, $privateKeyPem);

        $publicKey = openssl_pkey_get_details($privateKey)['key'];

        $keyId = bin2hex(random_bytes(16));

        $keypair = self::create([
            'public_key' => $publicKey,
            'private_key' => $privateKeyPem,
            'algorithm' => $algorithm,
            'key_id' => $keyId,
            'is_active' => $active,
        ]);

        if ($active) {
            self::clearActiveKeypairCache();
        }

        return $keypair;
    }

    /**
     * Check if this keypair uses an RSA algorithm.
     */
    public function isRsa(): bool
    {
        return str_starts_with($this->algorithm, 'RS');
    }

    /**
     * Check if this keypair uses an EC algorithm.
     */
    public function isEc(): bool
    {
        return str_starts_with($this->algorithm, 'ES');
    }

    /**
     * Get the JWKS representation of this key.
     */
    public function toJwk(): array
    {
        $keyDetails = openssl_pkey_get_details(
            openssl_pkey_get_public($this->public_key)
        );

        $jwk = [
            'alg' => $this->algorithm,
            'use' => 'sig',
            'kid' => $this->key_id,
        ];

        if ($this->isRsa()) {
            $jwk['kty'] = 'RSA';
            $jwk['n'] = self::base64UrlEncode($keyDetails['rsa']['n']);
            $jwk['e'] = self::base64UrlEncode($keyDetails['rsa']['e']);
        } else {
            $jwk['kty'] = 'EC';
            $jwk['crv'] = self::getCurveName($this->algorithm);
            $jwk['x'] = self::base64UrlEncode($keyDetails['ec']['x']);
            $jwk['y'] = self::base64UrlEncode($keyDetails['ec']['y']);
        }

        return $jwk;
    }

    /**
     * Base64 URL encode binary data.
     */
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Get the JWK curve name for an EC algorithm.
     */
    private static function getCurveName(string $algorithm): string
    {
        return match ($algorithm) {
            'ES256' => 'P-256',
            'ES384' => 'P-384',
            'ES512' => 'P-521',
            default => throw new \InvalidArgumentException("Unknown EC algorithm: {$algorithm}"),
        };
    }

    /**
     * Get the private key for signing (bypasses hidden).
     */
    public function getPrivateKeyForSigning(): string
    {
        return $this->attributes['private_key'];
    }

    /**
     * Get a CryptKey instance for the private key.
     */
    public function getPrivateCryptKey(): CryptKey
    {
        return new CryptKey($this->getPrivateKeyForSigning(), null, false);
    }

    /**
     * Get a CryptKey instance for the public key.
     */
    public function getPublicCryptKey(): CryptKey
    {
        return new CryptKey($this->public_key, null, false);
    }
}
