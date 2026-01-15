<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Repositories;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\Models\OidcAccessToken;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\AccessTokenEntity;
use Mrmaniak\Seat\IdentityProvider\Models\OidcKeypair;

class AccessTokenRepository implements AccessTokenRepositoryInterface
{
    /**
     * Create a new access token.
     */
    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null
    ): AccessTokenEntityInterface {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        $accessToken->setUserIdentifier($userIdentifier);
        $keypair = OidcKeypair::getActiveKeypair();
        $accessToken->setKeyId($keypair->key_id);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        return $accessToken;
    }

    /**
     * Persist a new access token to storage.
     */
    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity): void
    {
        $scopes = [];
        foreach ($accessTokenEntity->getScopes() as $scope) {
            $scopes[] = $scope->getIdentifier();
        }

        $data = [
            'id' => $accessTokenEntity->getIdentifier(),
            'user_id' => $accessTokenEntity->getUserIdentifier(),
            'client_id' => $accessTokenEntity->getClient()->getIdentifier(),
            'scopes' => $scopes,
            'revoked' => false,
            'expires_at' => $accessTokenEntity->getExpiryDateTime(),
        ];

        if ($accessTokenEntity instanceof AccessTokenEntity) {
            $data['key_id'] = $accessTokenEntity->getKeyId();
        }

        OidcAccessToken::create($data);
    }

    /**
     * Revoke an access token.
     */
    public function revokeAccessToken($tokenId): void
    {
        OidcAccessToken::where('id', $tokenId)->update(['revoked' => true]);
    }

    /**
     * Check if an access token has been revoked.
     */
    public function isAccessTokenRevoked($tokenId): bool
    {
        $token = OidcAccessToken::find($tokenId);

        return $token === null || $token->revoked;
    }
}
