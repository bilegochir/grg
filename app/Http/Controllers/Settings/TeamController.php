<?php

namespace App\Http\Controllers\Settings;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreAgencyInvitationRequest;
use App\Http\Requests\Settings\UpdateTeamMemberRoleRequest;
use App\Models\AgencyInvitation;
use App\Models\User;
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
        abort_if($agency === null || ! $request->user()?->canManageTeam(), 403);

        return Inertia::render('settings/Team', [
            'teamMembers' => $agency->users()
                ->orderByRaw('case when id = ? then 0 else 1 end', [$request->user()->id])
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'role', 'created_at'])
                ->map(fn ($user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->value,
                    'role_label' => $user->roleLabel(),
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
                    'role' => $invitation->role->value,
                    'role_label' => $invitation->role->label(),
                    'invited_by_name' => $invitation->invitedBy?->name,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'created_at' => $invitation->created_at?->toIso8601String(),
                ])
                ->values(),
            'roleOptions' => UserRole::options(),
        ]);
    }

    public function store(StoreAgencyInvitationRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null || ! $request->user()?->canManageTeam(), 403);

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
            'role' => UserRole::from($validated['role']),
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

    public function updateRole(UpdateTeamMemberRoleRequest $request, User $user): RedirectResponse
    {
        $actor = $request->user();
        $agency = $actor?->agency;
        abort_if($agency === null || ! $actor?->canManageTeam() || $user->agency_id !== $agency->id, 403);

        $role = UserRole::from($request->validated('role'));

        if ($user->is($actor)) {
            return back()->withErrors([
                'role' => 'Change another team member if you need to update roles.',
            ]);
        }

        if (
            $user->role === UserRole::Admin
            && $role !== UserRole::Admin
            && $agency->users()->where('role', UserRole::Admin->value)->count() === 1
        ) {
            return back()->withErrors([
                'role' => 'Your company needs at least one admin.',
            ]);
        }

        $user->update([
            'role' => $role,
        ]);

        return to_route('settings.team.index')->with('success', 'Team role updated.');
    }
}
