<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Mrmaniak\Seat\IdentityProvider\Models\OidcApplication;
use Mrmaniak\Seat\IdentityProvider\OAuth\Entities\UserEntity;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response as Psr7Response;
use Psr\Http\Message\ResponseInterface;
use Seat\Web\Http\Controllers\Controller;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;

class OAuth2Controller extends Controller
{
    private AuthorizationServer $server;
    private Psr17Factory $psr17Factory;
    private PsrHttpFactory $psrHttpFactory;
    private HttpFoundationFactory $httpFoundationFactory;

    public function __construct(AuthorizationServer $server)
    {
        $this->server = $server;
        $this->psr17Factory = new Psr17Factory();
        $this->psrHttpFactory = new PsrHttpFactory(
            $this->psr17Factory,
            $this->psr17Factory,
            $this->psr17Factory,
            $this->psr17Factory
        );
        $this->httpFoundationFactory = new HttpFoundationFactory();
    }

    /**
     * Display authorization form.
     */
    public function authorize(Request $request)
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        try {
            // Validate the authorization request
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            // Get the client for display
            $client = OidcApplication::where('client_id', $authRequest->getClient()->getIdentifier())
                ->where('is_active', true)
                ->first();

            if (!$client) {
                return response()->json(['error' => 'invalid_client'], 400);
            }

            // If consent is skipped, auto-approve the request
            if ($client->skip_consent) {
                $user = $request->user();
                $userEntity = new UserEntity($user->id);
                $authRequest->setUser($userEntity);
                $authRequest->setAuthorizationApproved(true);

                $response = $this->server->completeAuthorizationRequest(
                    $authRequest,
                    new Psr7Response()
                );

                return $this->convertResponse($response);
            }

            // Store auth request in session for later approval
            session(['oauth2_auth_request' => serialize($authRequest)]);

            // Show approval form
            return view('seat-identity-provider::oidc.authorize', [
                'client' => $client,
                'scopes' => $authRequest->getScopes(),
            ]);
        } catch (OAuthServerException $exception) {
            return $this->convertResponse(
                $exception->generateHttpResponse(new Psr7Response())
            );
        } catch (Exception $exception) {
            return response()->json([
                'error' => 'server_error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle authorization approval/denial.
     */
    public function approveAuthorization(Request $request)
    {
        $authRequest = unserialize(session('oauth2_auth_request'));

        if (!$authRequest) {
            return response()->json(['error' => 'invalid_request'], 400);
        }

        // Clear session
        session()->forget('oauth2_auth_request');

        // Set the user on the auth request
        $user = $request->user();
        $userEntity = new UserEntity($user->id);
        $authRequest->setUser($userEntity);

        // Did the user approve?
        $approved = $request->input('approve') === '1';
        $authRequest->setAuthorizationApproved($approved);

        try {
            $response = $this->server->completeAuthorizationRequest(
                $authRequest,
                new Psr7Response()
            );

            return $this->convertResponse($response);
        } catch (OAuthServerException $exception) {
            return $this->convertResponse(
                $exception->generateHttpResponse(new Psr7Response())
            );
        }
    }

    /**
     * Handle token request.
     */
    public function token(Request $request)
    {
        $psrRequest = $this->psrHttpFactory->createRequest($request);

        try {
            $response = $this->server->respondToAccessTokenRequest(
                $psrRequest,
                new Psr7Response()
            );

            return $this->convertResponse($response);
        } catch (OAuthServerException $exception) {
            return $this->convertResponse(
                $exception->generateHttpResponse(new Psr7Response())
            );
        } catch (Exception $exception) {
            return response()->json([
                'error' => 'server_error',
                'message' => $exception->getMessage(),
            ], 500);
        }
    }

    /**
     * Convert PSR-7 response to Laravel response.
     */
    private function convertResponse(ResponseInterface $psrResponse): Response
    {
        return new Response(
            $psrResponse->getBody()->__toString(),
            $psrResponse->getStatusCode(),
            $psrResponse->getHeaders()
        );
    }
}
