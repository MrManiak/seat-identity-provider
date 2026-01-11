<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Repositories;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\Models\OidcAuthCode;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\AuthCodeEntity;

class AuthCodeRepository implements AuthCodeRepositoryInterface
{
    /**
     * Create a new auth code.
     */
    public function getNewAuthCode(): AuthCodeEntityInterface
    {
        return new AuthCodeEntity();
    }

    /**
     * Persist a new auth code to storage.
     */
    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity): void
    {
        $scopes = [];
        foreach ($authCodeEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }

        OidcAuthCode::create([
            'id' => $authCodeEntity->getIdentifier(),
            'user_id' => $authCodeEntity->getUserIdentifier(),
            'client_id' => $authCodeEntity->getClient()->getIdentifier(),
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => $authCodeEntity->getExpiryDateTime(),
        ]);
    }

    /**
     * Revoke an auth code.
     */
    public function revokeAuthCode($codeId): void
    {
        OidcAuthCode::where('id', $codeId)->update(['revoked' => true]);
    }

    /**
     * Check if an auth code has been revoked.
     */
    public function isAuthCodeRevoked($codeId): bool
    {
        $code = OidcAuthCode::find($codeId);

        return $code === null || $code->revoked;
    }
}
