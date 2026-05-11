<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseStageHistory extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseStageHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'visa_case_id',
        'from_stage_id',
        'to_stage_id',
        'changed_by_user_id',
        'changed_at',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function fromStage(): BelongsTo
    {
        return $this->belongsTo(VisaWorkflowStage::class, 'from_stage_id');
    }

    public function toStage(): BelongsTo
    {
        return $this->belongsTo(VisaWorkflowStage::class, 'to_stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
