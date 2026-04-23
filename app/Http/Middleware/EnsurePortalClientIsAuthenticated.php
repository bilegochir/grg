<?php

namespace App\Http\Middleware;

use App\Models\Client;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePortalClientIsAuthenticated
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $portalToken = (string) $request->route('portalToken');
        $client = Client::findByPortalToken($portalToken);

        abort_if($client === null, 404);

        if (! $client->hasPortalPassword()) {
            return new RedirectResponse(route('portal.password.edit', $portalToken));
        }

        if ($request->session()->get(Client::PORTAL_SESSION_KEY) !== $client->id) {
            return new RedirectResponse(route('portal.login', [
                'portal' => $portalToken,
            ]));
        }

        return $next($request);
    }
}
