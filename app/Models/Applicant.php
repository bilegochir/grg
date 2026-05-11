<?php

namespace App\Models;

use App\Models\Concerns\HasTags;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

class Applicant extends Model
{
    /** @use HasFactory<\Database\Factories\ApplicantFactory> */
    use HasFactory;
    use HasTags;
    use Notifiable;

    protected $fillable = [
        'lead_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'date_of_birth',
        'nationality',
        'country_of_residence',
        'passport_number',
        'passport_country',
        'passport_issued_at',
        'passport_expires_at',
        'travel_history',
        'metadata',
        'notification_preferences',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'passport_issued_at' => 'date',
            'passport_expires_at' => 'date',
            'travel_history' => 'array',
            'metadata' => 'array',
            'notification_preferences' => 'array',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable')->latest();
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(ActivityLog::class, 'subject')->latest();
    }

    public function visaCases(): HasMany
    {
        return $this->hasMany(VisaCase::class)->latest();
    }

    public function portalInvites(): HasMany
    {
        return $this->hasMany(ApplicantPortalInvite::class)->latest();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(VisaCaseAppointment::class)->latest('starts_at');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function notificationPreferences(): array
    {
        $stored = $this->notification_preferences ?? [];

        return array_replace_recursive([
            'email_enabled' => true,
            'sms_enabled' => false,
            'locale' => 'en',
            'events' => [
                'case_status_changes' => true,
                'document_requests' => true,
                'payment_reminders' => true,
                'appointment_reminders' => true,
                'messages' => true,
            ],
        ], $stored);
    }

    public function wantsNotification(\App\Enums\ApplicantNotificationEvent $event, string $channel): bool
    {
        $preferences = $this->notificationPreferences();

        $channelEnabled = match ($channel) {
            'email' => (bool) ($preferences['email_enabled'] ?? false),
            'sms' => (bool) ($preferences['sms_enabled'] ?? false),
            default => false,
        };

        return $channelEnabled && (bool) data_get($preferences, "events.{$event->value}", false);
    }

    public function routeNotificationForMail(object $notification): ?string
    {
        return $this->email;
    }

    public function routeNotificationForSms(object $notification): ?string
    {
        return $this->phone;
    }
}
