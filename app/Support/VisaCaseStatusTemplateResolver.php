<?php

namespace App\Support;

use App\Enums\VisaCaseStatus;
use App\Models\Agency;
use App\Models\VisaCaseStatusTemplate;

class VisaCaseStatusTemplateResolver
{
    public function __construct(private DefaultVisaCaseStatusTemplateSeeder $defaultVisaCaseStatusTemplateSeeder)
    {
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public function options(Agency $agency): array
    {
        return $this->templates($agency)
            ->map(fn (VisaCaseStatusTemplate $template): array => [
                'value' => $template->status_key,
                'label' => $template->label,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    public function labelsByStatus(Agency $agency): array
    {
        return $this->templates($agency)
            ->mapWithKeys(fn (VisaCaseStatusTemplate $template): array => [$template->status_key => $template->label])
            ->all();
    }

    public function labelFor(Agency $agency, string|VisaCaseStatus $status): string
    {
        $statusValue = $status instanceof VisaCaseStatus ? $status->value : $status;
        $labels = $this->labelsByStatus($agency);

        return $labels[$statusValue] ?? VisaCaseStatus::from($statusValue)->label();
    }

    /**
     * @return \Illuminate\Support\Collection<int, VisaCaseStatusTemplate>
     */
    public function templates(Agency $agency)
    {
        $this->defaultVisaCaseStatusTemplateSeeder->seed($agency);

        return VisaCaseStatusTemplate::query()
            ->where('agency_id', $agency->id)
            ->orderBy('sort_order')
            ->get();
    }
}
