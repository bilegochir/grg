<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\AgencyInvitationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class AgencyInvitation extends Model
{
    /** @use HasFactory<AgencyInvitationFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'agency_id',
        'invited_by_id',
        'name',
        'email',
        'role',
        'token',
        'expires_at',
        'accepted_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'role' => UserRole::class,
            'expires_at' => 'datetime',
            'accepted_at' => 'datetime',
        ];
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by_id');
    }

    public function scopePending($query): void
    {
        $query->whereNull('accepted_at');
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->accepted_at === null;
    }

    public function matchesToken(string $token): bool
    {
        if ($this->token === null) {
            return false;
        }

        return hash_equals($this->token, hash('sha256', $token));
    }

    public function markAccepted(): void
    {
        $this->forceFill([
            'accepted_at' => Carbon::now(),
            'token' => null,
        ])->save();
    }
}
