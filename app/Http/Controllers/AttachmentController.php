<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreAttachmentRequest;
use App\Models\Attachment;
use App\Models\Client;
use App\Models\VisaCase;
use App\Models\VisaCaseRequirement;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    public function storeForClient(StoreAttachmentRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $this->storeAttachment($request, $client, $client->agency_id);

        return to_route('clients.show', $client)->with('success', 'Attachment uploaded successfully.');
    }

    public function storeForVisaCase(StoreAttachmentRequest $request, VisaCase $visaCase): RedirectResponse
    {
        $this->authorize('update', $visaCase);

        $this->storeAttachment($request, $visaCase, $visaCase->agency_id);

        return to_route('visa-cases.show', $visaCase)->with('success', 'Attachment uploaded successfully.');
    }

    public function storeForVisaCaseRequirement(
        StoreAttachmentRequest $request,
        VisaCase $visaCase,
        VisaCaseRequirement $requirement,
    ): RedirectResponse {
        $this->authorize('update', $visaCase);

        abort_unless($requirement->visa_case_id === $visaCase->id, 404);

        $this->storeAttachment($request, $requirement, $visaCase->agency_id);

        return to_route('visa-cases.show', $visaCase)->with('success', 'Requirement file uploaded successfully.');
    }

    public function show(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorize('view', $attachment);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->original_name);
    }

    private function storeAttachment(StoreAttachmentRequest $request, Client|VisaCase|VisaCaseRequirement $subject, int $agencyId): void
    {
        $file = $request->file('attachment');
        $path = $file->store('attachments/'.$agencyId.'/'.now()->format('Y/m'), 'local');

        $subject->attachments()->create([
            'agency_id' => $agencyId,
            'uploaded_by_id' => $request->user()->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
        ]);
    }
}
