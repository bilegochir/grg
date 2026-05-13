<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Actions\UpdateLeadStatusAction;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LeadController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string'],
            'source' => ['nullable', 'string'],
        ]);

        $leads = $this->workspace()->scopeLeads(Lead::query(), $request->user())
            ->with(['tags', 'assignedTo', 'applicant'])
            ->withCount('notes')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('interested_visa_type', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Lead $lead): array => $this->leadSummary($lead));

        $pipeline = collect(LeadStatus::cases())
            ->map(fn (LeadStatus $status): array => [
                'value' => $status->value,
                'label' => $status->label(),
                'color' => $status->color(),
                'count' => $this->workspace()->scopeLeads(Lead::query(), $request->user())->where('status', $status->value)->count(),
            ]);

        return Inertia::render('Leads/Index', [
            'leads' => $leads,
            'filters' => $filters,
            'pipeline' => $pipeline,
            'sources' => LeadSource::options(),
            'statuses' => LeadStatus::options(),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug', 'color']),
        ]);
    }

    public function store(StoreLeadRequest $request, RecordActivityAction $recordActivity): RedirectResponse
    {
        $lead = Lead::create([
            ...$request->safe()->except(['tag_ids', 'note']),
            'status' => $request->enum('status', LeadStatus::class) ?? LeadStatus::New,
            'assigned_to_user_id' => $request->user()->id,
        ]);

        $lead->tags()->sync($request->input('tag_ids', []));

        if ($request->filled('note')) {
            $lead->notes()->create([
                'body' => $request->string('note')->toString(),
                'created_by_user_id' => $request->user()->id,
            ]);
        }

        $lead->statusHistories()->create([
            'from_status' => null,
            'to_status' => $lead->status->value,
            'changed_by_user_id' => $request->user()->id,
            'changed_at' => now(),
        ]);

        $recordActivity->execute(
            $lead,
            'lead.created',
            'Lead created.',
            $request->user(),
            ['source' => $lead->source->value],
        );

        return to_route('leads.show', $lead)->with('success', 'Lead created successfully.');
    }

    public function show(Lead $lead): Response
    {
        $this->workspace()->assertLeadAccess(request()->user(), $lead);

        $lead->load([
            'assignedTo:id,name',
            'applicant:id,first_name,last_name',
            'tags:id,name,slug,color',
            'notes.author:id,name',
            'activities.causer:id,name',
            'statusHistories.user:id,name',
        ]);

        return Inertia::render('Leads/Show', [
            'lead' => [
                ...$this->leadSummary($lead),
                'date_of_birth' => $lead->date_of_birth?->toDateString(),
                'country_of_citizenship' => $lead->country_of_citizenship,
                'interested_visa_type' => $lead->interested_visa_type,
                'education_history' => collect($lead->education_history ?? [])->values(),
                'work_experience' => collect($lead->work_experience ?? [])->values(),
                'assigned_to' => $lead->assignedTo ? [
                    'id' => $lead->assignedTo->id,
                    'name' => $lead->assignedTo->name,
                ] : null,
                'applicant' => $lead->applicant ? [
                    'id' => $lead->applicant->id,
                    'name' => $lead->applicant->full_name,
                ] : null,
                'notes' => $lead->notes->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'author' => $note->author?->name ?? 'System',
                    'created_at' => $note->created_at?->toDateTimeString(),
                ]),
                'activities' => $lead->activities->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at?->toDateTimeString(),
                ]),
                'status_history' => $lead->statusHistories->map(fn ($history): array => [
                    'id' => $history->id,
                    'from_status' => $history->from_status?->label(),
                    'to_status' => $history->to_status->label(),
                    'changed_by' => $history->user?->name ?? 'System',
                    'changed_at' => $history->changed_at?->toDateTimeString(),
                ]),
            ],
            'statuses' => LeadStatus::options(),
            'sources' => LeadSource::options(),
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug', 'color']),
        ]);
    }

    public function update(
        UpdateLeadRequest $request,
        Lead $lead,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertLeadAccess($request->user(), $lead);

        $lead->update($request->safe()->except(['tag_ids']));

        if ($request->has('tag_ids')) {
            $lead->tags()->sync($request->input('tag_ids', []));
        }

        $recordActivity->execute(
            $lead,
            'lead.updated',
            'Lead information updated.',
            $request->user(),
        );

        return back()->with('success', 'Lead updated successfully.');
    }

    public function updateStatus(
        UpdateLeadStatusRequest $request,
        Lead $lead,
        UpdateLeadStatusAction $updateLeadStatus,
    ): RedirectResponse {
        $this->workspace()->assertLeadAccess($request->user(), $lead);

        $updateLeadStatus->execute(
            $lead,
            $request->enum('status', LeadStatus::class),
            $request->user(),
        );

        return back()->with('success', 'Lead status updated.');
    }

    private function leadSummary(Lead $lead): array
    {
        return [
            'id' => $lead->id,
            'first_name' => $lead->first_name,
            'last_name' => $lead->last_name,
            'name' => $lead->full_name,
            'email' => $lead->email,
            'phone' => $lead->phone,
            'date_of_birth' => $lead->date_of_birth?->toDateString(),
            'source' => [
                'value' => $lead->source->value,
                'label' => $lead->source->label(),
            ],
            'status' => [
                'value' => $lead->status->value,
                'label' => $lead->status->label(),
                'color' => $lead->status->color(),
            ],
            'interested_visa_type' => $lead->interested_visa_type,
            'education_history_count' => count($lead->education_history ?? []),
            'work_experience_count' => count($lead->work_experience ?? []),
            'converted_at' => $lead->converted_at?->toDateTimeString(),
            'created_at' => $lead->created_at?->toDateTimeString(),
            'applicant_id' => $lead->converted_to_applicant_id,
            'notes_count' => $lead->notes_count ?? null,
            'tags' => $lead->tags->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values(),
        ];
    }
}
