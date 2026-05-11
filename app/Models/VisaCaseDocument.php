<?php

namespace App\Models;

use App\Enums\VisaCaseDocumentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaCaseDocument extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_case_id',
        'document_template_id',
        'name',
        'description',
        'category',
        'client_instructions',
        'agent_guidance',
        'sample_hint',
        'accepted_file_types',
        'max_files',
        'max_file_size_mb',
        'due_days',
        'is_repeatable',
        'position',
        'status',
        'is_required',
        'tracks_expiry',
        'expiry_date',
        'verified_at',
        'rejected_at',
        'rejection_reason',
        'latest_version_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => VisaCaseDocumentStatus::class,
            'accepted_file_types' => 'array',
            'max_files' => 'integer',
            'max_file_size_mb' => 'integer',
            'due_days' => 'integer',
            'is_repeatable' => 'boolean',
            'is_required' => 'boolean',
            'tracks_expiry' => 'boolean',
            'expiry_date' => 'date',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'document_template_id');
    }

    public function latestVersion(): BelongsTo
    {
        return $this->belongsTo(VisaCaseDocumentVersion::class, 'latest_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(VisaCaseDocumentVersion::class)->latest('version_number');
    }
}
