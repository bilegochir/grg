<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApplicantPortalInvite extends Model
{
    use HasFactory;

    protected $fillable = [
        'applicant_id',
        'created_by_user_id',
        'token',
        'expires_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function isValid(): bool
    {
        return $this->expires_at->isFuture();
    }

    public function issuePlainTextToken(): string
    {
        return Str::random(40);
    }
}
