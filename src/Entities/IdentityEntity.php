<?php

namespace Mrmaniak\Seat\IdentityProvider\Entities;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Claim;
use OpenIDConnect\Claims\Traits\WithClaims;
use OpenIDConnect\Interfaces\IdentityEntityInterface;
use Seat\Web\Models\User;

class IdentityEntity implements IdentityEntityInterface
{
    use EntityTrait;
    use WithClaims;

    protected ?User $user = null;

    /**
     * Set the identifier (user ID) and load the user.
     */
    public function setIdentifier($identifier): void
    {
        $this->identifier = $identifier;
        $this->user = User::with(['main_character.affiliation', 'squads'])->find($identifier);
    }

    /**
     * Get the user model.
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Get all claims for the user.
     */
    public function getClaims(): array
    {
        if (!$this->user) {
            return [];
        }

        $siteDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'seat.local';

        return [
            // seat:user scope
            Claim::Sub->value => (string) $this->user->id,
            Claim::IsAdmin->value => $this->user->admin ?? false,

            // Profile scope
            Claim::Name->value => $this->user->name ?? 'Unknown',
            Claim::PreferredUsername->value => $this->user->name,
            Claim::UpdatedAt->value => $this->user->updated_at?->timestamp,

            // Email scope
            Claim::Email->value => "seatuser.{$this->user->id}@{$siteDomain}",
            Claim::EmailVerified->value => false,

            // seat:character scope
            Claim::CharacterId->value => $this->user->main_character?->character_id,
            Claim::CharacterName->value => $this->user->main_character?->name,

            // seat:corporation scope
            Claim::CorporationId->value => $this->user->main_character?->affiliation?->corporation_id,
            Claim::AllianceId->value => $this->user->main_character?->affiliation?->alliance_id,

            // seat:squads scope
            Claim::Squads->value => $this->user->squads->pluck('name')->toArray(),
        ];
    }
}
