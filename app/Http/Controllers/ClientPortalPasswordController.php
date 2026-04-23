<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portal\UpdatePortalPasswordRequest;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ClientPortalPasswordController extends Controller
{
    public function edit(Request $request, string $portalToken): RedirectResponse|Response
    {
        $client = $this->portalClient($portalToken);

        if ($client->hasPortalPassword() && ! $this->sessionMatchesClient($request, $client)) {
            return to_route('portal.login', ['portal' => $client->portal_token]);
        }

        return Inertia::render('portal/Password', [
            'portal' => [
                'token' => $client->portal_token,
                'company' => [
                    'name' => $client->agency?->name ?? 'Client portal',
                ],
                'client' => [
                    'full_name' => $client->full_name,
                ],
                'requiresPasswordSetup' => ! $client->hasPortalPassword(),
            ],
        ]);
    }

    public function update(UpdatePortalPasswordRequest $request, string $portalToken): RedirectResponse
    {
        $client = $this->portalClient($portalToken);
        $validated = $request->validated();
        $requiresPasswordSetup = ! $client->hasPortalPassword();

        if (! $requiresPasswordSetup && ! $this->sessionMatchesClient($request, $client)) {
            return to_route('portal.login', ['portal' => $client->portal_token]);
        }

        if ($client->hasPortalPassword() && ! $client->portalPasswordIsValid((string) ($validated['current_password'] ?? ''))) {
            throw ValidationException::withMessages([
                'current_password' => 'Your current password is incorrect.',
            ]);
        }

        $client->updatePortalPassword($validated['password']);
        $request->session()->regenerate();
        $request->session()->put(Client::PORTAL_SESSION_KEY, $client->id);

        return to_route('portal.show', $client->portal_token)->with(
            'success',
            $requiresPasswordSetup
                ? 'Your password is ready. You can now use it each time you sign in.'
                : 'Your password was updated.',
        );
    }

    private function portalClient(string $portalToken): Client
    {
        return Client::findByPortalToken($portalToken) ?? abort(404);
    }

    private function sessionMatchesClient(Request $request, Client $client): bool
    {
        return $request->session()->get(Client::PORTAL_SESSION_KEY) === $client->id;
    }
}
