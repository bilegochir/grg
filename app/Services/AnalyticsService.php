<?php

namespace App\Services;

use App\Enums\VisaCasePriority;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseTask;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    // Case Analytics

    public function getCaseCompletionRate(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subMonths(12);
        $to = $to ?? now();

        $totalCases = $this->getCaseQuery()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $closedCases = $this->getCaseQuery()
            ->whereBetween('closed_at', [$from, $to])
            ->count();

        return [
            'total' => $totalCases,
            'closed' => $closedCases,
            'rate' => $totalCases > 0 ? round(($closedCases / $totalCases) * 100, 2) : 0,
            'period' => "{$from->toDateString()} to {$to->toDateString()}",
        ];
    }

    public function getAverageCaseDuration(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subMonths(12);
        $to = $to ?? now();

        $cases = $this->getCaseQuery()
            ->whereBetween('created_at', [$from, $to])
            ->whereNotNull('closed_at')
            ->selectRaw('DATEDIFF(day, created_at, closed_at) as duration_days')
            ->get();

        if ($cases->isEmpty()) {
            return ['average' => 0, 'min' => 0, 'max' => 0, 'count' => 0];
        }

        $durations = $cases->pluck('duration_days');

        return [
            'average' => round($durations->average(), 1),
            'min' => $durations->min(),
            'max' => $durations->max(),
            'count' => $durations->count(),
        ];
    }

    public function getCasesByStageWithMetrics(Carbon $from = null, Carbon $to = null): Collection
    {
        $from = $from ?? now()->subMonths(12);

        return $this->getCaseQuery()
            ->selectRaw('
                vs.id,
                vs.name,
                vs.color,
                COUNT(DISTINCT vc.id) as total_cases,
                COUNT(DISTINCT CASE WHEN vc.closed_at IS NULL THEN vc.id END) as open_cases,
                AVG(CASE WHEN vc.closed_at IS NOT NULL 
                    THEN DATEDIFF(day, vc.created_at, vc.closed_at) 
                    END) as avg_duration_days
            ')
            ->join('visa_workflow_stages as vs', 'vc.current_stage_id', '=', 'vs.id')
            ->where('vc.created_at', '>=', $from)
            ->groupBy('vs.id', 'vs.name', 'vs.color')
            ->orderByDesc('total_cases')
            ->get();
    }

    public function getCasesByPriority(): Collection
    {
        return collect(VisaCasePriority::cases())
            ->map(function ($priority) {
                return [
                    'priority' => $priority->name,
                    'label' => $priority->label(),
                    'count' => $this->getCaseQuery()
                        ->where('priority', $priority->value)
                        ->whereNull('closed_at')
                        ->count(),
                ];
            });
    }

    public function getCasesByAgent(): Collection
    {
        return $this->getUserQuery()
            ->withCount([
                'assignedCases as assigned_count' => fn ($q) => $this->getCaseQuery($q),
                'assignedCases as open_cases_count' => fn ($q) => $this->getCaseQuery($q)->whereNull('closed_at'),
                'assignedCases as closed_cases_count' => fn ($q) => $this->getCaseQuery($q)->whereNotNull('closed_at'),
            ])
            ->get(['id', 'name'])
            ->map(function ($user) {
                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'assigned' => $user->assigned_count,
                    'open' => $user->open_cases_count,
                    'closed' => $user->closed_cases_count,
                    'close_rate' => $user->assigned_count > 0 
                        ? round(($user->closed_cases_count / $user->assigned_count) * 100, 2)
                        : 0,
                ];
            });
    }

    public function getCasesByCountry(): Collection
    {
        return TargetCountry::query()
            ->select('id', 'name')
            ->withCount([
                'visaCases as total_cases' => fn ($q) => $this->getCaseQuery($q),
                'visaCases as open_cases' => fn ($q) => $this->getCaseQuery($q)->whereNull('closed_at'),
            ])
            ->orderByDesc('total_cases')
            ->get()
            ->map(fn ($country) => [
                'country_id' => $country->id,
                'name' => $country->name,
                'total' => $country->total_cases,
                'open' => $country->open_cases,
            ]);
    }

    // Finance Analytics

    public function getFinanceMetrics(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->startOfMonth();
        $to = $to ?? now()->endOfMonth();

        $invoices = $this->getInvoiceQuery()
            ->whereBetween('issued_at', [$from, $to])
            ->get();

        $payments = DB::table('invoice_payments')
            ->whereIn('invoice_id', $this->getInvoiceQuery()->select('id'))
            ->whereBetween('paid_at', [$from, $to])
            ->get();

        return [
            'total_invoiced' => (float) $invoices->sum('total'),
            'total_paid' => (float) $payments->sum('amount'),
            'outstanding' => (float) $this->getInvoiceQuery()->sum('balance_due'),
            'invoice_count' => $invoices->count(),
            'paid_percentage' => $invoices->sum('total') > 0 
                ? round(($payments->sum('amount') / $invoices->sum('total')) * 100, 2)
                : 0,
            'period' => "{$from->toDateString()} to {$to->toDateString()}",
        ];
    }

    public function getRevenueByMonth(Carbon $from = null, Carbon $to = null): Collection
    {
        $from = $from ?? now()->subMonths(12);
        $to = $to ?? now();

        return DB::table('invoices')
            ->selectRaw('DATE_TRUNC(\'month\', issued_at) as month, SUM(total) as total_revenue, COUNT(*) as invoice_count')
            ->whereIn('id', $this->getInvoiceQuery()->select('id'))
            ->whereBetween('issued_at', [$from, $to])
            ->groupBy(DB::raw('DATE_TRUNC(\'month\', issued_at)'))
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => [
                'month' => Carbon::parse($row->month)->format('M Y'),
                'revenue' => (float) $row->total_revenue,
                'invoice_count' => (int) $row->invoice_count,
            ]);
    }

    public function getInvoiceAging(): array
    {
        $now = now();
        $overdue = $this->getInvoiceQuery()
            ->where('balance_due', '>', 0)
            ->whereDate('due_at', '<', $now->toDateString())
            ->count();

        $due30 = $this->getInvoiceQuery()
            ->where('balance_due', '>', 0)
            ->whereDate('due_at', '>=', $now->toDateString())
            ->whereDate('due_at', '<=', $now->addDays(30)->toDateString())
            ->count();

        $due60 = $this->getInvoiceQuery()
            ->where('balance_due', '>', 0)
            ->whereDate('due_at', '>', $now->addDays(30)->toDateString())
            ->whereDate('due_at', '<=', $now->addDays(60)->toDateString())
            ->count();

        $future = $this->getInvoiceQuery()
            ->where('balance_due', '>', 0)
            ->whereDate('due_at', '>', $now->addDays(60)->toDateString())
            ->count();

        return [
            'overdue' => $overdue,
            'due_30' => $due30,
            'due_60' => $due60,
            'future' => $future,
            'total_outstanding' => $overdue + $due30 + $due60 + $future,
        ];
    }

    // Staff Analytics

    public function getStaffProductivity(Carbon $from = null, Carbon $to = null): Collection
    {
        $from = $from ?? now()->subMonths(1);
        $to = $to ?? now();

        return $this->getUserQuery()
            ->selectRaw('
                users.id,
                users.name,
                COUNT(DISTINCT CASE WHEN vc.closed_at BETWEEN ? AND ? THEN vc.id END) as closed_cases,
                COUNT(DISTINCT CASE WHEN vc.created_at BETWEEN ? AND ? THEN vc.id END) as created_cases,
                COUNT(DISTINCT CASE WHEN vct.status = ? AND vct.assigned_to_user_id = users.id THEN vct.id END) as completed_tasks,
                COUNT(DISTINCT CASE WHEN vct.assigned_to_user_id = users.id AND vct.status NOT IN (?, ?) THEN vct.id END) as pending_tasks
            ', [$from, $to, $from, $to, 'completed', 'completed', 'skipped'])
            ->leftJoin('visa_cases as vc', 'users.id', '=', 'vc.assigned_to_user_id')
            ->leftJoin('visa_case_tasks as vct', 'vc.id', '=', 'vct.visa_case_id')
            ->groupBy('users.id', 'users.name')
            ->get()
            ->map(fn ($user) => [
                'user_id' => $user->id,
                'name' => $user->name,
                'closed_cases' => (int) $user->closed_cases,
                'created_cases' => (int) $user->created_cases,
                'completed_tasks' => (int) $user->completed_tasks,
                'pending_tasks' => (int) $user->pending_tasks,
            ]);
    }

    // Lead Analytics

    public function getLeadConversionFunnel(): Collection
    {
        return collect($this->getLeadStatusEnum())
            ->map(fn ($status) => [
                'status' => $status->name,
                'label' => $status->label(),
                'count' => $this->getLeadQuery()
                    ->where('status', $status->value)
                    ->count(),
            ]);
    }

    public function getLeadConversionRate(Carbon $from = null, Carbon $to = null): array
    {
        $from = $from ?? now()->subMonths(12);
        $to = $to ?? now();

        $totalLeads = $this->getLeadQuery()
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $convertedLeads = DB::table('leads')
            ->whereIn('id', $this->getLeadQuery()->select('id'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'converted')
            ->count();

        $daysToConvert = DB::table('leads')
            ->selectRaw('AVG(DATEDIFF(day, created_at, updated_at)) as avg_days')
            ->whereIn('id', $this->getLeadQuery()->select('id'))
            ->whereBetween('created_at', [$from, $to])
            ->where('status', 'converted')
            ->first();

        return [
            'total_leads' => $totalLeads,
            'converted' => $convertedLeads,
            'conversion_rate' => $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 2) : 0,
            'avg_days_to_convert' => (int) ($daysToConvert?->avg_days ?? 0),
        ];
    }

    // Helper Methods

    protected function getCaseQuery($query = null)
    {
        if (!$query) {
            $query = VisaCase::query();
        }

        return app('workspace')->scopeCases($query, $this->user);
    }

    protected function getInvoiceQuery($query = null)
    {
        if (!$query) {
            $query = Invoice::query();
        }

        return app('workspace')->scopeInvoices($query, $this->user);
    }

    protected function getLeadQuery($query = null)
    {
        if (!$query) {
            $query = Lead::query();
        }

        return app('workspace')->scopeLeads($query, $this->user);
    }

    protected function getUserQuery($query = null)
    {
        if (!$query) {
            $query = User::query();
        }

        return app('workspace')->scopeUsers($query, $this->user);
    }

    protected function getLeadStatusEnum()
    {
        return \App\Enums\LeadStatus::cases();
    }
}
