<?php

namespace Database\Seeders;

use App\Actions\InstantiateVisaCaseChecklistAction;
use App\Actions\InstantiateVisaCaseTasksAction;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\VisaCasePriority;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\CommunicationTemplate;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tag;
use App\Models\TeamInvitation;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaType;
use App\Models\VisaWorkflowStage;
use App\Support\AccessControl;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function __construct(
        private readonly InstantiateVisaCaseChecklistAction $instantiateChecklist,
        private readonly InstantiateVisaCaseTasksAction $instantiateTasks,
    ) {
    }

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        BusinessSetting::query()->firstOrCreate([], [
            'business_name' => 'Agency',
            'contact_email' => 'hello@agency.test',
            'contact_phone' => '+1 (800) 555-0117',
            'contact_address' => 'Ulaanbaatar City Centre',
            'bank_name' => 'Khan Bank',
            'bank_account' => '100200300400',
            'default_locale' => 'en',
            'sms_provider' => 'log',
            'sms_sender' => 'Agency',
            'multi_branch_enabled' => false,
            'multi_branch_ready' => true,
        ]);

        collect([
            [
                'name' => 'Case status email',
                'key' => 'case-status-change',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Your case {{case_reference}} has moved to {{stage_name}}',
                'body' => 'Hi {{applicant_name}}, your {{visa_type}} case for {{country_name}} is now at {{stage_name}}. If you have questions, reply to your case team and we will help.',
                'is_active' => true,
            ],
            [
                'name' => 'Case status SMS',
                'key' => 'case-status-change',
                'channel' => 'sms',
                'locale' => 'en',
                'subject' => null,
                'body' => 'Agency update: case {{case_reference}} is now at {{stage_name}}.',
                'is_active' => true,
            ],
            [
                'name' => 'Document request email',
                'key' => 'document-request',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'We need a document for case {{case_reference}}',
                'body' => 'Hi {{applicant_name}}, we need a document for your {{visa_type}} case. {{message_body}}',
                'is_active' => true,
            ],
            [
                'name' => 'Document request SMS',
                'key' => 'document-request',
                'channel' => 'sms',
                'locale' => 'en',
                'subject' => null,
                'body' => 'Agency needs a document for case {{case_reference}}. {{message_body}}',
                'is_active' => true,
            ],
            [
                'name' => 'Payment reminder email',
                'key' => 'payment-reminder',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Payment reminder for case {{case_reference}}',
                'body' => 'Hi {{applicant_name}}, this is a reminder about your payment for case {{case_reference}}. {{message_body}}',
                'is_active' => true,
            ],
            [
                'name' => 'Appointment reminder email',
                'key' => 'appointment-reminder',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'Appointment reminder for case {{case_reference}}',
                'body' => 'Hi {{applicant_name}}, this is a reminder about your appointment for case {{case_reference}}. {{message_body}}',
                'is_active' => true,
            ],
            [
                'name' => 'Case message email',
                'key' => 'case-message',
                'channel' => 'email',
                'locale' => 'en',
                'subject' => 'A new update for case {{case_reference}}',
                'body' => 'Hi {{applicant_name}}, your visa team sent a new update for case {{case_reference}}. {{message_body}}',
                'is_active' => true,
            ],
            [
                'name' => 'Case message SMS',
                'key' => 'case-message',
                'channel' => 'sms',
                'locale' => 'en',
                'subject' => null,
                'body' => 'Agency update for {{case_reference}}: {{message_body}}',
                'is_active' => true,
            ],
        ])->each(fn (array $template) => CommunicationTemplate::query()->firstOrCreate(
            [
                'key' => $template['key'],
                'channel' => $template['channel'],
                'locale' => $template['locale'],
            ],
            $template,
        ));

        $branches = collect([
            ['name' => 'Ulaanbaatar HQ', 'slug' => 'ulaanbaatar-hq', 'code' => 'UBHQ', 'is_active' => true],
            ['name' => 'Seoul Desk', 'slug' => 'seoul-desk', 'code' => 'SEOU', 'is_active' => true],
            ['name' => 'Sydney Desk', 'slug' => 'sydney-desk', 'code' => 'SYDN', 'is_active' => true],
        ])->map(fn (array $branch) => Branch::query()->firstOrCreate(['slug' => $branch['slug']], $branch));

        $permissions = collect(AccessControl::permissions())
            ->map(fn (array $permission) => Permission::query()->firstOrCreate(
                ['name' => $permission['name']],
                $permission,
            ));

        $roles = collect(AccessControl::defaultRoles())
            ->map(function (array $roleData) use ($permissions): Role {
                $role = Role::query()->firstOrCreate(
                    ['slug' => $roleData['slug']],
                    collect($roleData)->except('permissions')->all(),
                );

                $role->permissions()->sync(
                    $permissions->whereIn('name', $roleData['permissions'])->pluck('id')->all(),
                );

                return $role;
            });

        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'branch_id' => $branches[0]->id,
            'job_title' => 'Super Admin',
        ]);
        $user->roles()->sync([$roles->firstWhere('slug', 'super-admin')->id]);

        $manager = User::factory()->create([
            'name' => 'Ariun Manager',
            'email' => 'manager@example.com',
            'branch_id' => $branches[0]->id,
            'job_title' => 'Operations Manager',
        ]);
        $manager->roles()->sync([$roles->firstWhere('slug', 'manager')->id]);

        $agent = User::factory()->create([
            'name' => 'Sumiya Agent',
            'email' => 'agent@example.com',
            'branch_id' => $branches[1]->id,
            'job_title' => 'Senior Agent',
        ]);
        $agent->roles()->sync([$roles->firstWhere('slug', 'agent')->id]);

        $support = User::factory()->create([
            'name' => 'Mina Support',
            'email' => 'support@example.com',
            'branch_id' => $branches[2]->id,
            'job_title' => 'Applicant Support',
        ]);
        $support->roles()->sync([$roles->firstWhere('slug', 'support')->id]);

        TeamInvitation::query()->firstOrCreate(
            ['email' => 'new.agent@example.com'],
            [
                'name' => 'New Agent',
                'job_title' => 'Agent',
                'branch_id' => $branches[0]->id,
                'role_id' => $roles->firstWhere('slug', 'agent')->id,
                'invited_by_user_id' => $manager->id,
                'token' => \Illuminate\Support\Str::random(64),
                'expires_at' => now()->addDays(7),
                'accepted_at' => null,
            ],
        );

        $tags = collect([
            ['name' => 'Student', 'slug' => 'student', 'color' => 'blue'],
            ['name' => 'Skilled Worker', 'slug' => 'skilled-worker', 'color' => 'emerald'],
            ['name' => 'Tourist', 'slug' => 'tourist', 'color' => 'amber'],
        ])->map(fn (array $tag) => Tag::query()->firstOrCreate(['slug' => $tag['slug']], $tag));

        $leads = Lead::factory()
            ->count(6)
            ->sequence(
                ['assigned_to_user_id' => $manager->id, 'source' => LeadSource::Website, 'status' => LeadStatus::New, 'interested_visa_type' => 'Student'],
                ['assigned_to_user_id' => $manager->id, 'source' => LeadSource::Referral, 'status' => LeadStatus::Contacted, 'interested_visa_type' => 'Tourist'],
                ['assigned_to_user_id' => $agent->id, 'source' => LeadSource::WalkIn, 'status' => LeadStatus::Qualified, 'interested_visa_type' => 'Skilled Worker'],
                ['assigned_to_user_id' => $agent->id, 'source' => LeadSource::Social, 'status' => LeadStatus::Applied, 'interested_visa_type' => 'Student'],
                ['assigned_to_user_id' => $support->id, 'source' => LeadSource::Website, 'status' => LeadStatus::Approved, 'interested_visa_type' => 'Tourist'],
                ['assigned_to_user_id' => $support->id, 'source' => LeadSource::Referral, 'status' => LeadStatus::Rejected, 'interested_visa_type' => 'Skilled Worker'],
            )
            ->create();

        $leads->each(function (Lead $lead, int $index) use ($tags, $user): void {
            $lead->tags()->sync([$tags[$index % $tags->count()]->id]);
            $lead->notes()->create([
                'body' => 'Seeded intake note for demo workflows.',
                'created_by_user_id' => $user->id,
            ]);
            $lead->activities()->create([
                'event' => 'lead.created',
                'description' => 'Lead created from seeder.',
                'caused_by_user_id' => $user->id,
            ]);
            $lead->statusHistories()->create([
                'from_status' => null,
                'to_status' => $lead->status->value,
                'changed_by_user_id' => $user->id,
                'changed_at' => now(),
            ]);
        });

        $this->call([
            AustralianVisaCatalogSeeder::class,
            AdditionalVisaCatalogSeeder::class,
            VisaDocumentTemplateSeeder::class,
            VisaTaskTemplateSeeder::class,
        ]);

        $australia = TargetCountry::query()->firstWhere('slug', 'australia');
        $studentVisa = VisaType::query()
            ->where('target_country_id', $australia?->id)
            ->firstWhere('slug', 'student-500');

        abort_if($australia === null || $studentVisa === null, 500, 'Australian visa seed data was not created as expected.');

        $workflow = VisaWorkflowStage::query()
            ->where('visa_type_id', $studentVisa->id)
            ->orderBy('position')
            ->get();

        $applicant = $leads->first()?->applicant;

        if ($applicant !== null) {
            $visaCase = VisaCase::query()->firstOrCreate(
                ['applicant_id' => $applicant->id, 'reference_code' => 'VC-DEMO-001'],
                [
                    'visa_type_id' => $studentVisa->id,
                    'target_country_id' => $australia->id,
                    'branch_id' => $manager->branch_id,
                    'assigned_to_user_id' => $manager->id,
                    'current_stage_id' => $workflow[1]->id,
                    'priority' => VisaCasePriority::Urgent,
                    'expected_submission_at' => now()->addDays(10)->toDateString(),
                    'expected_decision_at' => now()->addDays(45)->toDateString(),
                ],
            );

            if ($visaCase->documents()->count() === 0) {
                $this->instantiateChecklist->execute($visaCase, $studentVisa->load('documentTemplates'));
            }

            if ($visaCase->tasks()->count() === 0) {
                $this->instantiateTasks->execute($visaCase, $studentVisa, $workflow[1]);
            }

            $visaCase->notes()->create([
                'body' => 'Internal reminder: verify financial capacity documents before submission.',
                'created_by_user_id' => $user->id,
                'is_client_visible' => false,
            ]);

            $visaCase->notes()->create([
                'body' => 'We are reviewing your documents and will update you once everything is ready for submission.',
                'created_by_user_id' => $user->id,
                'is_client_visible' => true,
            ]);

            $visaCase->stageHistories()->create([
                'from_stage_id' => $workflow[0]->id,
                'to_stage_id' => $workflow[1]->id,
                'changed_by_user_id' => $user->id,
                'changed_at' => now(),
            ]);

            $visaCase->activities()->create([
                'event' => 'visa_case.created',
                'description' => 'Visa case created from seeded data.',
                'caused_by_user_id' => $user->id,
            ]);

            $visaCase->documents()
                ->where('name', 'Passport Copy')
                ->first()?->update([
                    'status' => 'pending',
                ]);
        }
    }
}
