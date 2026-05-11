<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseMessage extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseMessageFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_case_id',
        'sent_by_user_id',
        'sender_type',
        'direction',
        'channel',
        'notification_event',
        'subject',
        'body',
        'metadata',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
