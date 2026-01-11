<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Enums;

enum Claim: string
{
    // Always included
    case Sub = 'sub';
    case IsAdmin = 'is_admin';

    // Profile scope (standard OIDC)
    case Name = 'name';
    case PreferredUsername = 'preferred_username';
    case UpdatedAt = 'updated_at';

    // Email scope (standard OIDC)
    case Email = 'email';
    case EmailVerified = 'email_verified';

    // seat:character scope
    case CharacterId = 'character_id';
    case CharacterName = 'character_name';

    // seat:corporation scope
    case CorporationId = 'corporation_id';
    case AllianceId = 'alliance_id';

    // seat:squads scope
    case Squads = 'squads';

    /**
     * Get all claim values as strings.
     */
    public static function values(): array
    {
        return array_map(fn (self $claim) => $claim->value, self::cases());
    }
}
