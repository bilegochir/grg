<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\BusinessSetting;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApplicantController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'tag' => ['nullable', 'string', 'max:255'],
        ]);

        $applicants = $this->workspace()->scopeApplicants(Applicant::query(), $request->user())
            ->with(['lead:id,first_name,last_name', 'tags'])
            ->withCount('notes')
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('passport_number', 'like', "%{$search}%");
                });
            })
            ->when($filters['tag'] ?? null, function (Builder $query, string $tag): void {
                $query->whereHas('tags', fn (Builder $builder) => $builder->where('slug', $tag));
            })
            ->latest()
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Applicant $applicant): array => $this->applicantSummary($applicant));

        return Inertia::render('Applicants/Index', [
            'applicants' => $applicants,
            'filters' => $filters,
            'tags' => Tag::query()->orderBy('name')->get(['id', 'name', 'slug', 'color']),
        ]);
    }

    public function show(Applicant $applicant): Response
    {
        $this->workspace()->assertApplicantAccess(request()->user(), $applicant);

        $applicant->load([
            'lead:id,first_name,last_name',
            'tags:id,name,slug,color',
            'visaCases' => fn ($query) => $this->workspace()->scopeCases($query, request()->user()),
            'visaCases.country:id,name,slug',
            'visaCases.visaType:id,name,target_country_id',
            'visaCases.currentStage:id,name,color',
            'portalInvites',
            'notes.author:id,name',
            'activities.causer:id,name',
        ]);

        $activePortalInvite = $applicant->portalInvites
            ->first(fn ($invite) => $invite->expires_at?->isFuture());

        return Inertia::render('Applicants/Show', [
            'applicant' => [
                ...$this->applicantSummary($applicant),
                'lead' => $applicant->lead ? [
                    'id' => $applicant->lead->id,
                    'name' => $applicant->lead->full_name,
                ] : null,
                'date_of_birth' => $applicant->date_of_birth?->toDateString(),
                'nationality' => $applicant->nationality,
                'country_of_residence' => $applicant->country_of_residence,
                'passport_number' => $applicant->passport_number,
                'passport_country' => $applicant->passport_country,
                'passport_issued_at' => $applicant->passport_issued_at?->toDateString(),
                'passport_expires_at' => $applicant->passport_expires_at?->toDateString(),
                'travel_history' => $applicant->travel_history ?? [],
                'notification_preferences' => $applicant->notificationPreferences(),
                'available_locales' => [
                    ['value' => BusinessSetting::current()->default_locale, 'label' => strtoupper(BusinessSetting::current()->default_locale)],
                    ['value' => 'en', 'label' => 'EN'],
                    ['value' => 'mn', 'label' => 'MN'],
                    ['value' => 'ja', 'label' => 'JA'],
                    ['value' => 'ko', 'label' => 'KO'],
                ],
                'portal' => [
                    'login_url' => route('portal.login'),
                    'invite_url' => session('portal_invite_url')
                        ?? ($activePortalInvite ? route('portal.accept', $activePortalInvite->token) : null),
                    'invite_expires_at' => $activePortalInvite?->expires_at?->toDateTimeString(),
                ],
                'visa_cases' => $applicant->visaCases->map(fn ($visaCase): array => [
                    'id' => $visaCase->id,
                    'reference_code' => $visaCase->reference_code,
                    'country' => $visaCase->country->name,
                    'visa_type' => $visaCase->visaType->name,
                    'stage' => $visaCase->currentStage?->name,
                ])->values(),
                'notes' => $applicant->notes->map(fn ($note): array => [
                    'id' => $note->id,
                    'body' => $note->body,
                    'author' => $note->author?->name ?? 'System',
                    'created_at' => $note->created_at?->toDateTimeString(),
                ]),
                'activities' => $applicant->activities->map(fn ($activity): array => [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name ?? 'System',
                    'created_at' => $activity->created_at?->toDateTimeString(),
                ]),
            ],
        ]);
    }

    private function applicantSummary(Applicant $applicant): array
    {
        return [
            'id' => $applicant->id,
            'name' => $applicant->full_name,
            'email' => $applicant->email,
            'phone' => $applicant->phone,
            'passport_number' => $applicant->passport_number,
            'nationality' => $applicant->nationality,
            'notes_count' => $applicant->notes_count ?? null,
            'tags' => $applicant->tags->map(fn (Tag $tag): array => [
                'id' => $tag->id,
                'name' => $tag->name,
                'color' => $tag->color,
            ])->values(),
        ];
    }
}
