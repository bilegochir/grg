<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaWorkflowStage extends Model
{
    /** @use HasFactory<\Database\Factories\VisaWorkflowStageFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_type_id',
        'name',
        'slug',
        'position',
        'color',
        'is_default',
        'is_closed',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_closed' => 'boolean',
        ];
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class, 'current_stage_id');
    }

    public function taskTemplates(): HasMany
    {
        return $this->hasMany(VisaTaskTemplate::class, 'visa_workflow_stage_id')->orderBy('position');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name;
    }
}
