<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AcceptAgencyInvitationRequest;
use App\Models\AgencyInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class AcceptInvitationController extends Controller
{
    public function create(Request $request, AgencyInvitation $agencyInvitation, string $token): Response
    {
        return Inertia::render('auth/AcceptInvitation', [
            'invitation' => $this->invitationPayload($agencyInvitation, $token),
        ]);
    }

    public function store(
        AcceptAgencyInvitationRequest $request,
        AgencyInvitation $agencyInvitation,
        string $token,
    ): RedirectResponse {
        $this->ensureInvitationIsUsable($agencyInvitation, $token);

        $user = DB::transaction(function () use ($request, $agencyInvitation): User {
            $user = new User([
                'agency_id' => $agencyInvitation->agency_id,
                'name' => $request->validated('name'),
                'email' => $agencyInvitation->email,
                'role' => $agencyInvitation->role,
                'password' => Hash::make($request->validated('password')),
            ]);
            $user->email_verified_at = now();
            $user->save();

            $agencyInvitation->markAccepted();

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return to_route('dashboard')->with('success', 'Your account is ready.');
    }

    /**
     * @return array<string, mixed>
     */
    private function invitationPayload(AgencyInvitation $agencyInvitation, string $token): array
    {
        $agencyInvitation->loadMissing('agency', 'invitedBy');

        return [
            'id' => $agencyInvitation->id,
            'email' => $agencyInvitation->email,
            'name' => $agencyInvitation->name,
            'company_name' => $agencyInvitation->agency?->name,
            'invited_by_name' => $agencyInvitation->invitedBy?->name,
            'expires_at' => $agencyInvitation->expires_at?->toIso8601String(),
            'accept_url' => route('team-invitations.store', [
                'agencyInvitation' => $agencyInvitation,
                'token' => $token,
            ]),
            'is_valid' => $agencyInvitation->isPending() && ! $agencyInvitation->isExpired() && $agencyInvitation->matchesToken($token),
        ];
    }

    private function ensureInvitationIsUsable(AgencyInvitation $agencyInvitation, string $token): void
    {
        if (! $agencyInvitation->matchesToken($token) || ! $agencyInvitation->isPending() || $agencyInvitation->isExpired()) {
            throw ValidationException::withMessages([
                'name' => 'This invitation link is no longer valid. Ask your company admin for a new invite.',
            ]);
        }

        if (User::query()->where('email', $agencyInvitation->email)->exists()) {
            throw ValidationException::withMessages([
                'name' => 'An account with this email already exists. Please log in instead.',
            ]);
        }
    }
}
