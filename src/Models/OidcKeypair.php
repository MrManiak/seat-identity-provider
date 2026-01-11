<?php

namespace Mrmaniak\Seat\IdentityProvider\Models;

use Illuminate\Database\Eloquent\Model;
use League\OAuth2\Server\CryptKey;

class OidcKeypair extends Model
{
    protected $table = 'oidc_keypair';

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
     * Get or generate the active keypair.
     */
    public static function getActiveKeypair(): self
    {
        $keypair = self::where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();

        if (!$keypair) {
            $keypair = self::generateKeypair();
        }

        return $keypair;
    }

    /**
     * Generate a new RSA keypair.
     */
    public static function generateKeypair(): self
    {
        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $privateKey = openssl_pkey_new($config);
        openssl_pkey_export($privateKey, $privateKeyPem);

        $publicKey = openssl_pkey_get_details($privateKey)['key'];

        $keyId = bin2hex(random_bytes(16));

        return self::create([
            'public_key' => $publicKey,
            'private_key' => $privateKeyPem,
            'algorithm' => 'RS256',
            'key_id' => $keyId,
            'is_active' => true,
        ]);
    }

    /**
     * Get the JWKS representation of this key.
     */
    public function toJwk(): array
    {
        $keyDetails = openssl_pkey_get_details(
            openssl_pkey_get_public($this->public_key)
        );

        return [
            'kty' => 'RSA',
            'alg' => $this->algorithm,
            'use' => 'sig',
            'kid' => $this->key_id,
            'n' => rtrim(strtr(base64_encode($keyDetails['rsa']['n']), '+/', '-_'), '='),
            'e' => rtrim(strtr(base64_encode($keyDetails['rsa']['e']), '+/', '-_'), '='),
        ];
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
