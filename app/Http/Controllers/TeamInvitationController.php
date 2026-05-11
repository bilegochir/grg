<?php

namespace App\Http\Controllers;

use App\Http\Requests\AcceptTeamInvitationRequest;
use App\Http\Requests\StoreTeamInvitationRequest;
use App\Models\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class TeamInvitationController extends Controller
{
    public function store(StoreTeamInvitationRequest $request): RedirectResponse
    {
        $role = Role::query()->findOrFail($request->integer('role_id'));
        $branchId = $request->user()?->hasPermissionTo('staff.manage')
            ? ($request->integer('branch_id') ?: $request->user()?->branch_id)
            : $this->workspace()->normalizeBranchId($request->user(), $request->integer('branch_id') ?: null);

        $invitation = TeamInvitation::query()
            ->where('email', $request->string('email')->toString())
            ->whereNull('accepted_at')
            ->latest()
            ->first();

        if ($invitation === null) {
            $invitation = new TeamInvitation();
        }

        $invitation->fill([
            'branch_id' => $branchId,
            'role_id' => $role->id,
            'invited_by_user_id' => $request->user()->id,
            'email' => $request->string('email')->toString(),
            'name' => $request->string('name')->toString() ?: null,
            'job_title' => $request->string('job_title')->toString() ?: null,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
            'accepted_at' => null,
        ])->save();

        $invitation->load(['role', 'branch']);

        Notification::route('mail', $invitation->email)
            ->notify(new TeamInvitationNotification($invitation));

        return back()->with('success', 'Invitation sent.');
    }

    public function create(string $token): Response
    {
        $invitation = TeamInvitation::query()
            ->with(['role:id,name', 'branch:id,name'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->accepted_at === null && $invitation->expires_at->isFuture(), 403);

        return Inertia::render('Auth/AcceptInvitation', [
            'invitation' => [
                'email' => $invitation->email,
                'name' => $invitation->name,
                'role' => $invitation->role->name,
                'branch' => $invitation->branch?->name,
                'job_title' => $invitation->job_title,
                'expires_at' => $invitation->expires_at->toDayDateTimeString(),
                'token' => $invitation->token,
            ],
        ]);
    }

    public function storeAcceptance(AcceptTeamInvitationRequest $request, string $token): RedirectResponse
    {
        $invitation = TeamInvitation::query()
            ->with(['role', 'branch'])
            ->where('token', $token)
            ->firstOrFail();

        abort_unless($invitation->accepted_at === null && $invitation->expires_at->isFuture(), 403);

        $user = DB::transaction(function () use ($request, $invitation): User {
            $user = User::create([
                'name' => $request->string('name')->toString(),
                'email' => $request->string('email')->toString(),
                'password' => Hash::make($request->string('password')->toString()),
                'branch_id' => $invitation->branch_id,
                'job_title' => $invitation->job_title,
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            $user->roles()->sync([$invitation->role_id]);

            $invitation->forceFill([
                'accepted_at' => now(),
            ])->save();

            return $user;
        });

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
