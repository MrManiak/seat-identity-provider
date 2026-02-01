<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Repositories;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\ClientEntity;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\ScopeEntity;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Scope;

class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * Get a scope entity by its identifier.
     */
    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
    {
        if (!in_array($identifier, Scope::values())) {
            return null;
        }

        return new ScopeEntity($identifier);
    }

    /**
     * Filter scopes based on grant type and client.
     */
    public function finalizeScopes(
        array $scopes,
        $grantType,
        ClientEntityInterface $clientEntity,
        $userIdentifier = null,
        $authCodeId = null
    ): array {
        // Get allowed scopes for this client
        $allowedScopes = $clientEntity instanceof ClientEntity ? $clientEntity->getAllowedScopes() : $scopes;

        // Filter requested scopes to only those allowed for this client
        $finalScopes = [];
        foreach ($scopes as $scope) {
            if (in_array($scope->getIdentifier(), $allowedScopes)) {
                $finalScopes[] = $scope;
            }
        }

        // Ensure openid scope is always present for OIDC
        $hasOpenId = false;
        foreach ($finalScopes as $scope) {
            if ($scope->getIdentifier() === Scope::OpenId->value) {
                $hasOpenId = true;
                break;
            }
        }

        if (!$hasOpenId && in_array(Scope::OpenId->value, $allowedScopes)) {
            $finalScopes[] = new ScopeEntity(Scope::OpenId->value);
        }

        return $finalScopes;
    }

    /**
     * Get all available scopes.
     */
    public static function getAllScopes(): array
    {
        return Scope::values();
    }
}
