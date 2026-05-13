<?php

namespace App\Http\Controllers;

use App\Actions\ProvisionVisaTypeDefaultsAction;
use App\Http\Requests\StoreCommunicationTemplateRequest;
use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\StoreDocumentTemplateSettingRequest;
use App\Http\Requests\StoreTargetCountryRequest;
use App\Http\Requests\StoreVisaTypeSettingRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Requests\UpdateBusinessSettingRequest;
use App\Http\Requests\UpdateCommunicationTemplateRequest;
use App\Http\Requests\UpdateDocumentTemplateSettingRequest;
use App\Http\Requests\UpdateTargetCountryRequest;
use App\Http\Requests\UpdateVisaTypeSettingRequest;
use App\Models\BusinessSetting;
use App\Models\Branch;
use App\Models\CommunicationTemplate;
use App\Models\DocumentTemplate;
use App\Models\TargetCountry;
use App\Models\VisaType;
use App\Models\VisaTaskTemplate;
use App\Models\VisaFormTemplate;
use App\Models\VisaWorkflowStage;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $businessSetting = BusinessSetting::current();

        $countries = TargetCountry::query()
            ->withCount('visaTypes')
            ->orderBy('name')
            ->get()
            ->map(fn (TargetCountry $country): array => [
                'id' => $country->id,
                'name' => $country->name,
                'slug' => $country->slug,
                'is_active' => $country->is_active,
                'visa_types_count' => $country->visa_types_count,
            ])->values();

        $visaTypes = VisaType::query()
            ->with(['country:id,name'])
            ->withCount(['workflowStages', 'documentTemplates', 'taskTemplates', 'cases'])
            ->orderBy('name')
            ->get()
            ->map(function (VisaType $visaType): array {
                $lastReviewedAt = $visaType->official_last_reviewed_at;
                $needsReview = $lastReviewedAt === null || $lastReviewedAt->lt(now()->subMonths(6));

                return [
                    'id' => $visaType->id,
                    'name' => $visaType->name,
                    'code' => $visaType->code,
                    'official_subclass' => $visaType->official_subclass,
                    'slug' => $visaType->slug,
                    'is_active' => $visaType->is_active,
                    'country' => [
                        'id' => $visaType->country->id,
                        'name' => $visaType->country->name,
                    ],
                    'submission_sla_days' => $visaType->submission_sla_days,
                    'decision_sla_days' => $visaType->decision_sla_days,
                    'validity_months' => $visaType->validity_months,
                    'stay_duration_days' => $visaType->stay_duration_days,
                    'entry_type' => $visaType->entry_type,
                    'service_scope' => $visaType->service_scope,
                    'priority_support' => $visaType->priority_support,
                    'dependants_allowed' => $visaType->dependants_allowed,
                    'biometrics_required' => $visaType->biometrics_required,
                    'interview_required' => $visaType->interview_required,
                    'medical_required' => $visaType->medical_required,
                    'police_clearance_required' => $visaType->police_clearance_required,
                    'financial_proof_required' => $visaType->financial_proof_required,
                    'checklist_intro' => $visaType->checklist_intro,
                    'portal_guidance' => $visaType->portal_guidance,
                    'notes' => $visaType->notes,
                    'official_reference_url' => $visaType->official_reference_url,
                    'official_summary' => $visaType->official_summary,
                    'official_requirements' => $visaType->official_requirements ?? [],
                    'official_last_reviewed_at' => $lastReviewedAt?->toDateString(),
                    'policy_effective_date' => $visaType->policy_effective_date?->toDateString(),
                    'official_change_notes' => $visaType->official_change_notes,
                    'needs_review' => $needsReview,
                    'review_status_label' => $needsReview ? 'Needs review' : 'Reviewed',
                    'review_status_tone' => $needsReview ? 'amber' : 'emerald',
                    'workflow_stages_count' => $visaType->workflow_stages_count,
                    'document_templates_count' => $visaType->document_templates_count,
                    'task_templates_count' => $visaType->task_templates_count,
                    'cases_count' => $visaType->cases_count,
                ];
            })->values();

        $workflowStages = VisaWorkflowStage::query()
            ->with(['visaType:id,name,target_country_id', 'visaType.country:id,name'])
            ->withCount(['cases', 'taskTemplates'])
            ->orderBy('visa_type_id')
            ->orderBy('position')
            ->get()
            ->map(fn (VisaWorkflowStage $stage): array => [
                'id' => $stage->id,
                'visa_type_id' => $stage->visa_type_id,
                'visa_type_name' => $stage->visaType->name,
                'country_name' => $stage->visaType->country->name,
                'name' => $stage->name,
                'slug' => $stage->slug,
                'position' => $stage->position,
                'color' => $stage->color,
                'is_default' => $stage->is_default,
                'is_closed' => $stage->is_closed,
                'cases_count' => $stage->cases_count,
                'task_templates_count' => $stage->task_templates_count,
            ])->values();

        $taskTemplates = VisaTaskTemplate::query()
            ->with(['visaType:id,name,target_country_id', 'visaType.country:id,name', 'stage:id,name'])
            ->withCount('caseTasks')
            ->orderBy('visa_type_id')
            ->orderBy('position')
            ->get()
            ->map(fn (VisaTaskTemplate $task): array => [
                'id' => $task->id,
                'visa_type_id' => $task->visa_type_id,
                'visa_type_name' => $task->visaType->name,
                'country_name' => $task->visaType->country->name,
                'stage_id' => $task->visa_workflow_stage_id,
                'stage_name' => $task->stage?->name,
                'name' => $task->name,
                'slug' => $task->slug,
                'description' => $task->description,
                'position' => $task->position,
                'due_days' => $task->due_days,
                'is_required' => $task->is_required,
                'is_client_visible' => $task->is_client_visible,
                'case_tasks_count' => $task->case_tasks_count,
            ])->values();

        $documentTemplates = DocumentTemplate::query()
            ->with(['visaType:id,name,target_country_id', 'visaType.country:id,name'])
            ->withCount('caseDocuments')
            ->orderBy('visa_type_id')
            ->orderBy('position')
            ->get()
            ->map(fn (DocumentTemplate $template): array => [
                'id' => $template->id,
                'visa_type_id' => $template->visa_type_id,
                'visa_type_name' => $template->visaType->name,
                'country_name' => $template->visaType->country->name,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'category' => $template->category,
                'client_instructions' => $template->client_instructions,
                'agent_guidance' => $template->agent_guidance,
                'sample_hint' => $template->sample_hint,
                'accepted_file_types' => $template->accepted_file_types ?? [],
                'max_files' => $template->max_files,
                'max_file_size_mb' => $template->max_file_size_mb,
                'due_days' => $template->due_days,
                'is_repeatable' => $template->is_repeatable,
                'position' => $template->position,
                'is_required' => $template->is_required,
                'tracks_expiry' => $template->tracks_expiry,
                'case_documents_count' => $template->case_documents_count,
            ])->values();

        $communicationTemplates = CommunicationTemplate::query()
            ->orderBy('channel')
            ->orderBy('name')
            ->get()
            ->map(fn (CommunicationTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'key' => $template->key,
                'channel' => $template->channel,
                'locale' => $template->locale,
                'subject' => $template->subject,
                'body' => $template->body,
                'is_active' => $template->is_active,
            ])->values();

        $branches = ($this->workspace()->selectedBranchId($request->user()) !== null
            ? Branch::query()->whereKey($this->workspace()->selectedBranchId($request->user()))
            : Branch::query())
            ->withCount(['staff', 'cases'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'is_active' => $branch->is_active,
                'staff_count' => $branch->staff_count,
                'cases_count' => $branch->cases_count,
            ])->values();

        return Inertia::render('Settings/Index', [
            'businessSetting' => [
                'business_name' => $businessSetting->business_name,
                'logo_url' => $businessSetting->logoUrl(),
                'contact_email' => $businessSetting->contact_email,
                'contact_phone' => $businessSetting->contact_phone,
                'contact_address' => $businessSetting->contact_address,
                'default_locale' => $businessSetting->default_locale,
                'sms_provider' => $businessSetting->sms_provider,
                'sms_sender' => $businessSetting->sms_sender,
                'multi_branch_enabled' => $businessSetting->multi_branch_enabled,
                'multi_branch_ready' => $businessSetting->multi_branch_ready,
            ],
            'countries' => $countries,
            'visaTypes' => $visaTypes,
            'workflowStages' => $workflowStages,
            'taskTemplates' => $taskTemplates,
            'documentTemplates' => $documentTemplates,
            'communicationTemplates' => $communicationTemplates,
            'formTemplates' => VisaFormTemplate::query()
                ->with('visaType:id,name')
                ->latest()
                ->get()
                ->map(fn (VisaFormTemplate $ft): array => [
                    'id'              => $ft->id,
                    'visa_type_id'    => $ft->visa_type_id,
                    'visa_type_name'  => $ft->visaType?->name ?? '—',
                    'name'            => $ft->name,
                    'description'     => $ft->description,
                    'original_filename' => $ft->original_filename,
                    'field_mapping'   => $ft->field_mapping ?? [],
                    'is_active'       => $ft->is_active,
                ])->values(),
            'availableFields' => VisaFormTemplate::availableFields(),
            'branches' => $branches,
            'locales' => [
                ['value' => 'en', 'label' => 'English'],
                ['value' => 'mn', 'label' => 'Mongolian'],
            ],
            'smsProviders' => [
                ['value' => 'log', 'label' => 'Log only'],
                ['value' => 'twilio', 'label' => 'Twilio'],
                ['value' => 'local_gateway', 'label' => 'Local gateway'],
            ],
            'appLocale' => app()->getLocale(),
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    public function updateBusiness(UpdateBusinessSettingRequest $request): RedirectResponse
    {
        $businessSetting = BusinessSetting::current();

        $payload = $request->safe()->except('logo');

        if ($request->hasFile('logo')) {
            if ($businessSetting->logo_path !== null) {
                Storage::disk('public')->delete($businessSetting->logo_path);
            }

            $payload['logo_path'] = $request->file('logo')->store('business-logos', 'public');
        }

        $businessSetting->update([
            ...$payload,
            'multi_branch_enabled' => $request->boolean('multi_branch_enabled'),
        ]);

        return back()->with('success', 'Business settings updated.');
    }

    public function logo()
    {
        $businessSetting = BusinessSetting::current();

        abort_unless(
            $businessSetting->logo_path !== null
            && Storage::disk('public')->exists($businessSetting->logo_path),
            404,
        );

        return Storage::disk('public')->response($businessSetting->logo_path, headers: [
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }

    public function storeCountry(StoreTargetCountryRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $slug = $this->uniqueCountrySlug($request->string('slug')->toString() ?: $name);

        TargetCountry::query()->create([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Country added.');
    }

    public function updateCountry(UpdateTargetCountryRequest $request, TargetCountry $country): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $slug = $this->uniqueCountrySlug($request->string('slug')->toString() ?: $name, $country->id);

        $country->update([
            'name' => $name,
            'slug' => $slug,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Country updated.');
    }

    public function destroyCountry(TargetCountry $country): RedirectResponse
    {
        if ($country->visaTypes()->exists() || $country->cases()->exists()) {
            return back()->with('error', 'This country is already in use. Deactivate it instead of deleting it.');
        }

        $country->delete();

        return back()->with('success', 'Country removed.');
    }

    public function storeBranch(StoreBranchRequest $request): RedirectResponse
    {
        abort_if($this->workspace()->selectedBranchId($request->user()) !== null, 403);

        $name = $request->string('name')->toString();

        Branch::query()->create([
            'name' => $name,
            'slug' => $this->uniqueBranchSlug($request->string('slug')->toString() ?: $name),
            'code' => $request->string('code')->toString() ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Branch added.');
    }

    public function updateBranch(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        if ($this->workspace()->selectedBranchId($request->user()) !== null) {
            abort_unless($branch->id === $this->workspace()->selectedBranchId($request->user()), 404);
        }

        $name = $request->string('name')->toString();

        $branch->update([
            'name' => $name,
            'slug' => $this->uniqueBranchSlug($request->string('slug')->toString() ?: $name, $branch->id),
            'code' => $request->string('code')->toString() ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Branch updated.');
    }

    public function storeVisaType(
        StoreVisaTypeSettingRequest $request,
        ProvisionVisaTypeDefaultsAction $provisionDefaults,
    ): RedirectResponse {
        $name = $request->string('name')->toString();

        $visaType = DB::transaction(function () use ($request, $name, $provisionDefaults): VisaType {
            $visaType = VisaType::query()->create([
                'target_country_id' => $request->integer('target_country_id'),
                'name' => $name,
                'code' => $request->string('code')->toString() ?: null,
                'official_subclass' => $request->string('official_subclass')->toString() ?: null,
                'slug' => $this->uniqueVisaTypeSlug(
                    $request->integer('target_country_id'),
                    $request->string('slug')->toString() ?: $name,
                ),
                'is_active' => $request->boolean('is_active', true),
                'submission_sla_days' => $request->filled('submission_sla_days') ? $request->integer('submission_sla_days') : null,
                'decision_sla_days' => $request->filled('decision_sla_days') ? $request->integer('decision_sla_days') : null,
                'validity_months' => $request->filled('validity_months') ? $request->integer('validity_months') : null,
                'stay_duration_days' => $request->filled('stay_duration_days') ? $request->integer('stay_duration_days') : null,
                'entry_type' => $request->string('entry_type')->toString() ?: null,
                'service_scope' => $request->string('service_scope')->toString() ?: null,
                'priority_support' => $request->boolean('priority_support', true),
                'dependants_allowed' => $request->boolean('dependants_allowed'),
                'biometrics_required' => $request->boolean('biometrics_required'),
                'interview_required' => $request->boolean('interview_required'),
                'medical_required' => $request->boolean('medical_required'),
                'police_clearance_required' => $request->boolean('police_clearance_required'),
                'financial_proof_required' => $request->boolean('financial_proof_required'),
                'checklist_intro' => $request->string('checklist_intro')->toString() ?: null,
                'portal_guidance' => $request->string('portal_guidance')->toString() ?: null,
                'notes' => $request->string('notes')->toString() ?: null,
                'official_reference_url' => $request->string('official_reference_url')->toString() ?: null,
                'official_summary' => $request->string('official_summary')->toString() ?: null,
                'official_requirements' => $request->input('official_requirements', []),
                'official_last_reviewed_at' => $request->date('official_last_reviewed_at'),
                'policy_effective_date' => $request->date('policy_effective_date'),
                'official_change_notes' => $request->string('official_change_notes')->toString() ?: null,
            ]);

            $provisionDefaults->execute($visaType);

            return $visaType;
        });

        return back()->with('success', "Visa type {$visaType->name} added.");
    }

    public function updateVisaType(UpdateVisaTypeSettingRequest $request, VisaType $visaType): RedirectResponse
    {
        $name = $request->string('name')->toString();

        $visaType->update([
            'target_country_id' => $request->integer('target_country_id'),
            'name' => $name,
            'code' => $request->string('code')->toString() ?: null,
            'official_subclass' => $request->string('official_subclass')->toString() ?: null,
            'slug' => $this->uniqueVisaTypeSlug(
                $request->integer('target_country_id'),
                $request->string('slug')->toString() ?: $name,
                $visaType->id,
            ),
            'is_active' => $request->boolean('is_active'),
            'submission_sla_days' => $request->filled('submission_sla_days') ? $request->integer('submission_sla_days') : null,
            'decision_sla_days' => $request->filled('decision_sla_days') ? $request->integer('decision_sla_days') : null,
            'validity_months' => $request->filled('validity_months') ? $request->integer('validity_months') : null,
            'stay_duration_days' => $request->filled('stay_duration_days') ? $request->integer('stay_duration_days') : null,
            'entry_type' => $request->string('entry_type')->toString() ?: null,
            'service_scope' => $request->string('service_scope')->toString() ?: null,
            'priority_support' => $request->boolean('priority_support', true),
            'dependants_allowed' => $request->boolean('dependants_allowed'),
            'biometrics_required' => $request->boolean('biometrics_required'),
            'interview_required' => $request->boolean('interview_required'),
            'medical_required' => $request->boolean('medical_required'),
            'police_clearance_required' => $request->boolean('police_clearance_required'),
            'financial_proof_required' => $request->boolean('financial_proof_required'),
            'checklist_intro' => $request->string('checklist_intro')->toString() ?: null,
            'portal_guidance' => $request->string('portal_guidance')->toString() ?: null,
            'notes' => $request->string('notes')->toString() ?: null,
            'official_reference_url' => $request->string('official_reference_url')->toString() ?: null,
            'official_summary' => $request->string('official_summary')->toString() ?: null,
            'official_requirements' => $request->input('official_requirements', []),
            'official_last_reviewed_at' => $request->date('official_last_reviewed_at'),
            'policy_effective_date' => $request->date('policy_effective_date'),
            'official_change_notes' => $request->string('official_change_notes')->toString() ?: null,
        ]);

        return back()->with('success', 'Visa type updated.');
    }

    public function destroyVisaType(VisaType $visaType): RedirectResponse
    {
        if ($visaType->cases()->exists() || $visaType->workflowStages()->exists() || $visaType->documentTemplates()->exists() || $visaType->taskTemplates()->exists()) {
            return back()->with('error', 'This visa type already has workflow or case data. Deactivate it instead of deleting it.');
        }

        $visaType->delete();

        return back()->with('success', 'Visa type removed.');
    }

    public function storeDocumentTemplate(StoreDocumentTemplateSettingRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $visaTypeId = $request->integer('visa_type_id');

        DocumentTemplate::query()->create([
            'visa_type_id' => $visaTypeId,
            'name' => $name,
            'slug' => $this->uniqueDocumentTemplateSlug($visaTypeId, $request->string('slug')->toString() ?: $name),
            'description' => $request->string('description')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'client_instructions' => $request->string('client_instructions')->toString() ?: null,
            'agent_guidance' => $request->string('agent_guidance')->toString() ?: null,
            'sample_hint' => $request->string('sample_hint')->toString() ?: null,
            'accepted_file_types' => $this->normalizedFileTypes($request->input('accepted_file_types', [])),
            'max_files' => $request->integer('max_files'),
            'max_file_size_mb' => $request->integer('max_file_size_mb'),
            'due_days' => $request->filled('due_days') ? $request->integer('due_days') : null,
            'is_repeatable' => $request->boolean('is_repeatable'),
            'position' => $request->integer('position'),
            'is_required' => $request->boolean('is_required', true),
            'tracks_expiry' => $request->boolean('tracks_expiry'),
        ]);

        return back()->with('success', 'Checklist template added.');
    }

    public function updateDocumentTemplate(
        UpdateDocumentTemplateSettingRequest $request,
        DocumentTemplate $documentTemplate,
    ): RedirectResponse {
        $name = $request->string('name')->toString();
        $visaTypeId = $request->integer('visa_type_id');

        $documentTemplate->update([
            'visa_type_id' => $visaTypeId,
            'name' => $name,
            'slug' => $this->uniqueDocumentTemplateSlug(
                $visaTypeId,
                $request->string('slug')->toString() ?: $name,
                $documentTemplate->id,
            ),
            'description' => $request->string('description')->toString() ?: null,
            'category' => $request->string('category')->toString() ?: null,
            'client_instructions' => $request->string('client_instructions')->toString() ?: null,
            'agent_guidance' => $request->string('agent_guidance')->toString() ?: null,
            'sample_hint' => $request->string('sample_hint')->toString() ?: null,
            'accepted_file_types' => $this->normalizedFileTypes($request->input('accepted_file_types', [])),
            'max_files' => $request->integer('max_files'),
            'max_file_size_mb' => $request->integer('max_file_size_mb'),
            'due_days' => $request->filled('due_days') ? $request->integer('due_days') : null,
            'is_repeatable' => $request->boolean('is_repeatable'),
            'position' => $request->integer('position'),
            'is_required' => $request->boolean('is_required'),
            'tracks_expiry' => $request->boolean('tracks_expiry'),
        ]);

        return back()->with('success', 'Checklist template updated.');
    }

    public function destroyDocumentTemplate(DocumentTemplate $documentTemplate): RedirectResponse
    {
        if ($documentTemplate->caseDocuments()->exists()) {
            return back()->with('error', 'This checklist template is already attached to case documents and cannot be deleted.');
        }

        $documentTemplate->delete();

        return back()->with('success', 'Checklist template removed.');
    }

    public function storeWorkflowStage(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visa_type_id' => ['required', 'exists:visa_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'color' => ['required', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ]);

        $visaTypeId = (int) $data['visa_type_id'];
        $name = trim($data['name']);

        DB::transaction(function () use ($data, $visaTypeId, $name): void {
            if (! empty($data['is_default'])) {
                VisaWorkflowStage::query()->where('visa_type_id', $visaTypeId)->update(['is_default' => false]);
            }

            VisaWorkflowStage::query()->create([
                'visa_type_id' => $visaTypeId,
                'name' => $name,
                'slug' => $this->uniqueWorkflowStageSlug($visaTypeId, $data['slug'] ?: $name),
                'position' => (int) $data['position'],
                'color' => $data['color'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_closed' => (bool) ($data['is_closed'] ?? false),
            ]);
        });

        return back()->with('success', 'Workflow stage added.');
    }

    public function updateWorkflowStage(Request $request, VisaWorkflowStage $workflowStage): RedirectResponse
    {
        $data = $request->validate([
            'visa_type_id' => ['required', 'exists:visa_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'position' => ['required', 'integer', 'min:1'],
            'color' => ['required', 'string', 'max:50'],
            'is_default' => ['sometimes', 'boolean'],
            'is_closed' => ['sometimes', 'boolean'],
        ]);

        $visaTypeId = (int) $data['visa_type_id'];
        $name = trim($data['name']);

        DB::transaction(function () use ($data, $visaTypeId, $name, $workflowStage): void {
            if (! empty($data['is_default'])) {
                VisaWorkflowStage::query()
                    ->where('visa_type_id', $visaTypeId)
                    ->whereKeyNot($workflowStage->id)
                    ->update(['is_default' => false]);
            }

            $workflowStage->update([
                'visa_type_id' => $visaTypeId,
                'name' => $name,
                'slug' => $this->uniqueWorkflowStageSlug($visaTypeId, $data['slug'] ?: $name, $workflowStage->id),
                'position' => (int) $data['position'],
                'color' => $data['color'],
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_closed' => (bool) ($data['is_closed'] ?? false),
            ]);
        });

        return back()->with('success', 'Workflow stage updated.');
    }

    public function destroyWorkflowStage(VisaWorkflowStage $workflowStage): RedirectResponse
    {
        if ($workflowStage->cases()->exists() || $workflowStage->taskTemplates()->exists()) {
            return back()->with('error', 'This stage is already in use. Reassign cases or task templates before deleting it.');
        }

        $workflowStage->delete();

        return back()->with('success', 'Workflow stage removed.');
    }

    public function storeTaskTemplate(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'visa_type_id' => ['required', 'exists:visa_types,id'],
            'visa_workflow_stage_id' => ['nullable', 'exists:visa_workflow_stages,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['required', 'integer', 'min:1'],
            'due_days' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['sometimes', 'boolean'],
            'is_client_visible' => ['sometimes', 'boolean'],
        ]);

        $visaTypeId = (int) $data['visa_type_id'];
        $name = trim($data['name']);

        VisaTaskTemplate::query()->create([
            'visa_type_id' => $visaTypeId,
            'visa_workflow_stage_id' => $data['visa_workflow_stage_id'] ?: null,
            'name' => $name,
            'slug' => $this->uniqueTaskTemplateSlug($visaTypeId, $data['slug'] ?: $name),
            'description' => $data['description'] ?: null,
            'position' => (int) $data['position'],
            'due_days' => $data['due_days'] !== null ? (int) $data['due_days'] : null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_client_visible' => (bool) ($data['is_client_visible'] ?? false),
        ]);

        return back()->with('success', 'Task template added.');
    }

    public function updateTaskTemplate(Request $request, VisaTaskTemplate $taskTemplate): RedirectResponse
    {
        $data = $request->validate([
            'visa_type_id' => ['required', 'exists:visa_types,id'],
            'visa_workflow_stage_id' => ['nullable', 'exists:visa_workflow_stages,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'position' => ['required', 'integer', 'min:1'],
            'due_days' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['sometimes', 'boolean'],
            'is_client_visible' => ['sometimes', 'boolean'],
        ]);

        $visaTypeId = (int) $data['visa_type_id'];
        $name = trim($data['name']);

        $taskTemplate->update([
            'visa_type_id' => $visaTypeId,
            'visa_workflow_stage_id' => $data['visa_workflow_stage_id'] ?: null,
            'name' => $name,
            'slug' => $this->uniqueTaskTemplateSlug($visaTypeId, $data['slug'] ?: $name, $taskTemplate->id),
            'description' => $data['description'] ?: null,
            'position' => (int) $data['position'],
            'due_days' => $data['due_days'] !== null ? (int) $data['due_days'] : null,
            'is_required' => (bool) ($data['is_required'] ?? false),
            'is_client_visible' => (bool) ($data['is_client_visible'] ?? false),
        ]);

        return back()->with('success', 'Task template updated.');
    }

    public function destroyTaskTemplate(VisaTaskTemplate $taskTemplate): RedirectResponse
    {
        if ($taskTemplate->caseTasks()->exists()) {
            return back()->with('error', 'This task template is already attached to case tasks and cannot be deleted.');
        }

        $taskTemplate->delete();

        return back()->with('success', 'Task template removed.');
    }

    public function storeCommunicationTemplate(StoreCommunicationTemplateRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();
        $key = $this->uniqueCommunicationTemplateKey(
            $request->string('key')->toString() ?: $name,
            $request->string('channel')->toString(),
            $request->string('locale')->toString(),
        );

        CommunicationTemplate::query()->create([
            'name' => $name,
            'key' => $key,
            'channel' => $request->string('channel')->toString(),
            'locale' => $request->string('locale')->toString(),
            'subject' => $request->string('subject')->toString() ?: null,
            'body' => $request->string('body')->toString(),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Message template added.');
    }

    public function updateCommunicationTemplate(
        UpdateCommunicationTemplateRequest $request,
        CommunicationTemplate $communicationTemplate,
    ): RedirectResponse {
        $name = $request->string('name')->toString();
        $channel = $request->string('channel')->toString();
        $locale = $request->string('locale')->toString();

        $communicationTemplate->update([
            'name' => $name,
            'key' => $this->uniqueCommunicationTemplateKey(
                $request->string('key')->toString() ?: $name,
                $channel,
                $locale,
                $communicationTemplate->id,
            ),
            'channel' => $channel,
            'locale' => $locale,
            'subject' => $request->string('subject')->toString() ?: null,
            'body' => $request->string('body')->toString(),
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Message template updated.');
    }

    public function destroyCommunicationTemplate(CommunicationTemplate $communicationTemplate): RedirectResponse
    {
        $communicationTemplate->delete();

        return back()->with('success', 'Message template removed.');
    }

    private function uniqueCountrySlug(string $value, ?int $ignoreId = null): string
    {
        return $this->ensureUniqueSlug(TargetCountry::query(), $value, $ignoreId);
    }

    private function uniqueVisaTypeSlug(int $countryId, string $value, ?int $ignoreId = null): string
    {
        return $this->ensureUniqueSlug(
            VisaType::query()->where('target_country_id', $countryId),
            $value,
            $ignoreId,
        );
    }

    private function uniqueDocumentTemplateSlug(int $visaTypeId, string $value, ?int $ignoreId = null): string
    {
        return $this->ensureUniqueSlug(
            DocumentTemplate::query()->where('visa_type_id', $visaTypeId),
            $value,
            $ignoreId,
        );
    }

    private function uniqueWorkflowStageSlug(int $visaTypeId, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'workflow-stage';
        $slug = $base;
        $counter = 2;

        while (VisaWorkflowStage::query()
            ->where('visa_type_id', $visaTypeId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueTaskTemplateSlug(int $visaTypeId, string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'task-template';
        $slug = $base;
        $counter = 2;

        while (VisaTaskTemplate::query()
            ->where('visa_type_id', $visaTypeId)
            ->where('slug', $slug)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function uniqueBranchSlug(string $value, ?int $ignoreId = null): string
    {
        return $this->ensureUniqueSlug(Branch::query(), $value, $ignoreId);
    }

    private function normalizedFileTypes(array $fileTypes): array
    {
        return collect($fileTypes)
            ->map(fn ($value) => Str::of((string) $value)->trim()->lower()->ltrim('.')->toString())
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueCommunicationTemplateKey(
        string $value,
        string $channel,
        string $locale,
        ?int $ignoreId = null,
    ): string {
        return $this->ensureUniqueSlug(
            CommunicationTemplate::query()->where('channel', $channel)->where('locale', $locale),
            $value,
            $ignoreId,
            'key',
        );
    }

    private function ensureUniqueSlug($query, string $value, ?int $ignoreId = null, string $column = 'slug'): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 1;

        while (
            (clone $query)
                ->when($ignoreId, fn ($builder) => $builder->whereKeyNot($ignoreId))
                ->where($column, $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
