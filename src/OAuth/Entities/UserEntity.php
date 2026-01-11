<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
    use EntityTrait;

    public function __construct(string|int $identifier)
    {
        $this->setIdentifier($identifier);
    }
}
