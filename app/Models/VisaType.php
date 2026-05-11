<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VisaType extends Model
{
    /** @use HasFactory<\Database\Factories\VisaTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'target_country_id',
        'name',
        'code',
        'official_subclass',
        'slug',
        'is_active',
        'submission_sla_days',
        'decision_sla_days',
        'validity_months',
        'stay_duration_days',
        'entry_type',
        'service_scope',
        'priority_support',
        'dependants_allowed',
        'biometrics_required',
        'interview_required',
        'medical_required',
        'police_clearance_required',
        'financial_proof_required',
        'checklist_intro',
        'portal_guidance',
        'notes',
        'official_reference_url',
        'official_summary',
        'official_requirements',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'submission_sla_days' => 'integer',
            'decision_sla_days' => 'integer',
            'validity_months' => 'integer',
            'stay_duration_days' => 'integer',
            'priority_support' => 'boolean',
            'dependants_allowed' => 'boolean',
            'biometrics_required' => 'boolean',
            'interview_required' => 'boolean',
            'medical_required' => 'boolean',
            'police_clearance_required' => 'boolean',
            'financial_proof_required' => 'boolean',
            'official_requirements' => 'array',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(TargetCountry::class, 'target_country_id');
    }

    public function workflowStages(): HasMany
    {
        return $this->hasMany(VisaWorkflowStage::class)->orderBy('position');
    }

    public function documentTemplates(): HasMany
    {
        return $this->hasMany(DocumentTemplate::class)->orderBy('position');
    }

    public function taskTemplates(): HasMany
    {
        return $this->hasMany(VisaTaskTemplate::class)->orderBy('position');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(VisaCase::class)->latest();
    }
}
