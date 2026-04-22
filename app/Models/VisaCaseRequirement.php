<?php

namespace App\Models;

use App\Enums\VisaRequirementStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class VisaCaseRequirement extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseRequirementFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'visa_case_id',
        'visa_requirement_item_id',
        'category',
        'label',
        'help_text',
        'is_required',
        'status',
        'due_at',
        'requested_at',
        'received_at',
        'reviewed_at',
        'review_notes',
        'is_completed',
        'completed_at',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'bool',
            'status' => VisaRequirementStatus::class,
            'due_at' => 'date',
            'requested_at' => 'datetime',
            'received_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'is_completed' => 'bool',
            'completed_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function templateItem(): BelongsTo
    {
        return $this->belongsTo(VisaRequirementItem::class, 'visa_requirement_item_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
