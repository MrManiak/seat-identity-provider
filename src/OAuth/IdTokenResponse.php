<?php

declare(strict_types=1);

namespace Mrmaniak\Seat\IdentityProvider\OAuth;

use Lcobucci\JWT\Builder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use OpenIDConnect\IdTokenResponse as BaseIdTokenResponse;
use OpenIDConnect\Interfaces\IdentityEntityInterface;

class IdTokenResponse extends BaseIdTokenResponse
{
    private string $keyId = '';

    /**
     * Set the key ID to include in the JWT header.
     */
    public function setKeyId(string $keyId): void
    {
        $this->keyId = $keyId;
    }

    /**
     * Override to add the "kid" header to the JWT.
     */
    protected function getBuilder(
        AccessTokenEntityInterface $accessToken,
        IdentityEntityInterface $userEntity
    ): Builder {
        $builder = parent::getBuilder($accessToken, $userEntity);

        if ($this->keyId !== '') {
            $builder = $builder->withHeader('kid', $this->keyId);
        }

        return $builder;
    }
}
