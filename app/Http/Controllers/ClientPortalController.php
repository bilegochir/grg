<?php

namespace App\Http\Controllers;

use App\Http\Requests\Portal\StorePortalAttachmentRequest;
use App\Http\Requests\Portal\UpdatePortalClientRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\VisaCase;
use App\Models\VisaCaseRequirement;
use App\Support\ClientProfileDataNormalizer;
use App\Support\VisaCaseStatusTemplateResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ClientPortalController extends Controller
{
    public function show(string $portalToken, VisaCaseStatusTemplateResolver $visaCaseStatusTemplateResolver): Response
    {
        $client = $this->portalClient($portalToken);
        $client->load([
            'agency:id,name,email,phone,website',
            'attachments.uploader:id,name',
            'visaCases.requirements.attachments.uploader:id,name',
        ]);

        $agency = $client->agency;
        abort_if($agency === null, 404);
        $statusStages = $visaCaseStatusTemplateResolver->options($agency);

        return Inertia::render('portal/Show', [
            'portal' => [
                'token' => $client->portal_token,
                'company' => [
                    'name' => $agency->name,
                    'email' => $agency->email,
                    'phone' => $agency->phone,
                    'website' => $agency->website,
                ],
                'client' => [
                    'full_name' => $client->full_name,
                    'email' => $client->email,
                    'phone' => $client->phone,
                    'passport_number' => $client->passport_number,
                    'passport_expiry_date' => $client->passport_expiry_date?->toDateString(),
                    'current_address' => $client->current_address,
                    'nationality' => $client->nationality,
                ],
                'clientAttachments' => $client->attachments
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(fn (Attachment $attachment): array => [
                        'id' => $attachment->id,
                        'original_name' => $attachment->original_name,
                        'mime_type' => $attachment->mime_type,
                        'size' => $attachment->human_size,
                        'uploaded_by' => $attachment->uploader?->name ?? 'Client portal',
                        'created_at' => $attachment->created_at?->toIso8601String(),
                        'download_url' => route('portal.attachments.download', [$client->portal_token, $attachment]),
                    ])
                    ->all(),
                'visaCases' => $client->visaCases
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function (VisaCase $visaCase) use ($client, $statusStages): array {
                        $requirements = $visaCase->requirements
                            ->sortBy('sort_order')
                            ->values();
                        $completedRequirementsCount = $requirements->filter(fn (VisaCaseRequirement $requirement): bool => $requirement->is_completed)->count();
                        $nextDueRequirement = $requirements
                            ->filter(fn (VisaCaseRequirement $requirement): bool => $requirement->due_at !== null && ! $requirement->is_completed)
                            ->sortBy('due_at')
                            ->first();
                        $totalRequirementsCount = $requirements->count();

                        return [
                            'id' => $visaCase->id,
                            'reference_code' => $visaCase->reference_code,
                            'visa_type' => $visaCase->visa_type,
                            'destination_country' => $visaCase->destination_country,
                            'status' => $visaCase->status->value,
                            'status_label' => collect($statusStages)->firstWhere('value', $visaCase->status->value)['label'] ?? $visaCase->status->label(),
                            'progress_percent' => $totalRequirementsCount === 0 ? 0 : (int) round(($completedRequirementsCount / $totalRequirementsCount) * 100),
                            'completed_requirements_count' => $completedRequirementsCount,
                            'total_requirements_count' => $totalRequirementsCount,
                            'submitted_at' => $visaCase->submitted_at?->toDateString(),
                            'decision_at' => $visaCase->decision_at?->toDateString(),
                            'next_due_at' => $nextDueRequirement?->due_at?->toDateString(),
                            'requirements' => $requirements->map(fn (VisaCaseRequirement $requirement): array => [
                                'id' => $requirement->id,
                                'category' => $requirement->category,
                                'label' => $requirement->label,
                                'help_text' => $requirement->help_text,
                                'is_required' => $requirement->is_required,
                                'status' => $requirement->status->value,
                                'status_label' => $requirement->status->label(),
                                'due_at' => $requirement->due_at?->toDateString(),
                                'is_completed' => $requirement->is_completed,
                                'attachments' => $requirement->attachments
                                    ->sortByDesc('created_at')
                                    ->values()
                                    ->map(fn (Attachment $attachment): array => [
                                        'id' => $attachment->id,
                                        'original_name' => $attachment->original_name,
                                        'mime_type' => $attachment->mime_type,
                                        'size' => $attachment->human_size,
                                        'uploaded_by' => $attachment->uploader?->name ?? 'Client portal',
                                        'created_at' => $attachment->created_at?->toIso8601String(),
                                        'download_url' => route('portal.attachments.download', [$client->portal_token, $attachment]),
                                    ])
                                    ->all(),
                            ])->all(),
                        ];
                    })
                    ->all(),
            ],
        ]);
    }

    public function update(
        UpdatePortalClientRequest $request,
        string $portalToken,
        ClientProfileDataNormalizer $clientProfileDataNormalizer,
    ): RedirectResponse {
        $client = $this->portalClient($portalToken);

        $client->update($clientProfileDataNormalizer->normalize($request->validated()));

        return to_route('portal.show', $client->portal_token)->with('success', 'Your details were updated successfully.');
    }

    public function storeClientAttachment(StorePortalAttachmentRequest $request, string $portalToken): RedirectResponse
    {
        $client = $this->portalClient($portalToken);

        $this->storeAttachment($request, $client, $client->agency_id);

        return to_route('portal.show', $client->portal_token)->with('success', 'Your document was uploaded successfully.');
    }

    public function storeRequirementAttachment(
        StorePortalAttachmentRequest $request,
        string $portalToken,
        VisaCase $visaCase,
        VisaCaseRequirement $requirement,
    ): RedirectResponse {
        $client = $this->portalClient($portalToken);

        abort_unless($visaCase->client_id === $client->id, 404);
        abort_unless($requirement->visa_case_id === $visaCase->id, 404);

        $this->storeAttachment($request, $requirement, $client->agency_id);

        return to_route('portal.show', $client->portal_token)->with('success', 'Your document was uploaded successfully.');
    }

    public function downloadAttachment(string $portalToken, Attachment $attachment): StreamedResponse
    {
        $client = $this->portalClient($portalToken);

        abort_unless($this->attachmentBelongsToClientPortal($client, $attachment), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function portalClient(string $portalToken): Client
    {
        return Client::findByPortalToken($portalToken) ?? abort(404);
    }

    private function storeAttachment(
        StorePortalAttachmentRequest $request,
        Client|VisaCaseRequirement $subject,
        int $agencyId,
    ): void {
        $file = $request->file('attachment');
        $path = $file->store('attachments/'.$agencyId.'/'.now()->format('Y/m'), 'local');

        $subject->attachments()->create([
            'agency_id' => $agencyId,
            'uploaded_by_id' => null,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }

    private function attachmentBelongsToClientPortal(Client $client, Attachment $attachment): bool
    {
        if ($attachment->attachable_type === $client->getMorphClass()) {
            return $attachment->attachable_id === $client->id;
        }

        if ($attachment->attachable_type === (new VisaCaseRequirement)->getMorphClass()) {
            return VisaCaseRequirement::query()
                ->whereKey($attachment->attachable_id)
                ->whereHas('visaCase', fn ($query) => $query->where('client_id', $client->id))
                ->exists();
        }

        return false;
    }
}
