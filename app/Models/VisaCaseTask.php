<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseTask extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_case_id',
        'visa_task_template_id',
        'visa_workflow_stage_id',
        'assigned_to_user_id',
        'name',
        'description',
        'status',
        'position',
        'due_at',
        'completed_at',
        'is_required',
        'is_client_visible',
    ];

    protected function casts(): array
    {
        return [
            'due_at' => 'date',
            'completed_at' => 'datetime',
            'is_required' => 'boolean',
            'is_client_visible' => 'boolean',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(VisaTaskTemplate::class, 'visa_task_template_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(VisaWorkflowStage::class, 'visa_workflow_stage_id');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }
}
