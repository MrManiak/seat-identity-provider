<?php

namespace Vita\Seat\IdentityProvider\Http\Controllers;

use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use RobRichards\XMLSecLibs\XMLSecurityDSig;
use RobRichards\XMLSecLibs\XMLSecurityKey;
use Seat\Web\Http\Controllers\Controller;
use Seat\Web\Models\User;
use Vita\Seat\IdentityProvider\Models\SamlApplication;

class SamlController extends Controller
{
    public function sso(Request $request, SamlApplication $application): Response|View
    {
        if (!$application->is_active) {
            abort(403, 'SAML application is not active');
        }

        // Get the AuthnRequest from the request
        $samlRequest = $request->input('SAMLRequest');
        $relayState = $request->input('RelayState', '');

        if (!$samlRequest) {
            abort(400, 'Missing SAMLRequest parameter');
        }

        // Decode the AuthnRequest
        $authnRequest = $this->decodeAuthnRequest($samlRequest, $request->isMethod('GET'));

        // Validate AuthnRequest signature if SP certificate is configured
        if ($application->certificate) {
            $this->validateAuthnRequestSignature($request, $application->certificate);
        }

        // Parse the AuthnRequest to get the ID and ACS URL
        $authnRequestData = $this->parseAuthnRequest($authnRequest);

        // Build the SAML Response
        $user = auth()->user();
        $samlResponse = $this->buildSamlResponse($application, $user, $authnRequestData);

        // Sign the response with RSA-SHA256
        $signedResponse = $this->signResponse($samlResponse, $application);

        // Base64 encode the response
        $encodedResponse = base64_encode($signedResponse);

        // Return auto-submit form to POST the response to the ACS URL
        return response()->view('seat-identity-provider::saml.post-binding', [
            'destination' => $authnRequestData['acs_url'] ?? $application->acs_url,
            'samlResponse' => $encodedResponse,
            'relayState' => $relayState,
        ]);
    }

    public function slo(Request $request, SamlApplication $application): Response|View
    {
        if (!$application->is_active) {
            abort(403, 'SAML application is not active');
        }

        // Get the LogoutRequest from the request
        $samlRequest = $request->input('SAMLRequest');
        $relayState = $request->input('RelayState', '');

        if (!$samlRequest) {
            abort(400, 'Missing SAMLRequest parameter');
        }

        // Decode the LogoutRequest
        $logoutRequest = $this->decodeAuthnRequest($samlRequest, $request->isMethod('GET'));

        // Validate LogoutRequest signature if SP certificate is configured
        if ($application->certificate) {
            $this->validateAuthnRequestSignature($request, $application->certificate);
        }

        // Parse the LogoutRequest
        $logoutRequestData = $this->parseLogoutRequest($logoutRequest);

        // Log out the user from SeAT
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        // Build the LogoutResponse
        $logoutResponse = $this->buildLogoutResponse($application, $logoutRequestData);

        // Sign the response
        $signedResponse = $this->signLogoutResponse($logoutResponse, $application);

        // Base64 encode the response
        $encodedResponse = base64_encode($signedResponse);

        // Determine destination - use SLO URL from request or application config
        $destination = $logoutRequestData['response_url'] ?? $application->slo_url;

        if (!$destination) {
            abort(400, 'No SLO URL configured for this application');
        }

        // Return auto-submit form to POST the response
        return response()->view('seat-identity-provider::saml.post-binding', [
            'destination' => $destination,
            'samlResponse' => $encodedResponse,
            'relayState' => $relayState,
        ]);
    }

    private function decodeAuthnRequest(string $samlRequest, bool $isRedirectBinding): string
    {
        $decoded = base64_decode($samlRequest);

        if ($isRedirectBinding) {
            $inflated = gzinflate($decoded);
            if ($inflated === false) {
                abort(400, 'Failed to inflate AuthnRequest');
            }
            return $inflated;
        }

        return $decoded;
    }

    private function validateAuthnRequestSignature(Request $request, string $certificate): void
    {
        if ($request->isMethod('GET')) {
            // HTTP-Redirect binding: signature is in query parameters
            $signature = $request->input('Signature');
            $sigAlg = $request->input('SigAlg');

            if (!$signature || !$sigAlg) {
                abort(400, 'AuthnRequest signature is required but missing');
            }

            // Build the signed query string
            $signedQuery = 'SAMLRequest=' . urlencode($request->input('SAMLRequest'));
            if ($request->has('RelayState')) {
                $signedQuery .= '&RelayState=' . urlencode($request->input('RelayState'));
            }
            $signedQuery .= '&SigAlg=' . urlencode($sigAlg);

            $certFormatted = "-----BEGIN CERTIFICATE-----\n" .
                chunk_split($certificate, 64, "\n") .
                "-----END CERTIFICATE-----";

            $publicKey = openssl_pkey_get_public($certFormatted);
            if (!$publicKey) {
                abort(500, 'Invalid SP certificate');
            }

            $algorithm = $this->getOpenSslAlgorithm($sigAlg);
            $valid = openssl_verify($signedQuery, base64_decode($signature), $publicKey, $algorithm);

            if ($valid !== 1) {
                abort(400, 'Invalid AuthnRequest signature');
            }
        } else {
            // HTTP-POST binding: signature is embedded in XML
            // The signature validation for POST binding would require XML signature verification
            // For now, we'll skip embedded signature validation in POST binding
        }
    }

    private function getOpenSslAlgorithm(string $sigAlg): int
    {
        return match ($sigAlg) {
            XMLSecurityKey::RSA_SHA256 => OPENSSL_ALGO_SHA256,
            XMLSecurityKey::RSA_SHA384 => OPENSSL_ALGO_SHA384,
            XMLSecurityKey::RSA_SHA512 => OPENSSL_ALGO_SHA512,
            XMLSecurityKey::RSA_SHA1 => OPENSSL_ALGO_SHA1,
            default => OPENSSL_ALGO_SHA256,
        };
    }

    private function parseAuthnRequest(string $authnRequest): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($authnRequest);

        $root = $dom->documentElement;

        return [
            'id' => $root->getAttribute('ID'),
            'acs_url' => $root->getAttribute('AssertionConsumerServiceURL') ?: null,
            'issuer' => $dom->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0)?->textContent,
        ];
    }

    private function generateUniqueId(): string
    {
        return bin2hex(random_bytes(21));
    }

    private function buildSamlResponse($application, User $user, array $authnRequestData): string
    {
        $responseId = '_' . $this->generateUniqueId();
        $assertionId = '_' . $this->generateUniqueId();
        $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
        $notBefore = gmdate('Y-m-d\TH:i:s\Z', time() - 60);
        $notOnOrAfter = gmdate('Y-m-d\TH:i:s\Z', time() + 300);
        $sessionNotOnOrAfter = gmdate('Y-m-d\TH:i:s\Z', time() + 3600);

        $idpEntityId = url('/saml/idp');
        $destination = $authnRequestData['acs_url'] ?? $application->acs_url;
        $inResponseTo = $authnRequestData['id'] ?? '';
        $audience = $application->entity_id;

        // Generate fake email based on user ID and site domain
        $siteDomain = parse_url(config('app.url'), PHP_URL_HOST) ?? 'seat.local';
        $seatUserEmail = "seatuser.{$user->id}@{$siteDomain}";

        // Determine NameID based on format
        $nameId = $this->getNameId($user, $application->name_id_format, $seatUserEmail);

        // Get main character info
        $mainCharacter = $user->main_character;
        $mainCharacterName = htmlspecialchars($mainCharacter->name ?? 'Unknown', ENT_XML1, 'UTF-8');
        $mainCharacterId = $mainCharacter->character_id ?? 0;
        $mainCharacterCorporationId = $mainCharacter->affiliation->corporation_id ?? 0;

        $userId = $user->id;
        $isAdmin = $user->admin;

        // Get squad names
        $squadValues = '';
        foreach ($user->squads as $squad) {
            $squadName = htmlspecialchars($squad->name, ENT_XML1, 'UTF-8');
            $squadValues .= "                <saml:AttributeValue>{$squadName}</saml:AttributeValue>\n";
        }
        $squadValues = rtrim($squadValues, "\n");

        $response = <<<XML
<samlp:Response xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                ID="{$responseId}"
                Version="2.0"
                IssueInstant="{$issueInstant}"
                Destination="{$destination}"
                InResponseTo="{$inResponseTo}">
    <saml:Issuer>{$idpEntityId}</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
    <saml:Assertion xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                    Version="2.0"
                    ID="{$assertionId}"
                    IssueInstant="{$issueInstant}">
        <saml:Issuer>{$idpEntityId}</saml:Issuer>
        <saml:Subject>
            <saml:NameID Format="{$application->name_id_format}">{$nameId}</saml:NameID>
            <saml:SubjectConfirmation Method="urn:oasis:names:tc:SAML:2.0:cm:bearer">
                <saml:SubjectConfirmationData NotOnOrAfter="{$notOnOrAfter}"
                                              Recipient="{$destination}"
                                              InResponseTo="{$inResponseTo}"/>
            </saml:SubjectConfirmation>
        </saml:Subject>
        <saml:Conditions NotBefore="{$notBefore}" NotOnOrAfter="{$notOnOrAfter}">
            <saml:AudienceRestriction>
                <saml:Audience>{$audience}</saml:Audience>
            </saml:AudienceRestriction>
        </saml:Conditions>
        <saml:AuthnStatement AuthnInstant="{$issueInstant}"
                             SessionNotOnOrAfter="{$sessionNotOnOrAfter}"
                             SessionIndex="{$assertionId}">
            <saml:AuthnContext>
                <saml:AuthnContextClassRef>urn:oasis:names:tc:SAML:2.0:ac:classes:Password</saml:AuthnContextClassRef>
            </saml:AuthnContext>
        </saml:AuthnStatement>
        <saml:AttributeStatement>
            <saml:Attribute Name="user_id" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$userId}</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="email" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$seatUserEmail}</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="name" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$mainCharacterName}</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="squads" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
{$squadValues}
            </saml:Attribute>
            <saml:Attribute Name="character_id" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$mainCharacterId}</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="corporation_id" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$mainCharacterCorporationId}</saml:AttributeValue>
            </saml:Attribute>
            <saml:Attribute Name="is_admin" NameFormat="urn:oasis:names:tc:SAML:2.0:attrname-format:basic">
                <saml:AttributeValue>{$isAdmin}</saml:AttributeValue>
            </saml:Attribute>
        </saml:AttributeStatement>
    </saml:Assertion>
</samlp:Response>
XML;

        return $response;
    }

    private function getNameId($user, string $nameIdFormat, string $seatUserEmail): string
    {
        return match ($nameIdFormat) {
            'urn:oasis:names:tc:SAML:1.1:nameid-format:emailAddress' => $seatUserEmail,
            'urn:oasis:names:tc:SAML:2.0:nameid-format:persistent' => (string) $user->id,
            'urn:oasis:names:tc:SAML:2.0:nameid-format:transient' => $this->generateUniqueId(),
            default => $user->name,
        };
    }

    private function signResponse(string $response, SamlApplication $application): string
    {
        $privateKey = $application->idp_private_key;
        $certificate = "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($application->idp_x509_certificate, 64, "\n") .
            "-----END CERTIFICATE-----";

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($response);

        // Sign the Assertion element
        $assertion = $dom->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Assertion')->item(0);

        if ($assertion) {
            // Create XMLSecurityDSig object
            $dsig = new XMLSecurityDSig();
            $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

            // Add reference to the Assertion with SHA-256
            $dsig->addReference(
                $assertion,
                XMLSecurityDSig::SHA256,
                ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
                ['id_name' => 'ID', 'overwrite' => false]
            );

            // Create and load the private key
            $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
            $key->loadKey($privateKey);

            // Sign the XML
            $dsig->sign($key);

            // Add the X.509 certificate to the signature
            $dsig->add509Cert($certificate);

            // Insert the signature after the Issuer element
            $issuer = $assertion->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
            if ($issuer && $issuer->nextSibling) {
                $dsig->insertSignature($assertion, $issuer->nextSibling);
            } else {
                $dsig->appendSignature($assertion);
            }
        }

        return $dom->saveXML();
    }

    private function parseLogoutRequest(string $logoutRequest): array
    {
        $dom = new DOMDocument();
        $dom->loadXML($logoutRequest);

        $root = $dom->documentElement;

        return [
            'id' => $root->getAttribute('ID'),
            'issuer' => $dom->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0)?->textContent,
            'name_id' => $dom->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'NameID')->item(0)?->textContent,
            'response_url' => $root->getAttribute('Destination') ?: null,
        ];
    }

    private function buildLogoutResponse(SamlApplication $application, array $logoutRequestData): string
    {
        $responseId = '_' . $this->generateUniqueId();
        $issueInstant = gmdate('Y-m-d\TH:i:s\Z');
        $idpEntityId = url('/saml/idp');
        $destination = $logoutRequestData['response_url'] ?? $application->slo_url;
        $inResponseTo = $logoutRequestData['id'] ?? '';

        $response = <<<XML
<samlp:LogoutResponse xmlns:samlp="urn:oasis:names:tc:SAML:2.0:protocol"
                      xmlns:saml="urn:oasis:names:tc:SAML:2.0:assertion"
                      ID="{$responseId}"
                      Version="2.0"
                      IssueInstant="{$issueInstant}"
                      Destination="{$destination}"
                      InResponseTo="{$inResponseTo}">
    <saml:Issuer>{$idpEntityId}</saml:Issuer>
    <samlp:Status>
        <samlp:StatusCode Value="urn:oasis:names:tc:SAML:2.0:status:Success"/>
    </samlp:Status>
</samlp:LogoutResponse>
XML;

        return $response;
    }

    private function signLogoutResponse(string $response, SamlApplication $application): string
    {
        $privateKey = $application->idp_private_key;
        $certificate = "-----BEGIN CERTIFICATE-----\n" .
            chunk_split($application->idp_x509_certificate, 64, "\n") .
            "-----END CERTIFICATE-----";

        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->formatOutput = false;
        $dom->loadXML($response);

        $root = $dom->documentElement;

        // Create XMLSecurityDSig object
        $dsig = new XMLSecurityDSig();
        $dsig->setCanonicalMethod(XMLSecurityDSig::EXC_C14N);

        // Add reference to the LogoutResponse with SHA-256
        $dsig->addReference(
            $root,
            XMLSecurityDSig::SHA256,
            ['http://www.w3.org/2000/09/xmldsig#enveloped-signature', XMLSecurityDSig::EXC_C14N],
            ['id_name' => 'ID', 'overwrite' => false]
        );

        // Create and load the private key
        $key = new XMLSecurityKey(XMLSecurityKey::RSA_SHA256, ['type' => 'private']);
        $key->loadKey($privateKey);

        // Sign the XML
        $dsig->sign($key);

        // Add the X.509 certificate to the signature
        $dsig->add509Cert($certificate);

        // Insert the signature after the Issuer element
        $issuer = $root->getElementsByTagNameNS('urn:oasis:names:tc:SAML:2.0:assertion', 'Issuer')->item(0);
        if ($issuer && $issuer->nextSibling) {
            $dsig->insertSignature($root, $issuer->nextSibling);
        } else {
            $dsig->appendSignature($root);
        }

        return $dom->saveXML();
    }
}
