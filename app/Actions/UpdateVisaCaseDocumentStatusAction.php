<?php

namespace App\Actions;

use App\Enums\VisaCaseDocumentStatus;
use App\Models\User;
use App\Models\VisaCaseDocument;

class UpdateVisaCaseDocumentStatusAction
{
    public function __construct(
        private readonly RecordActivityAction $recordActivity,
    ) {
    }

    public function execute(VisaCaseDocument $document, array $attributes, ?User $user = null): VisaCaseDocument
    {
        $status = VisaCaseDocumentStatus::from($attributes['status']);

        $document->forceFill([
            'status' => $status,
            'expiry_date' => $attributes['expiry_date'] ?? $document->expiry_date,
            'verified_at' => $status === VisaCaseDocumentStatus::Verified ? now() : null,
            'rejected_at' => $status === VisaCaseDocumentStatus::Rejected ? now() : null,
            'rejection_reason' => $status === VisaCaseDocumentStatus::Rejected
                ? ($attributes['rejection_reason'] ?? null)
                : null,
        ])->save();

        $this->recordActivity->execute(
            $document->visaCase,
            'visa_case.document_status_updated',
            sprintf('Marked %s as %s.', $document->name, $status->label()),
            $user,
        );

        return $document->fresh(['latestVersion', 'versions.uploader']);
    }
}
