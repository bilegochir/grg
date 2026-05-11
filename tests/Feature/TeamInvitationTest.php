<?php

use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use App\Support\AccessControl;
use Illuminate\Support\Facades\Notification;

function seedInviteAccess(): array
{
    $permissions = collect(AccessControl::permissions())
        ->map(fn (array $permission) => Permission::query()->create($permission));

    $roles = collect(AccessControl::defaultRoles())
        ->map(function (array $roleData) use ($permissions): Role {
            $role = Role::query()->create(collect($roleData)->except('permissions')->all());
            $role->permissions()->sync($permissions->whereIn('name', $roleData['permissions'])->pluck('id')->all());

            return $role;
        });

    return [$permissions, $roles];
}

it('sends a team invitation from the staff workspace', function () {
    Notification::fake();

    [$permissions, $roles] = seedInviteAccess();
    $branch = Branch::factory()->create();
    $manager = User::factory()->create();
    $manager->permissions()->sync([$permissions->firstWhere('name', 'staff.manage')->id]);

    $response = $this->actingAs($manager)->post(route('staff.invitations.store'), [
        'email' => 'invitee@example.com',
        'name' => 'Invitee',
        'job_title' => 'Agent',
        'branch_id' => $branch->id,
        'role_id' => $roles->firstWhere('slug', 'agent')->id,
    ]);

    $response->assertRedirect();

    $invitation = TeamInvitation::query()->where('email', 'invitee@example.com')->firstOrFail();

    expect($invitation->branch_id)->toBe($branch->id);
    expect($invitation->role->slug)->toBe('agent');

    Notification::assertSentOnDemand(TeamInvitationNotification::class);
});

it('accepts a team invitation and creates the user with branch and role', function () {
    [, $roles] = seedInviteAccess();
    $branch = Branch::factory()->create();
    $invitation = TeamInvitation::factory()->create([
        'branch_id' => $branch->id,
        'role_id' => $roles->firstWhere('slug', 'support')->id,
        'email' => 'joiner@example.com',
        'job_title' => 'Support Specialist',
    ]);

    $response = $this->post(route('team-invitations.store', $invitation->token), [
        'name' => 'Joined User',
        'email' => 'joiner@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::query()->where('email', 'joiner@example.com')->firstOrFail();

    expect($user->branch_id)->toBe($branch->id);
    expect($user->job_title)->toBe('Support Specialist');
    expect($user->roles()->pluck('slug')->all())->toBe(['support']);
    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});
