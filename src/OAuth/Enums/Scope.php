<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Enums;

enum Scope: string
{
    // Standard OIDC scopes
    case OpenId = 'openid';
    case Profile = 'profile';
    case Email = 'email';

    // Custom SeAT/EVE scopes
    case User = 'seat:user';
    case Character = 'seat:character';
    case Corporation = 'seat:corporation';
    case Squads = 'seat:squads';

    /**
     * Get all scope values as strings.
     */
    public static function values(): array
    {
        return array_map(fn (self $scope) => $scope->value, self::cases());
    }
}
