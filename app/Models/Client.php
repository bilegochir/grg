<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /** @use HasFactory<\Database\Factories\ClientFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    public const MARITAL_STATUSES = [
        'single',
        'married',
        'divorced',
        'widowed',
        'separated',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'owner_id',
        'full_name',
        'email',
        'phone',
        'date_of_birth',
        'passport_number',
        'passport_expiry_date',
        'marital_status',
        'occupation',
        'current_address',
        'nationality',
        'destination_country',
        'lead_source',
        'status',
        'notes',
        'family_members',
        'education_history',
        'work_experiences',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ClientStatus::class,
            'date_of_birth' => 'date',
            'passport_expiry_date' => 'date',
            'family_members' => 'array',
            'education_history' => 'array',
            'work_experiences' => 'array',
        ];
    }

    public function scopeForAgency(Builder $query, Agency|int $agency): void
    {
        $query->where('agency_id', $agency instanceof Agency ? $agency->id : $agency);
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function visaCases(): HasMany
    {
        return $this->hasMany(VisaCase::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function crmNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
