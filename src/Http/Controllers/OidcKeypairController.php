<?php

namespace Mrmaniak\Seat\IdentityProvider\Http\Controllers;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Mrmaniak\Seat\IdentityProvider\Models\OidcKeypair;
use Seat\Web\Http\Controllers\Controller;

class OidcKeypairController extends Controller
{
    /**
     * Display a listing of OIDC keypairs.
     */
    public function index(): View
    {
        $keypairs = OidcKeypair::orderBy('created_at', 'desc')->get();

        return view('seat-identity-provider::oidc.keys.index', compact('keypairs'));
    }

    /**
     * Generate a new keypair (inactive by default).
     */
    public function store(): RedirectResponse
    {
        try {
            OidcKeypair::generateKeypair(active: false);

            return redirect()
                ->route('seat-identity-provider.oidc.keys.index')
                ->with('success', trans('seat-identity-provider::oidc.key_generated'));

        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to generate keypair: ' . $e->getMessage()]);
        }
    }

    /**
     * Activate a keypair (deactivate all others).
     */
    public function activate(OidcKeypair $keypair): RedirectResponse
    {
        try {
            // Deactivate all other keypairs
            OidcKeypair::where('id', '!=', $keypair->id)->update(['is_active' => false]);

            // Activate the selected keypair
            $keypair->update(['is_active' => true]);

            return redirect()
                ->route('seat-identity-provider.oidc.keys.index')
                ->with('success', trans('seat-identity-provider::oidc.key_activated'));

        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to activate keypair: ' . $e->getMessage()]);
        }
    }

    /**
     * Delete an inactive keypair.
     */
    public function destroy(OidcKeypair $keypair): RedirectResponse
    {
        // Prevent deletion of active keypair
        if ($keypair->is_active) {
            return back()
                ->withErrors(['error' => trans('seat-identity-provider::oidc.cannot_delete_active')]);
        }

        try {
            $keypair->delete();

            return redirect()
                ->route('seat-identity-provider.oidc.keys.index')
                ->with('success', trans('seat-identity-provider::oidc.key_deleted'));

        } catch (Exception $e) {
            return back()
                ->withErrors(['error' => 'Failed to delete keypair: ' . $e->getMessage()]);
        }
    }
}
