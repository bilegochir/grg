<?php

namespace App\Support;

use App\Enums\ClientStatus;
use App\Enums\VisaCaseStatus;
use App\Models\Client;
use App\Models\User;
use App\Models\VisaCase;
use BackedEnum;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

class CrmActivityLogger
{
    /**
     * @param  array<string, mixed>  $originalAttributes
     */
    public function logClientUpdated(Client $client, array $originalAttributes, ?User $actor): void
    {
        $changes = $this->collectClientChanges($client, $originalAttributes);

        if ($changes === []) {
            return;
        }

        $client->crmActivities()->create([
            'agency_id' => $client->agency_id,
            'actor_id' => $actor?->id,
            'event_type' => 'client_updated',
            'title' => 'Client updated',
            'description' => $this->summarizeChanges($changes),
            'properties' => ['changes' => $changes],
        ]);
    }

    /**
     * @param  array<string, mixed>  $originalAttributes
     */
    public function logVisaCaseUpdated(VisaCase $visaCase, array $originalAttributes, ?User $actor): void
    {
        $changes = $this->collectVisaCaseChanges($visaCase, $originalAttributes);

        if ($changes === []) {
            return;
        }

        $visaCase->crmActivities()->create([
            'agency_id' => $visaCase->agency_id,
            'actor_id' => $actor?->id,
            'event_type' => 'visa_case_updated',
            'title' => 'Visa case updated',
            'description' => $this->summarizeChanges($changes),
            'properties' => ['changes' => $changes],
        ]);
    }

    /**
     * @param  array<string, mixed>  $originalAttributes
     * @return list<string>
     */
    private function collectClientChanges(Client $client, array $originalAttributes): array
    {
        $changes = [];

        foreach ([
            'full_name' => 'Full name',
            'email' => 'Email',
            'phone' => 'Phone',
            'date_of_birth' => 'Date of birth',
            'passport_number' => 'Passport number',
            'passport_expiry_date' => 'Passport expiry',
            'marital_status' => 'Marital status',
            'occupation' => 'Occupation',
            'current_address' => 'Address',
            'nationality' => 'Nationality',
            'destination_country' => 'Destination',
            'lead_source' => 'Lead source',
            'status' => 'Status',
        ] as $field => $label) {
            $before = $this->formatClientValue($field, $originalAttributes[$field] ?? null);
            $after = $this->formatClientValue($field, $client->getAttribute($field));

            if ($before !== $after) {
                $changes[] = "{$label} from {$before} to {$after}";
            }
        }

        foreach ([
            'family_members' => 'Family information updated',
            'education_history' => 'Education history updated',
            'work_experiences' => 'Work experience updated',
        ] as $field => $label) {
            $before = $this->normalizeJsonValue($originalAttributes[$field] ?? null);
            $after = $client->getAttribute($field) ?? [];

            if ($before !== $after) {
                $changes[] = $label;
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $originalAttributes
     * @return list<string>
     */
    private function collectVisaCaseChanges(VisaCase $visaCase, array $originalAttributes): array
    {
        $changes = [];

        foreach ([
            'client_id' => 'Client',
            'assigned_user_id' => 'Assignee',
            'visa_type' => 'Visa type',
            'destination_country' => 'Destination',
            'institution_name' => 'School',
            'status' => 'Status',
            'submitted_at' => 'Submitted date',
            'decision_at' => 'Decision date',
        ] as $field => $label) {
            $before = $this->formatVisaCaseValue($visaCase, $field, $originalAttributes[$field] ?? null);
            $after = $this->formatVisaCaseValue($visaCase, $field, $visaCase->getAttribute($field));

            if ($before !== $after) {
                $changes[] = "{$label} from {$before} to {$after}";
            }
        }

        return $changes;
    }

    private function summarizeChanges(array $changes): string
    {
        $visibleChanges = array_slice($changes, 0, 3);
        $summary = implode('; ', $visibleChanges);
        $remainingCount = count($changes) - count($visibleChanges);

        if ($remainingCount > 0) {
            $summary .= "; +{$remainingCount} more";
        }

        return $summary;
    }

    private function formatClientValue(string $field, mixed $value): string
    {
        return match ($field) {
            'status' => ClientStatus::tryFrom($this->normalizeEnumValue($value))?->label() ?? $this->fallbackValue($value),
            'date_of_birth', 'passport_expiry_date' => $this->formatDateValue($value),
            'marital_status' => filled($value) ? str((string) $value)->replace('_', ' ')->title()->toString() : 'Not set',
            default => $this->fallbackValue($value),
        };
    }

    private function formatVisaCaseValue(VisaCase $visaCase, string $field, mixed $value): string
    {
        return match ($field) {
            'status' => VisaCaseStatus::tryFrom($this->normalizeEnumValue($value))?->label() ?? $this->fallbackValue($value),
            'client_id' => $this->resolveClientName($visaCase, $value),
            'assigned_user_id' => $this->resolveUserName($visaCase, $value),
            'submitted_at', 'decision_at' => $this->formatDateValue($value),
            default => $this->fallbackValue($value),
        };
    }

    private function resolveClientName(VisaCase $visaCase, mixed $value): string
    {
        if (! filled($value)) {
            return 'Not set';
        }

        if ((int) $value === $visaCase->client_id && $visaCase->relationLoaded('client')) {
            return $visaCase->client?->full_name ?? 'Not set';
        }

        return (string) Client::query()->whereKey($value)->value('full_name') ?: 'Not set';
    }

    private function resolveUserName(VisaCase $visaCase, mixed $value): string
    {
        if (! filled($value)) {
            return 'Not set';
        }

        if ((int) $value === $visaCase->assigned_user_id && $visaCase->relationLoaded('assignee')) {
            return $visaCase->assignee?->name ?? 'Not set';
        }

        return (string) User::query()->whereKey($value)->value('name') ?: 'Not set';
    }

    private function formatDateValue(mixed $value): string
    {
        if (! filled($value)) {
            return 'Not set';
        }

        if ($value instanceof CarbonInterface) {
            return $value->toFormattedDateString();
        }

        return Carbon::parse((string) $value)->toFormattedDateString();
    }

    private function fallbackValue(mixed $value): string
    {
        if (blank($value)) {
            return 'Not set';
        }

        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return is_string($value) ? $value : (string) $value;
    }

    private function normalizeEnumValue(mixed $value): string
    {
        if ($value instanceof BackedEnum) {
            return (string) $value->value;
        }

        return (string) $value;
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeJsonValue(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (blank($value)) {
            return [];
        }

        return json_decode((string) $value, true) ?? [];
    }
}
