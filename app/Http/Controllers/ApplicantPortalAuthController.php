<?php

namespace App\Http\Controllers;

use App\Models\ApplicantPortalInvite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantPortalAuthController extends Controller
{
    public function create(Request $request): Response
    {
        return Inertia::render('Portal/Login', [
            'status' => $request->session()->get('status'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'access_token' => ['required', 'string'],
        ]);

        $invite = ApplicantPortalInvite::query()
            ->with('applicant')
            ->where('token', trim($data['access_token']))
            ->first();

        if (! $invite || ! $invite->isValid()) {
            return back()->with('error', 'That portal link is no longer valid. Ask your visa team for a new access link.');
        }

        $invite->forceFill(['last_used_at' => now()])->save();
        $request->session()->put('portal_applicant_id', $invite->applicant_id);

        return redirect()->route('portal.dashboard');
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invite = ApplicantPortalInvite::query()
            ->with('applicant')
            ->where('token', $token)
            ->first();

        if (! $invite || ! $invite->isValid()) {
            return redirect()->route('portal.login')->with('error', 'That portal link is expired. Ask your visa team for a fresh one.');
        }

        $invite->forceFill(['last_used_at' => now()])->save();
        $request->session()->put('portal_applicant_id', $invite->applicant_id);

        return redirect()->route('portal.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->session()->forget('portal_applicant_id');

        return redirect()->route('portal.login')->with('status', 'You have been signed out of the applicant portal.');
    }
}
