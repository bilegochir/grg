<?php

namespace App\Models;

use App\Enums\VisaCasePriority;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class VisaCase extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseFactory> */
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'visa_type_id',
        'target_country_id',
        'branch_id',
        'assigned_to_user_id',
        'current_stage_id',
        'priority',
        'reference_code',
        'expected_submission_at',
        'expected_decision_at',
        'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'priority' => VisaCasePriority::class,
            'expected_submission_at' => 'date',
            'expected_decision_at' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function visaType(): BelongsTo
    {
        return $this->belongsTo(VisaType::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(TargetCountry::class, 'target_country_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function currentStage(): BelongsTo
    {
        return $this->belongsTo(VisaWorkflowStage::class, 'current_stage_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(VisaCaseNote::class)->latest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function stageHistories(): HasMany
    {
        return $this->hasMany(VisaCaseStageHistory::class)->latest('changed_at');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(VisaCaseDocument::class)->orderBy('position');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(VisaCaseMessage::class)->latest();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(VisaCaseAppointment::class)->orderBy('starts_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(VisaCaseTask::class)->orderBy('position');
    }
}
