<?php

declare(strict_types=1);

namespace Mrmaniak\Seat\IdentityProvider\OAuth\Validators;

use DateTimeZone;
use Exception;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\LooseValidAt;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use League\OAuth2\Server\AuthorizationValidators\AuthorizationValidatorInterface;
use League\OAuth2\Server\CryptKeyInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use Mrmaniak\Seat\IdentityProvider\OAuth\SignerFactory;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Custom BearerTokenValidator that supports multiple JWT signing algorithms.
 *
 * The base class hardcodes RSA Sha256, but we need to support EC algorithms too.
 * Since the parent class uses private properties, we implement the interface directly.
 */
class BearerTokenValidator implements AuthorizationValidatorInterface
{
    protected AccessTokenRepositoryInterface $accessTokenRepository;
    protected CryptKeyInterface $publicKey;
    protected ?string $algorithm = null;
    private Configuration $jwtConfiguration;

    public function __construct(AccessTokenRepositoryInterface $accessTokenRepository)
    {
        $this->accessTokenRepository = $accessTokenRepository;
    }

    /**
     * Set the algorithm for JWT verification.
     */
    public function setAlgorithm(?string $algorithm): void
    {
        $this->algorithm = $algorithm;
    }

    /**
     * Set the public key.
     */
    public function setPublicKey(CryptKeyInterface $key): void
    {
        $this->publicKey = $key;
        $this->initJwtConfiguration();
    }

    /**
     * Initialize the JWT configuration with the correct signer for the algorithm.
     */
    private function initJwtConfiguration(): void
    {
        $signer = SignerFactory::create($this->algorithm);
        $clock = new SystemClock(new DateTimeZone(\date_default_timezone_get()));
        $verificationKey = InMemory::plainText($this->publicKey->getKeyContents(), $this->publicKey->getPassPhrase() ?? '');

        $this->jwtConfiguration = Configuration::forAsymmetricSigner(
            $signer,
            InMemory::plainText('empty', 'empty'),
            $verificationKey
        )->withValidationConstraints(
            new LooseValidAt($clock),
            new SignedWith(
                $signer,
                $verificationKey
            )
        );
    }

    /**
     * Validate the authorization request.
     *
     * @throws OAuthServerException
     */
    public function validateAuthorization(ServerRequestInterface $request): ServerRequestInterface
    {
        if ($request->hasHeader('authorization') === false) {
            throw OAuthServerException::accessDenied('Missing "Authorization" header');
        }

        $header = $request->getHeader('authorization');
        $jwt = trim((string) preg_replace('/^\s*Bearer\s/', '', $header[0]));

        if ($jwt === '') {
            throw OAuthServerException::accessDenied('Missing access token');
        }

        try {
            $token = $this->jwtConfiguration->parser()->parse($jwt);
        } catch (\Lcobucci\JWT\Exception $exception) {
            throw OAuthServerException::accessDenied($exception->getMessage(), null, $exception);
        }

        if (!$token instanceof Plain) {
            throw OAuthServerException::accessDenied('Invalid token type');
        }

        try {
            $constraints = $this->jwtConfiguration->validationConstraints();
            $this->jwtConfiguration->validator()->assert($token, ...$constraints);
        } catch (\Lcobucci\JWT\Validation\RequiredConstraintsViolated $exception) {
            throw OAuthServerException::accessDenied('Access token could not be verified', null, $exception);
        }

        $claims = $token->claims();

        if ($this->accessTokenRepository->isAccessTokenRevoked($claims->get('jti'))) {
            throw OAuthServerException::accessDenied('Access token has been revoked');
        }

        return $request
            ->withAttribute('oauth_access_token_id', $claims->get('jti'))
            ->withAttribute('oauth_client_id', $this->convertSingleRecordAudToString($claims->get('aud')))
            ->withAttribute('oauth_user_id', $claims->get('sub'))
            ->withAttribute('oauth_scopes', $claims->get('scopes'));
    }

    /**
     * Convert single record arrays into strings to ensure backwards compatibility.
     *
     * @param mixed $aud
     * @return array|string
     */
    private function convertSingleRecordAudToString(mixed $aud): array|string
    {
        if (\is_array($aud) && \count($aud) === 1) {
            return $aud[0];
        }

        return $aud;
    }
}
