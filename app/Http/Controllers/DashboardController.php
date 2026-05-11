<?php

namespace App\Http\Controllers;

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\VisaCaseDocumentStatus;
use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Lead;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Support\OperationsAlertService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request, OperationsAlertService $alerts): Response
    {
        $lastWeek = now()->subDays(7);

        $newLeadsCount = $this->workspace()->scopeLeads(Lead::query(), $request->user())->where('status', LeadStatus::New)->count();
        $newLeadsThisWeek = $this->workspace()->scopeLeads(Lead::query(), $request->user())->where('status', LeadStatus::New)->where('created_at', '>=', $lastWeek)->count();

        $activeApplicantsCount = $this->workspace()->scopeApplicants(Applicant::query(), $request->user())
            ->whereHas('visaCases', fn ($query) => $query->whereNull('closed_at'))
            ->count();
        $activeApplicantsThisWeek = $this->workspace()->scopeApplicants(Applicant::query(), $request->user())
            ->whereHas('visaCases', fn ($query) => $query
                ->whereNull('closed_at')
                ->where('created_at', '>=', $lastWeek))
            ->count();

        $pendingDocumentsCount = $this->workspace()->scopeDocuments(VisaCaseDocument::query(), $request->user())
            ->whereIn('status', [
                VisaCaseDocumentStatus::Pending->value,
                VisaCaseDocumentStatus::Uploaded->value,
            ])
            ->count();
        $overdueDocumentsCount = $this->workspace()->scopeDocuments(VisaCaseDocument::query(), $request->user())
            ->where('status', VisaCaseDocumentStatus::Pending->value)
            ->whereDate('updated_at', '<=', now()->subDays(5))
            ->count();

        $qualifiedLeadsCount = $this->workspace()->scopeLeads(Lead::query(), $request->user())->where('status', LeadStatus::Qualified)->count();
        $openCasesCount = $this->workspace()->scopeCases(VisaCase::query(), $request->user())->whereNull('closed_at')->count();
        $reviewCasesCount = $this->workspace()->scopeCases(VisaCase::query(), $request->user())
            ->whereHas('currentStage', fn ($query) => $query->where('slug', 'under-review'))
            ->count();

        $activities = $this->workspace()->scopeActivities(ActivityLog::query(), $request->user())
            ->with(['causer:id,name', 'subject'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function (ActivityLog $activity): array {
                $subjectName = match ($activity->subject_type) {
                    Lead::class => $activity->subject?->full_name,
                    Applicant::class => $activity->subject?->full_name,
                    VisaCase::class => $activity->subject?->reference_code,
                    default => null,
                };

                return [
                    'id' => $activity->id,
                    'description' => $activity->description,
                    'causer' => $activity->causer?->name ?? 'System',
                    'subject_name' => $subjectName,
                    'created_at' => $activity->created_at?->diffForHumans(),
                ];
            })
            ->values();

        // ── Lead Flow Chart (Last 14 days) ──────────────────────────────────
        $leadFlow = collect(range(0, 13))
            ->map(function ($daysAgo) use ($request) {
                $date = now()->subDays($daysAgo);
                $count = $this->workspace()->scopeLeads(Lead::query(), $request->user())
                    ->whereDate('created_at', $date->toDateString())
                    ->count();
                
                return [
                    'date' => $date->format('M d'),
                    'count' => $count,
                ];
            })
            ->reverse()
            ->values();

        // ── Source Distribution Chart ───────────────────────────────────────
        $sourceDistribution = collect(LeadSource::cases())
            ->map(fn (LeadSource $source) => [
                'label' => $source->label(),
                'count' => $this->workspace()->scopeLeads(Lead::query(), $request->user())
                    ->where('source', $source->value)
                    ->count(),
            ])
            ->filter(fn ($item) => $item['count'] > 0)
            ->values();

        return Inertia::render('Dashboard', [
            'stats' => [
                [
                    'label' => 'Leads to review',
                    'value' => $newLeadsCount,
                    'trend' => "{$newLeadsThisWeek} added in the last 7 days",
                    'tone' => 'border-l-brand-primary',
                    'href' => route('leads.index', ['status' => LeadStatus::New->value]),
                ],
                [
                    'label' => 'Applicants in motion',
                    'value' => $activeApplicantsCount,
                    'trend' => "{$activeApplicantsThisWeek} opened this week",
                    'tone' => 'border-l-brand-success',
                    'href' => route('applicants.index'),
                ],
                [
                    'label' => 'Docs waiting on clients',
                    'value' => $pendingDocumentsCount,
                    'trend' => $overdueDocumentsCount > 0
                        ? "{$overdueDocumentsCount} pending for more than 5 days"
                        : 'Nothing overdue right now',
                    'tone' => 'border-l-brand-warning',
                    'href' => route('cases.index'),
                ],
            ],
            'attentionItems' => [
                [
                    'label' => 'New leads',
                    'description' => 'Prospects who just entered your pipeline',
                    'count' => $newLeadsCount,
                    'badge' => $newLeadsCount === 1 ? '1 waiting' : "{$newLeadsCount} waiting",
                    'color' => 'blue',
                ],
                [
                    'label' => 'Document reviews',
                    'description' => 'Applicants waiting on agent verification',
                    'count' => $pendingDocumentsCount,
                    'badge' => $pendingDocumentsCount === 1 ? '1 pending' : "{$pendingDocumentsCount} pending",
                    'color' => 'amber',
                ],
                [
                    'label' => 'Ready to convert',
                    'description' => 'Qualified leads who can become active applicants',
                    'count' => $qualifiedLeadsCount,
                    'badge' => $qualifiedLeadsCount === 1 ? '1 ready' : "{$qualifiedLeadsCount} ready",
                    'color' => 'emerald',
                ],
            ],
            'activity' => $activities,
            'leadFlow' => $leadFlow,
            'sourceDistribution' => $sourceDistribution,
            'slaAlerts' => $alerts->alerts($request->user())->all(),
            'hasData' => $newLeadsCount > 0 || $activeApplicantsCount > 0 || $pendingDocumentsCount > 0 || $activities->isNotEmpty() || $openCasesCount > 0,
        ]);
    }
}
