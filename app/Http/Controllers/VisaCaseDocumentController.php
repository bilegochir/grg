<?php

namespace App\Http\Controllers;

use App\Actions\UpdateVisaCaseDocumentStatusAction;
use App\Actions\UploadVisaCaseDocumentVersionAction;
use App\Http\Requests\UpdateVisaCaseDocumentStatusRequest;
use App\Http\Requests\UploadVisaCaseDocumentRequest;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseDocumentVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class VisaCaseDocumentController extends Controller
{
    public function upload(
        UploadVisaCaseDocumentRequest $request,
        VisaCase $case,
        VisaCaseDocument $document,
        UploadVisaCaseDocumentVersionAction $uploadDocumentVersion,
    ): RedirectResponse {
        abort_unless($document->visa_case_id === $case->id, Response::HTTP_NOT_FOUND);
        $this->workspace()->assertCaseAccess($request->user(), $case);
        $this->workspace()->assertDocumentAccess($request->user(), $document);

        $uploadDocumentVersion->execute(
            $document,
            $request->file('file'),
            $request->user(),
        );

        return back()->with('success', 'Document uploaded.');
    }

    public function updateStatus(
        UpdateVisaCaseDocumentStatusRequest $request,
        VisaCase $case,
        VisaCaseDocument $document,
        UpdateVisaCaseDocumentStatusAction $updateDocumentStatus,
    ): RedirectResponse {
        abort_unless($document->visa_case_id === $case->id, Response::HTTP_NOT_FOUND);
        $this->workspace()->assertCaseAccess($request->user(), $case);
        $this->workspace()->assertDocumentAccess($request->user(), $document);

        $updateDocumentStatus->execute($document, $request->validated(), $request->user());

        return back()->with('success', 'Document status updated.');
    }

    public function downloadVersion(Request $request, VisaCaseDocumentVersion $version): BinaryFileResponse|\Symfony\Component\HttpFoundation\StreamedResponse
    {
        abort_unless($request->hasValidSignature(), Response::HTTP_FORBIDDEN);

        if ($request->user()) {
            $this->workspace()->assertDocumentVersionAccess($request->user(), $version);
        }

        return Storage::disk($version->disk)->download($version->path, $version->original_name);
    }

    public function downloadZip(Request $request, VisaCase $case): BinaryFileResponse
    {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $case->load(['documents.latestVersion']);

        $zipPath = storage_path('app/private/tmp/'.Str::uuid().'.zip');
        $directory = dirname($zipPath);

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $zip = new \ZipArchive;
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);

        foreach ($case->documents as $document) {
            if ($document->latestVersion === null) {
                continue;
            }

            $contents = Storage::disk($document->latestVersion->disk)->get($document->latestVersion->path);
            $filename = Str::slug($document->name).'-v'.$document->latestVersion->version_number;
            $extension = pathinfo($document->latestVersion->original_name, PATHINFO_EXTENSION);
            $zip->addFromString($filename.($extension ? '.'.$extension : ''), $contents);
        }

        $zip->close();

        return response()->download($zipPath, $case->reference_code.'-documents.zip')->deleteFileAfterSend(true);
    }
}
