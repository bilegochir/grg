<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VisaCaseNote extends Model
{
    /** @use HasFactory<\Database\Factories\VisaCaseNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'visa_case_id',
        'created_by_user_id',
        'body',
        'is_client_visible',
    ];

    protected function casts(): array
    {
        return [
            'is_client_visible' => 'boolean',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
