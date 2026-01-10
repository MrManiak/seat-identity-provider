<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Seat\Web\Http\Controllers\Controller;
use Mrmaniak\Seat\IdentityProvider\Models\SamlApplication;

class SamlApplicationController extends Controller
{
    public function index(): View
    {
        $applications = SamlApplication::all();

        return view('seat-identity-provider::saml.applications.index', compact('applications'));
    }

    public function create(): View
    {
        return view('seat-identity-provider::saml.applications.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_id' => 'required|string|max:255|unique:saml_applications,entity_id',
            'acs_url' => 'required|url|max:255',
            'slo_url' => 'nullable|url|max:255',
            'certificate' => 'nullable|string',
            'metadata_url' => 'nullable|url|max:255',
            'name_id_format' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        // Generate IdP certificate for this application
        $certData = SamlApplication::generateCertificate();
        $validated['idp_x509_certificate'] = $certData['certificate'];
        $validated['idp_private_key'] = $certData['private_key'];

        SamlApplication::create($validated);

        return redirect()
            ->route('seat-identity-provider.saml.applications.index')
            ->with('success', 'SAML application created successfully.');
    }

    public function edit(SamlApplication $application): View
    {
        return view('seat-identity-provider::saml.applications.edit', compact('application'));
    }

    public function update(Request $request, SamlApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'entity_id' => 'required|string|max:255|unique:saml_applications,entity_id,' . $application->id,
            'acs_url' => 'required|url|max:255',
            'slo_url' => 'nullable|url|max:255',
            'certificate' => 'nullable|string',
            'metadata_url' => 'nullable|url|max:255',
            'name_id_format' => 'required|string|max:255',
            'is_active' => 'boolean',
        ]);

        $validated['is_active'] = $request->has('is_active');

        $application->update($validated);

        return redirect()
            ->route('seat-identity-provider.saml.applications.index')
            ->with('success', 'SAML application updated successfully.');
    }

    public function destroy(SamlApplication $application): RedirectResponse
    {
        $application->delete();

        return redirect()
            ->route('seat-identity-provider.saml.applications.index')
            ->with('success', 'SAML application deleted successfully.');
    }

    // Has to be on the backend to get around CORS and CSP
    public function fetchMetadata(Request $request): JsonResponse
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $url = $request->input('url');

        // SSRF Mitigation: Enforce HTTPS protocol
        if (!str_starts_with(strtolower($url), 'https://')) {
            return response()->json(['error' => 'Only HTTPS URLs are allowed'], 400);
        }

        // SSRF Mitigation: Resolve domain and check for private IP ranges
        $host = parse_url($url, PHP_URL_HOST);
        if (!$host) {
            return response()->json(['error' => 'Invalid URL'], 400);
        }

        $ip = gethostbyname($host);
        if ($ip === $host) {
            return response()->json(['error' => 'Could not resolve hostname'], 400);
        }

        if ($this->isPrivateIp($ip)) {
            return response()->json(['error' => 'Access to private IP ranges is not allowed'], 400);
        }

        try {
            $response = Http::timeout(3)->get($url);

            if (!$response->successful()) {
                return response()->json(['error' => 'Failed to fetch metadata'], 400);
            }

            $xml = simplexml_load_string($response->body());
            if ($xml === false) {
                return response()->json(['error' => 'Invalid XML metadata'], 400);
            }

            $xml->registerXPathNamespace('md', 'urn:oasis:names:tc:SAML:2.0:metadata');
            $xml->registerXPathNamespace('ds', 'http://www.w3.org/2000/09/xmldsig#');

            $data = [
                'entity_id' => (string) $xml['entityID'] ?? null,
                'acs_url' => null,
                'slo_url' => null,
                'certificate' => null,
                'name_id_format' => null,
            ];

            // Get ACS URL
            $acs = $xml->xpath('//md:AssertionConsumerService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"]');
            if (!empty($acs)) {
                $data['acs_url'] = (string) $acs[0]['Location'];
            }

            // Get SLO URL
            $slo = $xml->xpath('//md:SingleLogoutService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"]');
            if (empty($slo)) {
                $slo = $xml->xpath('//md:SingleLogoutService[@Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"]');
            }
            if (!empty($slo)) {
                $data['slo_url'] = (string) $slo[0]['Location'];
            }

            // Get Certificate
            $cert = $xml->xpath('//md:KeyDescriptor[@use="signing"]//ds:X509Certificate');
            if (empty($cert)) {
                $cert = $xml->xpath('//md:KeyDescriptor//ds:X509Certificate');
            }
            if (!empty($cert)) {
                $data['certificate'] = trim((string) $cert[0]);
            }

            // Get NameID Format
            $nameIdFormat = $xml->xpath('//md:NameIDFormat');
            if (!empty($nameIdFormat)) {
                $data['name_id_format'] = (string) $nameIdFormat[0];
            }

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to parse metadata: ' . $e->getMessage()], 400);
        }
    }

    public function metadata(SamlApplication $application): Response
    {
        $idpEntityId = url('/saml/idp');
        $ssoUrl = route('seat-identity-provider.saml.sso', $application);
        $sloUrl = route('seat-identity-provider.saml.slo', $application);
        $certificate = $application->idp_x509_certificate;

        $xml = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<md:EntityDescriptor xmlns:md="urn:oasis:names:tc:SAML:2.0:metadata"
                     xmlns:ds="http://www.w3.org/2000/09/xmldsig#"
                     entityID="{$idpEntityId}">
    <md:IDPSSODescriptor WantAuthnRequestsSigned="false"
                         protocolSupportEnumeration="urn:oasis:names:tc:SAML:2.0:protocol">
        <md:KeyDescriptor use="signing">
            <ds:KeyInfo>
                <ds:X509Data>
                    <ds:X509Certificate>{$certificate}</ds:X509Certificate>
                </ds:X509Data>
            </ds:KeyInfo>
        </md:KeyDescriptor>
        <md:NameIDFormat>{$application->name_id_format}</md:NameIDFormat>
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                Location="{$ssoUrl}"/>
        <md:SingleSignOnService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="{$ssoUrl}"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-POST"
                                Location="{$sloUrl}"/>
        <md:SingleLogoutService Binding="urn:oasis:names:tc:SAML:2.0:bindings:HTTP-Redirect"
                                Location="{$sloUrl}"/>
    </md:IDPSSODescriptor>
</md:EntityDescriptor>
XML;

        $filename = sprintf('idp-metadata-%s.xml', $application->id);

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    private function isPrivateIp(string $ip): bool
    {
        // Check for private and reserved IP ranges
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }
}
