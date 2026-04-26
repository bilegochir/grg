<?php

namespace Tests\Feature\Settings;

use App\Enums\UserRole;
use App\Models\AgencyInvitation;
use App\Models\User;
use App\Notifications\TeamInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TeamInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_team_settings_page_is_displayed_with_members_and_pending_invites(): void
    {
        $user = User::factory()->create();
        AgencyInvitation::factory()->create([
            'agency_id' => $user->agency_id,
            'invited_by_id' => $user->id,
            'name' => 'Pending Teammate',
            'email' => 'pending@example.com',
        ]);

        $this->actingAs($user)
            ->get(route('settings.team.index'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('settings/Team')
                ->has('teamMembers', 1)
                ->where('teamMembers.0.email', $user->email)
                ->where('teamMembers.0.role', UserRole::Admin->value)
                ->has('pendingInvites', 1)
                ->where('pendingInvites.0.email', 'pending@example.com')
                ->where('pendingInvites.0.role', UserRole::Staff->value)
            );
    }

    public function test_company_users_can_invite_teammates_by_email(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('settings.team.store'), [
            'name' => 'Ariunaa Bat',
            'email' => 'ariunaa@example.com',
            'role' => UserRole::Viewer->value,
        ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('settings.team.index'));

        $invitation = AgencyInvitation::query()->firstOrFail();

        $this->assertSame($user->agency_id, $invitation->agency_id);
        $this->assertSame($user->id, $invitation->invited_by_id);
        $this->assertSame('Ariunaa Bat', $invitation->name);
        $this->assertSame('ariunaa@example.com', $invitation->email);
        $this->assertSame(UserRole::Viewer, $invitation->role);
        $this->assertTrue($invitation->expires_at->isFuture());

        Notification::assertSentOnDemand(TeamInvitationNotification::class, function (
            TeamInvitationNotification $notification,
            array $channels,
            object $notifiable,
        ) use ($invitation): bool {
            return in_array('mail', $channels, true)
                && $notifiable->routeNotificationFor('mail', $notification) === 'ariunaa@example.com'
                && str_contains($notification->acceptanceUrl(), "/team-invitations/{$invitation->id}/");
        });
    }

    public function test_invited_teammates_can_set_their_password_from_the_email_link(): void
    {
        Notification::fake();

        $inviter = User::factory()->create([
            'name' => 'Naraa Admin',
        ]);

        $this->actingAs($inviter)->post(route('settings.team.store'), [
            'name' => 'Ariunaa Bat',
            'email' => 'ariunaa@example.com',
            'role' => UserRole::CaseManager->value,
        ]);

        $this->post(route('logout'));

        $acceptanceUrl = null;

        Notification::assertSentOnDemand(TeamInvitationNotification::class, function (
            TeamInvitationNotification $notification,
        ) use (&$acceptanceUrl): bool {
            $acceptanceUrl = $notification->acceptanceUrl();

            return true;
        });

        $this->assertNotNull($acceptanceUrl);

        $this->get($acceptanceUrl)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/AcceptInvitation')
                ->where('invitation.email', 'ariunaa@example.com')
                ->where('invitation.company_name', $inviter->agency?->name)
                ->where('invitation.is_valid', true)
            );

        $response = $this->put($acceptanceUrl, [
            'name' => 'Ariunaa Bat',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $user = User::query()->where('email', 'ariunaa@example.com')->firstOrFail();
        $invitation = AgencyInvitation::query()->firstOrFail();

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
        $this->assertSame($inviter->agency_id, $user->agency_id);
        $this->assertSame(UserRole::CaseManager, $user->role);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertNull($invitation->fresh()->token);
    }

    public function test_existing_users_cannot_be_invited_again(): void
    {
        Notification::fake();

        $user = User::factory()->create();
        $existingUser = User::factory()->create([
            'agency_id' => $user->agency_id,
        ]);

        $this->actingAs($user)
            ->post(route('settings.team.store'), [
                'name' => $existingUser->name,
                'email' => $existingUser->email,
                'role' => UserRole::Staff->value,
            ])
            ->assertSessionHasErrors('email');

        $this->assertDatabaseCount('agency_invitations', 0);
        Notification::assertNothingSent();
    }

    public function test_expired_invites_show_as_invalid_and_cannot_be_accepted(): void
    {
        $inviter = User::factory()->create();
        $token = 'expired-token';
        $invitation = AgencyInvitation::factory()->create([
            'agency_id' => $inviter->agency_id,
            'invited_by_id' => $inviter->id,
            'email' => 'expired@example.com',
            'token' => hash('sha256', $token),
            'expires_at' => now()->subDay(),
        ]);

        $acceptanceUrl = route('team-invitations.accept', [
            'agencyInvitation' => $invitation,
            'token' => $token,
        ]);

        $this->get($acceptanceUrl)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('auth/AcceptInvitation')
                ->where('invitation.email', 'expired@example.com')
                ->where('invitation.is_valid', false)
            );

        $this->put(route('team-invitations.store', [
            'agencyInvitation' => $invitation,
            'token' => $token,
        ]), [
            'name' => 'Expired User',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertSessionHasErrors('name');

        $this->assertDatabaseMissing('users', [
            'email' => 'expired@example.com',
        ]);
    }
}
