<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Entities;

use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\Traits\ScopeTrait;

class ScopeEntity implements ScopeEntityInterface
{
    use EntityTrait;
    use ScopeTrait;

    public function __construct(string $identifier)
    {
        $this->setIdentifier($identifier);
    }

    public function jsonSerialize(): mixed
    {
        return $this->getIdentifier();
    }
}
