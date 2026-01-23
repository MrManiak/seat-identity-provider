<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use League\OAuth2\Server\AuthorizationServer;
use Mrmaniak\Seat\IdentityProvider\Entities\IdentityEntity;
use Mrmaniak\Seat\IdentityProvider\Models\OidcKeypair;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Claim;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\ScopeRepository;
use OpenIDConnectServer\ClaimExtractor;
use Seat\Web\Http\Controllers\Controller;

class OidcController extends Controller
{
    private AuthorizationServer $server;
    private ClaimExtractor $claimExtractor;

    public function __construct(AuthorizationServer $server, ClaimExtractor $claimExtractor)
    {
        $this->server = $server;
        $this->claimExtractor = $claimExtractor;
    }

    /**
     * Get all supported claims by extracting with all scopes.
     */
    private function getSupportedClaims(): array
    {
        // Use IdentityEntity to get all possible claim keys
        $allPossibleClaims = array_fill_keys(Claim::values(), '');

        // Extract claims using all scopes to get the full list
        $allScopes = ScopeRepository::getAllScopes();
        $extractedClaims = $this->claimExtractor->extract($allScopes, $allPossibleClaims);

        // Return all claim keys including those always included
        return array_unique(array_merge(
            [Claim::Sub->value, Claim::IsAdmin->value],
            array_keys($extractedClaims)
        ));
    }

    /**
     * OpenID Connect Discovery endpoint.
     * /.well-known/openid-configuration
     */
    public function discovery(): JsonResponse
    {
        $issuer = url('/');

        return response()->json([
            'issuer' => $issuer,
            'authorization_endpoint' => route('seat-identity-provider.oauth2.authorize'),
            'token_endpoint' => route('seat-identity-provider.oauth2.token'),
            'userinfo_endpoint' => route('seat-identity-provider.oidc.userinfo'),
            'jwks_uri' => route('seat-identity-provider.oidc.jwks'),
            'scopes_supported' => ScopeRepository::getAllScopes(),
            'response_types_supported' => ['code'],
            'response_modes_supported' => ['query'],
            'grant_types_supported' => ['authorization_code', 'refresh_token'],
            'subject_types_supported' => ['public'],
            'id_token_signing_alg_values_supported' => [OidcKeypair::getActiveKeypair()->algorithm],
            'token_endpoint_auth_methods_supported' => ['client_secret_basic', 'client_secret_post'],
            'claims_supported' => $this->getSupportedClaims(),
        ]);
    }

    /**
     * JSON Web Key Set endpoint.
     * /oidc/jwks
     *
     * Returns the public key in JWKS format for token validation.
     */
    public function jwks(): JsonResponse
    {
        $keypair = OidcKeypair::getActiveKeypair();

        return response()->json([
            'keys' => [$keypair->toJwk()],
        ]);
    }

    /**
     * UserInfo endpoint.
     * Returns claims about the authenticated user.
     */
    public function userinfo(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Get scopes from the validated access token
        $scopes = $request->attributes->get('oauth_scopes', []);

        // Use IdentityEntity to get all claims for the user
        $identityEntity = new IdentityEntity();
        $identityEntity->setIdentifier($user->id);
        $allClaims = $identityEntity->getClaims();

        // Extract only the claims allowed by the granted scopes
        $filteredClaims = $this->claimExtractor->extract($scopes, $allClaims);

        return response()->json($filteredClaims);
    }
}
