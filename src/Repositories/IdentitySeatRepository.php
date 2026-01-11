<?php

namespace Mrmaniak\Seat\IdentityProvider\Repositories;

use Mrmaniak\Seat\IdentityProvider\Entities\IdentityEntity;
use OpenIDConnect\Interfaces\IdentityEntityInterface;
use OpenIDConnect\Repositories\IdentityRepository;

class IdentitySeatRepository extends IdentityRepository
{
    /**
     * Get an identity entity by identifier.
     */
    public function getByIdentifier(string $identifier): IdentityEntityInterface
    {
        $entity = new IdentityEntity();
        $entity->setIdentifier($identifier);

        return $entity;
    }
}
