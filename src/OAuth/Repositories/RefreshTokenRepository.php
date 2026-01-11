<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Repositories;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\Models\OidcAccessToken;
use Mrmaniak\Seat\IdentityProvider\Models\OidcRefreshToken;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\RefreshTokenEntity;
use Seat\Web\Models\User;

class RefreshTokenRepository implements RefreshTokenRepositoryInterface
{
    /**
     * Create a new refresh token.
     */
    public function getNewRefreshToken(): ?RefreshTokenEntityInterface
    {
        return new RefreshTokenEntity();
    }

    /**
     * Persist a new refresh token to storage.
     */
    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity): void
    {
        OidcRefreshToken::create([
            'id' => $refreshTokenEntity->getIdentifier(),
            'access_token_id' => $refreshTokenEntity->getAccessToken()->getIdentifier(),
            'revoked' => false,
            'expires_at' => $refreshTokenEntity->getExpiryDateTime(),
        ]);
    }

    /**
     * Revoke a refresh token.
     */
    public function revokeRefreshToken($tokenId): void
    {
        OidcRefreshToken::where('id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Check if a refresh token has been revoked.
     */
    public function isRefreshTokenRevoked($tokenId): bool
    {
        $token = OidcRefreshToken::find($tokenId);

        if ($token === null || $token->revoked) {
            return true;
        }

        // Also check if the associated access token is revoked
        $accessToken = OidcAccessToken::find($token->access_token_id);

        if ($accessToken === null || $accessToken->revoked) {
            return true;
        }

        // Check if the user exists and is active
        $user = User::find($accessToken->user_id);

        return $user === null || !$user->active;
    }
}
