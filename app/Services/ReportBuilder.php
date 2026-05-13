<?php

namespace App\Services;

use App\Models\CustomReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class ReportBuilder
{
    protected User $user;
    protected string $type;
    protected array $filters = [];
    protected array $columns = [];
    protected array $groupBy = [];
    protected array $sortBy = [];

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    public function setFilters(array $filters): self
    {
        $this->filters = $filters;
        return $this;
    }

    public function setColumns(array $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    public function setGroupBy(array $groupBy): self
    {
        $this->groupBy = $groupBy;
        return $this;
    }

    public function setSortBy(array $sortBy): self
    {
        $this->sortBy = $sortBy;
        return $this;
    }

    public function build(): Collection
    {
        $analytics = new AnalyticsService($this->user);

        return match ($this->type) {
            'cases' => $this->buildCasesReport($analytics),
            'finance' => $this->buildFinanceReport($analytics),
            'staff' => $this->buildStaffReport($analytics),
            'leads' => $this->buildLeadsReport($analytics),
            default => collect([]),
        };
    }

    public function save(string $name, string $description = null): CustomReport
    {
        return CustomReport::create([
            'user_id' => $this->user->id,
            'name' => $name,
            'description' => $description,
            'type' => $this->type,
            'filters' => $this->filters,
            'columns' => $this->columns,
            'group_by' => $this->groupBy,
            'sort_by' => $this->sortBy,
        ]);
    }

    protected function buildCasesReport(AnalyticsService $analytics): Collection
    {
        $from = $this->getDateFilter('from', now()->subMonths(12));
        $to = $this->getDateFilter('to', now());

        $data = $analytics->getCasesByStageWithMetrics($from, $to);

        if (in_array('priority', $this->groupBy)) {
            $data = $analytics->getCasesByPriority();
        }

        if (in_array('agent', $this->groupBy)) {
            $data = $analytics->getCasesByAgent();
        }

        if (in_array('country', $this->groupBy)) {
            $data = $analytics->getCasesByCountry();
        }

        return $this->applyColumnFilter($data);
    }

    protected function buildFinanceReport(AnalyticsService $analytics): Collection
    {
        $from = $this->getDateFilter('from', now()->startOfMonth());
        $to = $this->getDateFilter('to', now()->endOfMonth());

        if (in_array('monthly_revenue', $this->groupBy)) {
            $data = $analytics->getRevenueByMonth($from, $to);
        } else {
            $metrics = $analytics->getFinanceMetrics($from, $to);
            $data = collect([$metrics]);
        }

        return $this->applyColumnFilter($data);
    }

    protected function buildStaffReport(AnalyticsService $analytics): Collection
    {
        $from = $this->getDateFilter('from', now()->subMonths(1));
        $to = $this->getDateFilter('to', now());

        $data = $analytics->getStaffProductivity($from, $to);

        return $this->applyColumnFilter($data);
    }

    protected function buildLeadsReport(AnalyticsService $analytics): Collection
    {
        if (in_array('funnel', $this->groupBy)) {
            $data = $analytics->getLeadConversionFunnel();
        } else {
            $from = $this->getDateFilter('from', now()->subMonths(12));
            $to = $this->getDateFilter('to', now());
            $metrics = $analytics->getLeadConversionRate($from, $to);
            $data = collect([$metrics]);
        }

        return $this->applyColumnFilter($data);
    }

    protected function applyColumnFilter(Collection $data): Collection
    {
        if (empty($this->columns)) {
            return $data;
        }

        return $data->map(function ($row) {
            $filtered = [];
            $row = (array) $row;

            foreach ($this->columns as $column) {
                if (array_key_exists($column, $row)) {
                    $filtered[$column] = $row[$column];
                }
            }

            return $filtered;
        });
    }

    protected function getDateFilter(string $key, Carbon $default): Carbon
    {
        if (isset($this->filters['date_range'][$key])) {
            return Carbon::parse($this->filters['date_range'][$key]);
        }

        return $default;
    }

    public static function fromCustomReport(User $user, CustomReport $report): self
    {
        $builder = new self($user);

        return $builder
            ->setType($report->type)
            ->setFilters($report->filters ?? [])
            ->setColumns($report->columns ?? [])
            ->setGroupBy($report->group_by ?? [])
            ->setSortBy($report->sort_by ?? []);
    }
}
