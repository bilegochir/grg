<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'visa_case_id',
        'applicant_id',
        'created_by_user_id',
        'number',
        'status',
        'currency',
        'line_items',
        'subtotal',
        'total',
        'balance_due',
        'issued_at',
        'due_at',
        'client_message',
        'notes',
        'reminder_sent_at',
    ];

    protected function casts(): array
    {
        return [
            'line_items' => 'array',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'issued_at' => 'date',
            'due_at' => 'date',
            'reminder_sent_at' => 'datetime',
        ];
    }

    public function visaCase(): BelongsTo
    {
        return $this->belongsTo(VisaCase::class);
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(Applicant::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(InvoicePayment::class)->latest('paid_at');
    }
}
