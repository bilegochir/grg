<?php

namespace App\Actions;

use App\Enums\VisaCaseDocumentStatus;
use App\Models\User;
use App\Models\VisaCaseDocument;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadVisaCaseDocumentVersionAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
    ) {
    }

    public function execute(
        VisaCaseDocument $document,
        UploadedFile $file,
        ?User $user = null,
        ?string $disk = null,
    ): VisaCaseDocument {
        $disk ??= config('filesystems.default');

        return DB::transaction(function () use ($document, $file, $user, $disk): VisaCaseDocument {
            $nextVersion = (int) $document->versions()->max('version_number') + 1;
            $extension = $file->getClientOriginalExtension();
            $filename = sprintf(
                '%s-v%d-%s%s',
                Str::slug($document->name),
                $nextVersion,
                Str::lower(Str::random(8)),
                $extension ? ".{$extension}" : '',
            );

            $path = $file->storeAs(
                "visa-cases/{$document->visa_case_id}/documents/{$document->id}",
                $filename,
                $disk,
            );

            $version = $document->versions()->create([
                'uploaded_by_user_id' => $user?->id,
                'disk' => $disk,
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize() ?: 0,
                'version_number' => $nextVersion,
            ]);

            $document->forceFill([
                'latest_version_id' => $version->id,
                'status' => VisaCaseDocumentStatus::Uploaded,
                'verified_at' => null,
                'rejected_at' => null,
                'rejection_reason' => null,
            ])->save();

            $this->recordActivity->execute(
                $document->visaCase,
                'visa_case.document_uploaded',
                sprintf('Uploaded a new version for %s.', $document->name),
                $user,
                [
                    'document_id' => $document->id,
                    'version_number' => $version->version_number,
                ],
            );

            return $document->fresh(['latestVersion', 'versions.uploader']);
        });
    }
}
