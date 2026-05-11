<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaTaskTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_type_id',
        'visa_workflow_stage_id',
        'name',
        'slug',
        'description',
        'position',
        'due_days',
        'is_required',
        'is_client_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_client_visible' => 'boolean',
        ];
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(VisaWorkflowStage::class, 'visa_workflow_stage_id');
    }

    public function caseTasks(): HasMany
    {
        return $this->hasMany(VisaCaseTask::class)->latest();
    }
}
