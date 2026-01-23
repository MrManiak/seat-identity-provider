<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Entities;

use DateTimeImmutable;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\Traits\AccessTokenTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\TokenEntityTrait;
use Mrmaniak\Seat\IdentityProvider\OAuth\SignerFactory;

class AccessTokenEntity implements AccessTokenEntityInterface
{
    use AccessTokenTrait {
        toString as private traitToString;
        initJwtConfiguration as private traitInitJwtConfiguration;
    }
    use EntityTrait;
    use TokenEntityTrait;

    private ?string $keyId = null;
    private ?string $algorithm = null;

    public function getKeyId(): ?string
    {
        return $this->keyId;
    }

    public function setKeyId(?string $keyId): void
    {
        $this->keyId = $keyId;
    }

    public function getAlgorithm(): ?string
    {
        return $this->algorithm;
    }

    public function setAlgorithm(?string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Get the signer for this token's algorithm.
     */
    public function getSigner(): Signer
    {
        return SignerFactory::create($this->algorithm);
    }

    /**
     * Initialize JWT configuration with the correct signer for the algorithm.
     */
    public function initJwtConfiguration(): void
    {
        // Use the algorithm-specific signer instead of hardcoded RS256
        $this->jwtConfiguration = Configuration::forAsymmetricSigner(
            $this->getSigner(),
            InMemory::plainText($this->privateKey->getKeyContents(), $this->privateKey->getPassPhrase() ?? ''),
            InMemory::plainText('empty', 'empty')
        );
    }

    /**
     * Generate a JWT from the access token with kid header.
     */
    private function convertToJWTWithKid(): Token
    {
        $this->initJwtConfiguration();

        $builder = $this->jwtConfiguration->builder()
            ->permittedFor($this->getClient()->getIdentifier())
            ->identifiedBy($this->getIdentifier())
            ->issuedAt(new DateTimeImmutable())
            ->canOnlyBeUsedAfter(new DateTimeImmutable())
            ->expiresAt($this->getExpiryDateTime())
            ->relatedTo((string) $this->getUserIdentifier())
            ->withClaim('scopes', $this->getScopes());

        if ($this->keyId !== null) {
            $builder = $builder->withHeader('kid', $this->keyId);
        }

        return $builder->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey());
    }

    /**
     * Generate a string representation from the access token.
     */
    public function toString(): string
    {
        return $this->convertToJWTWithKid()->toString();
    }
}
