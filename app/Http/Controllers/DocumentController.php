<?php

namespace App\Http\Controllers;

use App\Enums\VisaCaseDocumentStatus;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCaseDocument;
use App\Support\VisaCaseDocumentUrlGenerator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentController extends Controller
{
    public function index(Request $request, VisaCaseDocumentUrlGenerator $documentUrlGenerator): Response
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'bucket' => ['nullable', 'string', 'in:all,review,expiring,verified'],
            'status' => ['nullable', 'string', 'max:255'],
            'country' => ['nullable', 'string', 'max:255'],
            'assigned_to' => ['nullable', 'integer'],
        ]);

        $documentsQuery = $this->workspace()->scopeDocuments(VisaCaseDocument::query(), $request->user())
            ->with([
                'visaCase.applicant:id,first_name,last_name',
                'visaCase.country:id,name,slug',
                'visaCase.currentStage:id,name',
                'visaCase.assignedTo:id,name',
                'latestVersion',
            ])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $builder) use ($search): void {
                    $builder
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('visaCase', fn (Builder $case) => $case
                            ->where('reference_code', 'like', "%{$search}%")
                            ->orWhereHas('applicant', fn (Builder $applicant) => $applicant
                                ->where('first_name', 'like', "%{$search}%")
                                ->orWhere('last_name', 'like', "%{$search}%")));
                });
            })
            ->when(($filters['bucket'] ?? null) === 'review', fn (Builder $query) => $query->whereIn('status', [
                VisaCaseDocumentStatus::Pending->value,
                VisaCaseDocumentStatus::Uploaded->value,
                VisaCaseDocumentStatus::Rejected->value,
            ]))
            ->when(($filters['bucket'] ?? null) === 'expiring', fn (Builder $query) => $query
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '>=', now()->toDateString())
                ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString()))
            ->when(($filters['bucket'] ?? null) === 'verified', fn (Builder $query) => $query->where('status', VisaCaseDocumentStatus::Verified->value))
            ->when($filters['status'] ?? null, fn (Builder $query, string $status) => $query->where('status', $status))
            ->when($filters['country'] ?? null, function (Builder $query, string $country): void {
                $query->whereHas('visaCase.country', fn (Builder $builder) => $builder->where('slug', $country));
            })
            ->when($filters['assigned_to'] ?? null, fn (Builder $query, int $assignedTo) => $query->whereHas('visaCase', fn (Builder $case) => $case->where('assigned_to_user_id', $assignedTo)))
            ->latest('updated_at');

        $documents = (clone $documentsQuery)
            ->paginate(12)
            ->withQueryString()
            ->through(fn (VisaCaseDocument $document): array => $this->documentRow($document, $documentUrlGenerator));

        $summaryBase = $this->workspace()->scopeDocuments(VisaCaseDocument::query(), $request->user());

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'filters' => [
                'search' => $filters['search'] ?? '',
                'bucket' => $filters['bucket'] ?? 'all',
                'status' => $filters['status'] ?? '',
                'country' => $filters['country'] ?? '',
                'assigned_to' => $filters['assigned_to'] ?? '',
            ],
            'summary' => [
                'total' => (clone $summaryBase)->count(),
                'pending' => (clone $summaryBase)->where('status', VisaCaseDocumentStatus::Pending->value)->count(),
                'uploaded' => (clone $summaryBase)->where('status', VisaCaseDocumentStatus::Uploaded->value)->count(),
                'verified' => (clone $summaryBase)->where('status', VisaCaseDocumentStatus::Verified->value)->count(),
                'expired' => (clone $summaryBase)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '<', now()->toDateString())
                    ->count(),
                'expiring_soon' => (clone $summaryBase)
                    ->whereNotNull('expiry_date')
                    ->whereDate('expiry_date', '>=', now()->toDateString())
                    ->whereDate('expiry_date', '<=', now()->addDays(30)->toDateString())
                    ->count(),
            ],
            'statuses' => VisaCaseDocumentStatus::options(),
            'countries' => TargetCountry::query()->where('is_active', true)->orderBy('name')->get(['id', 'name', 'slug']),
            'agents' => $this->workspace()->scopeUsers(User::query(), $request->user())->orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function documentRow(VisaCaseDocument $document, VisaCaseDocumentUrlGenerator $documentUrlGenerator): array
    {
        $case = $document->visaCase;
        $expiryDate = $document->expiry_date?->toDateString();
        $isExpired = $document->expiry_date?->isPast() ?? false;
        $isExpiringSoon = $document->expiry_date?->isFuture() && $document->expiry_date->lte(now()->addDays(30));

        return [
            'id' => $document->id,
            'name' => $document->name,
            'category' => $document->category,
            'description' => $document->description,
            'client_instructions' => $document->client_instructions,
            'tracks_expiry' => $document->tracks_expiry,
            'expiry_date' => $expiryDate,
            'expiry_state' => $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring_soon' : null),
            'rejection_reason' => $document->rejection_reason,
            'status' => [
                'value' => $document->status->value,
                'label' => $document->status->label(),
                'color' => $document->status->color(),
            ],
            'latest_version' => $document->latestVersion ? [
                'id' => $document->latestVersion->id,
                'original_name' => $document->latestVersion->original_name,
                'version_number' => $document->latestVersion->version_number,
                'size' => $document->latestVersion->size,
                'download_url' => $documentUrlGenerator->temporaryDownloadUrl($document->latestVersion),
            ] : null,
            'case' => [
                'id' => $case->id,
                'reference_code' => $case->reference_code,
                'applicant_name' => $case->applicant->full_name,
                'country_name' => $case->country?->name,
                'current_stage' => $case->currentStage?->name,
                'assigned_to' => $case->assignedTo?->name,
            ],
        ];
    }
}
