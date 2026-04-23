<?php

namespace App\Support;

use Illuminate\Support\Arr;

class ClientProfileDataNormalizer
{
    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    public function normalize(array $validated): array
    {
        $validated['family_members'] = $this->normalizeRows(
            Arr::get($validated, 'family_members', []),
            ['relationship', 'full_name', 'date_of_birth', 'nationality', 'occupation', 'is_accompanying'],
        );
        $validated['education_history'] = $this->normalizeRows(
            Arr::get($validated, 'education_history', []),
            ['institution', 'qualification', 'field_of_study', 'country', 'start_date', 'end_date', 'is_current'],
        );
        $validated['work_experiences'] = $this->normalizeRows(
            Arr::get($validated, 'work_experiences', []),
            ['employer', 'job_title', 'country', 'start_date', 'end_date', 'is_current', 'summary'],
        );

        return $validated;
    }

    /**
     * @param  list<string>  $allowedKeys
     * @return list<array<string, mixed>>
     */
    private function normalizeRows(mixed $rows, array $allowedKeys): array
    {
        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(fn ($row): bool => is_array($row))
            ->map(function (array $row) use ($allowedKeys): array {
                $normalizedRow = Arr::only($row, $allowedKeys);

                foreach ($normalizedRow as $key => $value) {
                    if ($value === '') {
                        $normalizedRow[$key] = null;
                    }
                }

                return $normalizedRow;
            })
            ->filter(function (array $row): bool {
                return collect($row)
                    ->reject(fn ($value, $key): bool => in_array($key, ['is_accompanying', 'is_current'], true))
                    ->filter(fn ($value): bool => $value !== null)
                    ->isNotEmpty();
            })
            ->values()
            ->all();
    }
}
