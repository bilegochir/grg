<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAgencyInvitationRequest;
use App\Models\AgencyInvitation;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;

class TeamController extends Controller
{
    public function index(Request $request): Response
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null, 403);

        return Inertia::render('settings/Team', [
            'teamMembers' => $agency->users()
                ->orderByRaw('case when id = ? then 0 else 1 end', [$request->user()->id])
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'created_at'])
                ->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'joined_at' => $user->created_at?->toIso8601String(),
                    'is_current_user' => $user->is($request->user()),
                ])
                ->values(),
            'pendingInvites' => AgencyInvitation::query()
                ->whereBelongsTo($agency)
                ->pending()
                ->where('expires_at', '>', Carbon::now())
                ->with('invitedBy:id,name')
                ->latest()
                ->get()
                ->map(fn (AgencyInvitation $invitation): array => [
                    'id' => $invitation->id,
                    'name' => $invitation->name,
                    'email' => $invitation->email,
                    'invited_by_name' => $invitation->invitedBy?->name,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'created_at' => $invitation->created_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    public function store(StoreAgencyInvitationRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null, 403);

        $validated = $request->validated();
        $plainTextToken = Str::random(64);

        $invitation = AgencyInvitation::query()
            ->whereBelongsTo($agency)
            ->pending()
            ->where('email', $validated['email'])
            ->first() ?? new AgencyInvitation([
                'agency_id' => $agency->id,
                'email' => $validated['email'],
            ]);

        $invitation->fill([
            'name' => $validated['name'] ?: null,
            'email' => $validated['email'],
            'invited_by_id' => $request->user()?->id,
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => Carbon::now()->addDays(7),
            'accepted_at' => null,
        ]);
        $invitation->agency()->associate($agency);
        $invitation->save();
        $invitation->loadMissing('agency', 'invitedBy');

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation, $plainTextToken));

        return to_route('settings.team.index')->with('success', 'Invitation sent successfully.');
    }
}
