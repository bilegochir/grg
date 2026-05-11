<?php

namespace App\Support;

use App\Models\VisaCaseDocumentVersion;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Throwable;

class VisaCaseDocumentUrlGenerator
{
    public function temporaryDownloadUrl(VisaCaseDocumentVersion $version, int $minutes = 10): string
    {
        try {
            return Storage::disk($version->disk)->temporaryUrl(
                $version->path,
                now()->addMinutes($minutes),
            );
        } catch (Throwable) {
            return URL::temporarySignedRoute(
                'cases.documents.versions.download',
                now()->addMinutes($minutes),
                ['version' => $version->id],
            );
        }
    }
}
