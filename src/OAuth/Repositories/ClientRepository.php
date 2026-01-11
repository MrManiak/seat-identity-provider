<?php

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Repositories;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\Models\OidcApplication;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\ClientEntity;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Scope;

class ClientRepository implements ClientRepositoryInterface
{
    /**
     * Get a client by its identifier.
     */
    public function getClientEntity($clientIdentifier): ?ClientEntity
    {
        $application = OidcApplication::where('client_id', $clientIdentifier)
            ->where('is_active', true)
            ->first();

        if (!$application) {
            return null;
        }

        $client = new ClientEntity();
        $client->setIdentifier($clientIdentifier);
        $client->setName($application->name);
        $client->setRedirectUri($application->redirect_uris ?? []);
        $client->setConfidential(true);
        $client->setAllowedScopes($application->allowed_scopes ?? [Scope::OpenId->value]);

        return $client;
    }

    /**
     * Validate a client's secret.
     */
    public function validateClient($clientIdentifier, $clientSecret, $grantType): bool
    {
        $application = OidcApplication::where('client_id', $clientIdentifier)
            ->where('is_active', true)
            ->first();

        if (!$application) {
            return false;
        }

        // Verify the client secret (required for confidential clients)
        if ($clientSecret !== null) {
            if (!$application->verifySecret($clientSecret)) {
                return false;
            }
        } else {
            // Client secret is required for confidential clients
            return false;
        }

        // Check if grant type is allowed (we support authorization_code and refresh_token)
        $allowedGrantTypes = ['authorization_code', 'refresh_token'];
        if ($grantType !== null && !in_array($grantType, $allowedGrantTypes)) {
            return false;
        }

        return true;
    }
}
