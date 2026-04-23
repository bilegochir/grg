<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portal\PortalLoginRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalAuthController extends Controller
{
    public function create(Request $request): RedirectResponse|Response
    {
        $portalToken = trim((string) $request->query('portal', ''));
        $client = $portalToken !== '' ? Client::findByPortalToken($portalToken) : null;
        $authenticatedClient = $this->authenticatedPortalClient($request);

        if ($authenticatedClient !== null && ($client === null || $client->is($authenticatedClient))) {
            return to_route('portal.show', $authenticatedClient->portal_token);
        }

        return Inertia::render('portal/Login', [
            'portal' => [
                'portalToken' => $client?->portal_token ?? ($portalToken !== '' ? $portalToken : null),
                'context' => $client === null ? null : [
                    'company_name' => $client->agency?->name,
                    'client_name' => $client->full_name,
                ],
            ],
        ]);
    }

    public function store(PortalLoginRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $client = Client::query()
            ->with('agency:id,name')
            ->where('email', $validated['email'])
            ->first();

        if ($client === null || ! $client->portalPasswordIsValid($validated['password'])) {
            throw ValidationException::withMessages([
                'password' => 'The email or password is incorrect.',
            ]);
        }

        $request->session()->regenerate();
        $request->session()->put(Client::PORTAL_SESSION_KEY, $client->id);

        $redirectPortalToken = filled($validated['portal_token'] ?? null) && $client->portal_token === $validated['portal_token']
            ? $validated['portal_token']
            : $client->portal_token;

        return to_route('portal.show', $redirectPortalToken);
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget(Client::PORTAL_SESSION_KEY);
        $request->session()->regenerateToken();

        return to_route('portal.login')->with('success', 'You have been signed out of the portal.');
    }

    private function authenticatedPortalClient(Request $request): ?Client
    {
        $clientId = $request->session()->get(Client::PORTAL_SESSION_KEY);

        if (! is_int($clientId) && ! is_string($clientId)) {
            return null;
        }

        return Client::query()->find($clientId);
    }
}
