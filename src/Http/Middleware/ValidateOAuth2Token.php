<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\ResourceServer;
use Nyholm\Psr7\Factory\Psr17Factory;
use Seat\Web\Models\User;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class ValidateOAuth2Token
{
    private ResourceServer $resourceServer;

    public function __construct(ResourceServer $resourceServer)
    {
        $this->resourceServer = $resourceServer;
    }

    public function handle(Request $request, Closure $next)
    {
        $psr17Factory = new Psr17Factory();
        $psrHttpFactory = new PsrHttpFactory(
            $psr17Factory,
            $psr17Factory,
            $psr17Factory,
            $psr17Factory
        );

        $psrRequest = $psrHttpFactory->createRequest($request);

        try {
            $psrRequest = $this->resourceServer->validateAuthenticatedRequest($psrRequest);

            // Get the user ID from the validated request
            $userId = $psrRequest->getAttribute('oauth_user_id');

            if ($userId) {
                // Set the authenticated user
                $user = User::find($userId);
                if ($user) {
                    auth()->setUser($user);
                }
            }

            // Pass the OAuth attributes to the request
            $request->attributes->set('oauth_user_id', $userId);
            $request->attributes->set('oauth_client_id', $psrRequest->getAttribute('oauth_client_id'));
            $request->attributes->set('oauth_scopes', $psrRequest->getAttribute('oauth_scopes'));

            return $next($request);
        } catch (OAuthServerException $exception) {
            return response()->json([
                'error' => $exception->getErrorType(),
                'message' => $exception->getMessage(),
            ], $exception->getHttpStatusCode());
        } catch (Exception $exception) {
            return response()->json([
                'error' => 'server_error',
                'message' => 'An error occurred while validating the access token.',
            ], 500);
        }
    }
}
