<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DocumentTemplate extends Model
{
    /** @use HasFactory<\Database\Factories\DocumentTemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_type_id',
        'name',
        'slug',
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
        'is_required',
        'tracks_expiry',
    ];

    protected function casts(): array
    {
        return [
            'accepted_file_types' => 'array',
            'max_files' => 'integer',
            'max_file_size_mb' => 'integer',
            'due_days' => 'integer',
            'is_repeatable' => 'boolean',
            'is_required' => 'boolean',
            'tracks_expiry' => 'boolean',
        ];
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function caseDocuments(): HasMany
    {
        return $this->hasMany(VisaCaseDocument::class)->orderBy('position');
    }
}
