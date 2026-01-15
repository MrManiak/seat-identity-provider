<?php

declare(strict_types=1);

namespace Mrmaniak\Seat\IdentityProvider\OAuth;

use Lcobucci\JWT\Builder;
use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\AccessTokenEntity;
use OpenIDConnect\IdTokenResponse as BaseIdTokenResponse;
use OpenIDConnect\Interfaces\IdentityEntityInterface;

class IdTokenResponse extends BaseIdTokenResponse
{
    /**
     * Override to add the "kid" header to the JWT.
     */
    protected function getBuilder(
        AccessTokenEntityInterface $accessToken,
        IdentityEntityInterface $userEntity
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
}
