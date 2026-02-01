<?php

declare(strict_types=1);

namespace Mrmaniak\Seat\IdentityProvider\OAuth;

use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\UserEntityInterface;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\AccessTokenEntity;
use OpenIDConnectServer\Entities\ClaimSetInterface;
use OpenIDConnectServer\IdTokenResponse as BaseIdTokenResponse;

class IdTokenResponse extends BaseIdTokenResponse
{
    /**
     * Override to add the "kid" header to the JWT from the access token entity.
     *
     * This allows dynamic key identification when multiple signing keys exist.
     */
    protected function getBuilder(
        AccessTokenEntityInterface $accessToken,
        UserEntityInterface $userEntity
    ): Builder {
        $builder = parent::getBuilder($accessToken, $userEntity);

        if ($accessToken instanceof AccessTokenEntity) {
            $kid = $accessToken->getKeyId();
            if ($kid !== null) {
                $builder = $builder->withHeader('kid', $kid);
            }
        }

        return $builder;
    }

    /**
     * Override to use the correct signer based on the algorithm.
     *
     * The base class hardcodes RSA Sha256, but we need to support EC algorithms too.
     */
    protected function getExtraParams(AccessTokenEntityInterface $accessToken): array
    {
        if (false === $this->isOpenIDRequest($accessToken->getScopes())) {
            return [];
        }

        $userEntity = $this->identityProvider->getUserEntityByIdentifier($accessToken->getUserIdentifier());

        if (false === is_a($userEntity, UserEntityInterface::class)) {
            throw new \RuntimeException('UserEntity must implement UserEntityInterface');
        } elseif (false === is_a($userEntity, ClaimSetInterface::class)) {
            throw new \RuntimeException('UserEntity must implement ClaimSetInterface');
        }

        $builder = $this->getBuilder($accessToken, $userEntity);

        $claims = $this->claimExtractor->extract($accessToken->getScopes(), $userEntity->getClaims());

        foreach ($claims as $claimName => $claimValue) {
            $builder = $builder->withClaim($claimName, $claimValue);
        }

        // Use the signer from the access token entity (supports RSA and EC algorithms)
        $signer = $accessToken instanceof AccessTokenEntity
            ? $accessToken->getSigner()
            : SignerFactory::create(null);

        $key = InMemory::plainText(
            $this->privateKey->getKeyContents(),
            (string) $this->privateKey->getPassPhrase()
        );

        $token = $builder->getToken($signer, $key);

        return [
            'id_token' => $token->toString(),
        ];
    }

    /**
     * Check if this is an OpenID request (has openid scope).
     *
     * @param ScopeEntityInterface[] $scopes
     */
    private function isOpenIDRequest(array $scopes): bool
    {
        foreach ($scopes as $scope) {
            if ($scope->getIdentifier() === 'openid') {
                return true;
            }
        }

        return false;
    }
}
