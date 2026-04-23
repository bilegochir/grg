<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
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

    public const PORTAL_SESSION_KEY = 'portal_client_id';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'owner_id',
        'portal_token',
        'portal_password',
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
     * @var list<string>
     */
    protected $hidden = [
        'portal_password',
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

    protected static function booted(): void
    {
        static::creating(function (self $client): void {
            if (blank($client->portal_token)) {
                $client->portal_token = (string) Str::uuid();
            }
        });
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

    public static function findByPortalToken(string $portalToken): ?self
    {
        return self::query()
            ->where('portal_token', $portalToken)
            ->first();
    }

    public function hasPortalPassword(): bool
    {
        return filled($this->portal_password);
    }

    public function portalPasswordIsValid(string $plainTextPassword): bool
    {
        return $this->hasPortalPassword() && Hash::check($plainTextPassword, (string) $this->portal_password);
    }

    public function updatePortalPassword(string $plainTextPassword): void
    {
        $this->forceFill([
            'portal_password' => Hash::make($plainTextPassword),
        ])->save();
    }
}
