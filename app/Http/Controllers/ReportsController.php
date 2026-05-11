<?php

namespace App\Http\Controllers;

use App\Enums\LeadStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\TargetCountry;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseTask;
use App\Support\OperationsAlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportsController extends Controller
{
    public function __invoke(Request $request, OperationsAlertService $alerts): Response
    {
        $leadFunnel = collect(LeadStatus::cases())
            ->map(fn (LeadStatus $status): array => [
                'label' => $status->label(),
                'value' => $this->workspace()->scopeLeads(Lead::query(), $request->user())->where('status', $status->value)->count(),
            ])
            ->values();

        $leadSources = $this->workspace()->scopeLeads(Lead::query(), $request->user())
            ->select('source', DB::raw('count(*) as total'))
            ->groupBy('source')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => str($row->source?->value ?? (string) $row->source)->replace('_', ' ')->title()->toString(),
                'value' => (int) $row->total,
            ])
            ->values();

        $casesByStage = $this->workspace()->scopeCases(VisaCase::query(), $request->user())
            ->select('current_stage_id', DB::raw('count(*) as total'))
            ->with('currentStage:id,name,color')
            ->whereNull('closed_at')
            ->groupBy('current_stage_id')
            ->orderByDesc('total')
            ->get()
            ->map(fn ($row): array => [
                'label' => $row->currentStage?->name ?? 'Unassigned stage',
                'value' => (int) $row->total,
                'color' => $row->currentStage?->color ?? 'slate',
            ])
            ->values();

        $staffWorkload = $this->workspace()->scopeUsers(User::query(), $request->user())
            ->withCount([
                'assignedCases as open_cases_count' => fn ($query) => $this->workspace()->scopeCases($query, $request->user())->whereNull('closed_at'),
                'appointments as upcoming_appointments_count' => fn ($query) => $this->workspace()->scopeAppointments($query, $request->user())->where('starts_at', '>=', now()),
            ])
            ->orderByDesc('open_cases_count')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(function (User $user) use ($request): array {
                $openTasksCount = $this->workspace()->scopeTasks(VisaCaseTask::query(), $request->user())
                    ->where('assigned_to_user_id', $user->id)
                    ->whereNotIn('status', ['completed', 'skipped'])
                    ->count();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'open_cases_count' => $user->open_cases_count,
                    'open_tasks_count' => $openTasksCount,
                    'upcoming_appointments_count' => $user->upcoming_appointments_count,
                ];
            })
            ->values();

        $finance = [
            'outstanding_balance' => (float) $this->workspace()->scopeInvoices(Invoice::query(), $request->user())->sum('balance_due'),
            'overdue_invoices' => $this->workspace()->scopeInvoices(Invoice::query(), $request->user())
                ->where('balance_due', '>', 0)
                ->whereDate('due_at', '<', now()->toDateString())
                ->count(),
            'paid_this_month' => (float) DB::table('invoice_payments')
                ->whereIn('invoice_id', $this->workspace()->scopeInvoices(Invoice::query(), $request->user())->select('id'))
                ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->sum('amount'),
        ];

        $countryDemand = TargetCountry::query()
            ->withCount('visaTypes')
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (TargetCountry $country): array => [
                'label' => $country->name,
                'value' => $this->workspace()->scopeCases(VisaCase::query(), $request->user())->where('target_country_id', $country->id)->count(),
            ])
            ->filter(fn (array $row) => $row['value'] > 0)
            ->values();

        return Inertia::render('Reports/Index', [
            'leadFunnel' => $leadFunnel,
            'leadSources' => $leadSources,
            'casesByStage' => $casesByStage,
            'staffWorkload' => $staffWorkload,
            'finance' => $finance,
            'countryDemand' => $countryDemand,
            'alerts' => $alerts->alerts($request->user())->all(),
        ]);
    }
}
