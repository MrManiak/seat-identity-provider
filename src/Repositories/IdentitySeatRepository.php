<?php

namespace Mrmaniak\Seat\IdentityProvider\Repositories;

use Mrmaniak\Seat\IdentityProvider\Entities\IdentityEntity;
use OpenIDConnectServer\Repositories\IdentityProviderInterface;

class IdentitySeatRepository implements IdentityProviderInterface
{
    /**
     * Get an identity entity by identifier.
     *
     * Returns an entity implementing both UserEntityInterface and ClaimSetInterface.
     */
    public function getUserEntityByIdentifier($identifier): IdentityEntity
    {
        $entity = new IdentityEntity();
        $entity->setIdentifier($identifier);

        return $entity;
    }
}
