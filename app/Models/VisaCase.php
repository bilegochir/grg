<?php

namespace App\Models;

use App\Enums\VisaCaseStatus;
use Database\Factories\VisaCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class VisaCase extends Model
{
    /** @use HasFactory<VisaCaseFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'client_id',
        'assigned_user_id',
        'reference_code',
        'visa_type',
        'destination_country',
        'institution_name',
        'status',
        'submitted_at',
        'decision_at',
        'notes',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $visaCase): void {
            if (blank($visaCase->reference_code)) {
                $visaCase->reference_code = self::generateReferenceCode();
            }
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => VisaCaseStatus::class,
            'submitted_at' => 'datetime',
            'decision_at' => 'datetime',
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

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(VisaCaseRequirement::class)->orderBy('sort_order');
    }

    public function crmNotes(): MorphMany
    {
        return $this->morphMany(Note::class, 'notable');
    }

    public function crmActivities(): MorphMany
    {
        return $this->morphMany(CrmActivity::class, 'notable')->latest();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public static function generateReferenceCode(): string
    {
        do {
            $code = 'CASE-'.Str::upper(Str::random(8));
        } while (self::query()->where('reference_code', $code)->exists());

        return $code;
    }
}
