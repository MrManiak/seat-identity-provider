<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Entities;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

class ClientEntity implements ClientEntityInterface
{
    use ClientTrait;
    use EntityTrait;

    protected array $allowedScopes = [];

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setRedirectUri(string|array $uri): void
    {
        $this->redirectUri = is_array($uri) ? $uri : [$uri];
    }

    public function setConfidential(bool $isConfidential = true): void
    {
        $this->isConfidential = $isConfidential;
    }

    public function setAllowedScopes(array $scopes): void
    {
        $this->allowedScopes = $scopes;
    }

    public function getAllowedScopes(): array
    {
        return $this->allowedScopes;
    }
}
