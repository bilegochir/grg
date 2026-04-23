<?php

namespace Tests\Feature\Feature\Portal;

use App\Enums\VisaCaseStatus;
use App\Enums\VisaRequirementStatus;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseRequirement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class ClientPortalTest extends TestCase
{
    use RefreshDatabase;

    public function test_portal_login_page_is_accessible(): void
    {
        $this->get(route('portal.login'))
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('portal/Login')
                ->where('portal.context', null)
            );
    }

    public function test_portal_routes_redirect_to_login_when_not_authenticated(): void
    {
        $client = Client::factory()->create([
            'portal_token' => '3feaa6b8-5519-42ca-b4b2-2227d60b6141',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $this->get(route('portal.show', $client->portal_token))
            ->assertRedirect(route('portal.login', ['portal' => $client->portal_token]));
    }

    public function test_first_visit_from_private_link_redirects_to_password_setup(): void
    {
        $client = Client::factory()->create([
            'email' => 'amina@example.com',
            'portal_token' => '9e220657-2b4b-4fbe-98da-ec98d24d0cd4',
        ]);

        $this->get(route('portal.show', $client->portal_token))
            ->assertRedirect(route('portal.password.edit', $client->portal_token));
    }

    public function test_customer_can_create_a_password_after_first_login(): void
    {
        $client = Client::factory()->create([
            'portal_token' => '1a18f2a3-b5c2-4f89-b9f2-d6fe8de2c4fd',
        ]);

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->put(route('portal.password.update', $client->portal_token), [
            'password' => 'new-portal-pass',
            'password_confirmation' => 'new-portal-pass',
        ]);

        $response
            ->assertRedirect(route('portal.show', $client->portal_token))
            ->assertSessionHas('success', 'Your password is ready. You can now use it each time you sign in.');

        $client->refresh();

        $this->assertTrue($client->hasPortalPassword());
        $this->assertTrue($client->portalPasswordIsValid('new-portal-pass'));
    }

    public function test_customer_can_log_in_with_saved_password(): void
    {
        $client = Client::factory()->create([
            'email' => 'portal@example.com',
            'portal_token' => '7d9f67c2-16e1-494f-b790-90f1b2a3e2ea',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $this->post(route('portal.login.store'), [
            'email' => 'portal@example.com',
            'password' => 'secret-pass-123',
            'portal_token' => $client->portal_token,
        ])
            ->assertRedirect(route('portal.show', $client->portal_token));
    }

    public function test_portal_login_rejects_an_invalid_password(): void
    {
        $client = Client::factory()->create([
            'email' => 'portal@example.com',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $this->from(route('portal.login'))
            ->post(route('portal.login.store'), [
                'email' => 'portal@example.com',
                'password' => 'wrong-pass',
            ])
            ->assertRedirect(route('portal.login'))
            ->assertSessionHasErrors([
                'password' => 'The email or password is incorrect.',
            ]);
    }

    public function test_customer_can_change_password_after_setup(): void
    {
        $client = Client::factory()->create([
            'portal_token' => '83f71361-9bf5-46bc-bb90-d9b33495c5e8',
        ]);
        $client->updatePortalPassword('old-pass-123');

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->put(route('portal.password.update', $client->portal_token), [
            'current_password' => 'old-pass-123',
            'password' => 'fresh-pass-456',
            'password_confirmation' => 'fresh-pass-456',
        ]);

        $response
            ->assertRedirect(route('portal.show', $client->portal_token))
            ->assertSessionHas('success', 'Your password was updated.');

        $client->refresh();

        $this->assertTrue($client->portalPasswordIsValid('fresh-pass-456'));
    }

    public function test_password_change_requires_the_current_password_once_it_has_been_set(): void
    {
        $client = Client::factory()->create([
            'portal_token' => 'af9eb9e7-df4d-4e40-991e-5c3447b80e1d',
        ]);
        $client->updatePortalPassword('old-pass-123');

        $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->from(route('portal.password.edit', $client->portal_token))
            ->put(route('portal.password.update', $client->portal_token), [
                'current_password' => 'wrong-pass',
                'password' => 'fresh-pass-456',
                'password_confirmation' => 'fresh-pass-456',
            ])
            ->assertRedirect(route('portal.password.edit', $client->portal_token))
            ->assertSessionHasErrors([
                'current_password' => 'Your current password is incorrect.',
            ]);
    }

    public function test_portal_page_shows_client_progress_profile_and_documents(): void
    {
        $owner = User::factory()->create();
        $client = Client::factory()->create([
            'agency_id' => $owner->agency_id,
            'owner_id' => $owner->id,
            'full_name' => 'Amina Batsukh',
            'portal_token' => '3feaa6b8-5519-42ca-b4b2-2227d60b6141',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $visaCase = VisaCase::factory()->create([
            'agency_id' => $owner->agency_id,
            'client_id' => $client->id,
            'visa_type' => 'Student visa',
            'destination_country' => 'Australia',
            'reference_code' => 'VC-2401',
            'status' => VisaCaseStatus::DocumentsPending,
        ]);
        $requirement = VisaCaseRequirement::factory()->create([
            'visa_case_id' => $visaCase->id,
            'category' => 'Identity',
            'label' => 'Passport bio page',
            'status' => VisaRequirementStatus::Requested,
            'is_required' => true,
            'is_completed' => false,
            'sort_order' => 1,
        ]);

        Attachment::factory()->for($client, 'attachable')->create([
            'agency_id' => $owner->agency_id,
            'uploaded_by_id' => null,
            'original_name' => 'profile-note.pdf',
        ]);
        Attachment::factory()->for($requirement, 'attachable')->create([
            'agency_id' => $owner->agency_id,
            'uploaded_by_id' => null,
            'original_name' => 'passport.pdf',
        ]);

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->get(route('portal.show', $client->portal_token));

        $response->assertOk();
        $response->assertInertia(fn (AssertableInertia $page) => $page
            ->component('portal/Show')
            ->where('portal.client.full_name', 'Amina Batsukh')
            ->has('portal.clientAttachments', 1)
            ->where('portal.visaCases.0.reference_code', 'VC-2401')
            ->where('portal.visaCases.0.requirements.0.label', 'Passport bio page')
        );
    }

    public function test_portal_profile_updates_client_details(): void
    {
        $client = Client::factory()->create([
            'portal_token' => '94d44893-f324-4c19-86fc-dab9d8a13689',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->patch(route('portal.profile.update', $client->portal_token), [
            'full_name' => 'Updated Applicant',
            'email' => 'updated@example.com',
            'phone' => '+97670001111',
            'passport_number' => 'MGL998877',
            'current_address' => 'Ulaanbaatar, Mongolia',
            'nationality' => 'Mongolian',
        ]);

        $response
            ->assertRedirect(route('portal.show', $client->portal_token))
            ->assertSessionHas('success', 'Your details were updated successfully.');

        $client->refresh();

        $this->assertSame('Updated Applicant', $client->full_name);
        $this->assertSame('updated@example.com', $client->email);
        $this->assertSame('Ulaanbaatar, Mongolia', $client->current_address);
    }

    public function test_portal_can_upload_general_client_documents(): void
    {
        Storage::fake('local');

        $client = Client::factory()->create([
            'portal_token' => '4efc1aa0-fc79-4206-8b81-03f59797c47d',
        ]);
        $client->updatePortalPassword('secret-pass-123');

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->post(route('portal.attachments.store', $client->portal_token), [
            'attachment' => UploadedFile::fake()->create('passport-copy.pdf', 256, 'application/pdf'),
        ]);

        $response
            ->assertRedirect(route('portal.show', $client->portal_token))
            ->assertSessionHas('success', 'Your document was uploaded successfully.');

        $attachment = $client->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('passport-copy.pdf', $attachment->original_name);
        $this->assertNull($attachment->uploaded_by_id);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_portal_can_upload_requirement_documents(): void
    {
        Storage::fake('local');

        $client = Client::factory()->create([
            'portal_token' => '033f4d2b-ac2f-4e1f-adc8-ae17fd7bcc14',
        ]);
        $client->updatePortalPassword('secret-pass-123');
        $visaCase = VisaCase::factory()->create([
            'agency_id' => $client->agency_id,
            'client_id' => $client->id,
        ]);
        $requirement = VisaCaseRequirement::factory()->create([
            'visa_case_id' => $visaCase->id,
        ]);

        $response = $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->post(route('portal.requirements.attachments.store', [$client->portal_token, $visaCase, $requirement]), [
            'attachment' => UploadedFile::fake()->create('bank-statement.pdf', 320, 'application/pdf'),
        ]);

        $response
            ->assertRedirect(route('portal.show', $client->portal_token))
            ->assertSessionHas('success', 'Your document was uploaded successfully.');

        $attachment = $requirement->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame('bank-statement.pdf', $attachment->original_name);
        $this->assertNull($attachment->uploaded_by_id);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_portal_cannot_download_attachments_from_another_client(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('attachments/test/secret.pdf', 'secret');

        $client = Client::factory()->create([
            'portal_token' => 'd81eb85e-e35a-4d07-9c9c-b79c53e5f1f5',
        ]);
        $client->updatePortalPassword('secret-pass-123');
        $otherClient = Client::factory()->create();
        $attachment = Attachment::factory()->for($otherClient, 'attachable')->create([
            'agency_id' => $otherClient->agency_id,
            'uploaded_by_id' => null,
            'path' => 'attachments/test/secret.pdf',
            'original_name' => 'secret.pdf',
        ]);

        $this->withSession([
            Client::PORTAL_SESSION_KEY => $client->id,
        ])->get(route('portal.attachments.download', [$client->portal_token, $attachment]))
            ->assertNotFound();
    }
}
