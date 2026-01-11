<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Mrmaniak\Seat\IdentityProvider\Models\OidcApplication;
use Mrmaniak\Seat\IdentityProvider\OAuth\Enums\Scope;
use Mrmaniak\Seat\IdentityProvider\OAuth\Repositories\ScopeRepository;
use Seat\Web\Http\Controllers\Controller;

class OidcApplicationController extends Controller
{
    /**
     * Display a listing of OIDC applications.
     */
    public function index(): View
    {
        $applications = OidcApplication::all();

        return view('seat-identity-provider::oidc.applications.index', compact('applications'));
    }

    /**
     * Show the form for creating a new OIDC application.
     */
    public function create(): View
    {
        $availableScopes = ScopeRepository::getAllScopes();

        return view('seat-identity-provider::oidc.applications.create', compact('availableScopes'));
    }

    /**
     * Store a newly created OIDC application.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'redirect_uris' => 'required|string',
            'allowed_scopes' => 'required|array|min:1',
            'allowed_scopes.*' => 'string|in:' . implode(',', ScopeRepository::getAllScopes()),
            'is_active' => 'boolean',
        ]);

        // Parse redirect URIs (newline-separated)
        $redirectUris = array_filter(
            array_map('trim', explode("\n", $validated['redirect_uris']))
        );

        // Validate each redirect URI
        foreach ($redirectUris as $uri) {
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                return back()
                    ->withInput()
                    ->withErrors(['redirect_uris' => "Invalid redirect URI: {$uri}"]);
            }
        }

        // Ensure openid scope is always included
        if (!in_array(Scope::OpenId->value, $validated['allowed_scopes'])) {
            $validated['allowed_scopes'][] = Scope::OpenId->value;
        }

        try {
            // Create the OIDC application instance
            $application = new OidcApplication([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'redirect_uris' => $redirectUris,
                'allowed_scopes' => $validated['allowed_scopes'],
                'is_active' => $request->boolean('is_active', true),
                'created_by' => auth()->id(),
            ]);

            // Generate the initial client secret (this also saves the model)
            $clientSecret = $application->regenerateSecret();

            return redirect()
                ->route('seat-identity-provider.oidc.applications.index')
                ->with('success', 'OIDC application created successfully.')
                ->with('client_id', $application->client_id)
                ->with('client_secret', $clientSecret);

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to create application: ' . $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing an OIDC application.
     */
    public function edit(OidcApplication $application): View
    {
        $availableScopes = ScopeRepository::getAllScopes();

        return view('seat-identity-provider::oidc.applications.edit', compact('application', 'availableScopes'));
    }

    /**
     * Update the specified OIDC application.
     */
    public function update(Request $request, OidcApplication $application): RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'redirect_uris' => 'required|string',
            'allowed_scopes' => 'required|array|min:1',
            'allowed_scopes.*' => 'string|in:' . implode(',', ScopeRepository::getAllScopes()),
            'is_active' => 'boolean',
        ]);

        // Parse redirect URIs
        $redirectUris = array_filter(
            array_map('trim', explode("\n", $validated['redirect_uris']))
        );

        // Validate each redirect URI
        foreach ($redirectUris as $uri) {
            if (!filter_var($uri, FILTER_VALIDATE_URL)) {
                return back()
                    ->withInput()
                    ->withErrors(['redirect_uris' => "Invalid redirect URI: {$uri}"]);
            }
        }

        // Ensure openid scope is always included
        if (!in_array(Scope::OpenId->value, $validated['allowed_scopes'])) {
            $validated['allowed_scopes'][] = Scope::OpenId->value;
        }

        try {
            $application->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'redirect_uris' => $redirectUris,
                'allowed_scopes' => $validated['allowed_scopes'],
                'is_active' => $request->boolean('is_active', true),
            ]);

            return redirect()
                ->route('seat-identity-provider.oidc.applications.index')
                ->with('success', 'OIDC application updated successfully.');

        } catch (Exception $e) {
            return back()
                ->withInput()
                ->withErrors(['error' => 'Failed to update application: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified OIDC application.
     */
    public function destroy(OidcApplication $application): RedirectResponse
    {
        try {
            $application->delete();

            return redirect()
                ->route('seat-identity-provider.oidc.applications.index')
                ->with('success', 'OIDC application deleted successfully.');

        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete application: ' . $e->getMessage()]);
        }
    }

    /**
     * Regenerate the client secret for an OIDC application.
     */
    public function regenerateSecret(OidcApplication $application): RedirectResponse
    {
        try {
            $newSecret = $application->regenerateSecret();

            return redirect()
                ->route('seat-identity-provider.oidc.applications.edit', $application)
                ->with('success', 'Client secret regenerated successfully.')
                ->with('client_secret', $newSecret);

        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to regenerate secret: ' . $e->getMessage()]);
        }
    }
}
