<?php

namespace App\Support;

use App\Enums\LeadStatus;
use App\Enums\VisaCaseDocumentStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseTask;
use Illuminate\Support\Collection;

class OperationsAlertService
{
    public function alerts(?User $user = null): Collection
    {
        $today = now()->startOfDay();

        $workspace = app(WorkspaceAccess::class);

        $overdueTasks = $workspace->scopeTasks(VisaCaseTask::query(), $user)
            ->whereNotIn('status', ['completed', 'skipped'])
            ->whereDate('due_at', '<', $today)
            ->count();

        $staleCases = $workspace->scopeCases(VisaCase::query(), $user)
            ->whereNull('closed_at')
            ->whereHas('currentStage', fn ($query) => $query->where('is_closed', false))
            ->whereDate('updated_at', '<=', now()->subDays(5)->toDateString())
            ->count();

        $reviewQueue = $workspace->scopeDocuments(VisaCaseDocument::query(), $user)
            ->where('status', VisaCaseDocumentStatus::Uploaded->value)
            ->whereDate('updated_at', '<=', now()->subDays(2)->toDateString())
            ->count();

        $overdueInvoices = $workspace->scopeInvoices(Invoice::query(), $user)
            ->where('balance_due', '>', 0)
            ->whereDate('due_at', '<', $today)
            ->count();

        $agingQualifiedLeads = $workspace->scopeLeads(Lead::query(), $user)
            ->where('status', LeadStatus::Qualified->value)
            ->whereDate('updated_at', '<=', now()->subDays(3)->toDateString())
            ->count();

        return collect([
            [
                'key' => 'overdue_tasks',
                'label' => 'Overdue tasks',
                'description' => 'Task deadlines have passed and still need agent action.',
                'count' => $overdueTasks,
                'badge' => $overdueTasks === 1 ? '1 task overdue' : "{$overdueTasks} tasks overdue",
                'color' => 'rose',
                'href' => route('tasks.index', ['status' => 'pending']),
            ],
            [
                'key' => 'stale_cases',
                'label' => 'Stale cases',
                'description' => 'Cases have not moved for 5 days while still in an active workflow stage.',
                'count' => $staleCases,
                'badge' => $staleCases === 1 ? '1 case stalled' : "{$staleCases} cases stalled",
                'color' => 'amber',
                'href' => route('cases.index'),
            ],
            [
                'key' => 'review_queue',
                'label' => 'Documents waiting review',
                'description' => 'Uploaded files have been waiting more than 2 days for verification.',
                'count' => $reviewQueue,
                'badge' => $reviewQueue === 1 ? '1 doc waiting' : "{$reviewQueue} docs waiting",
                'color' => 'blue',
                'href' => route('documents.index', ['status' => 'uploaded']),
            ],
            [
                'key' => 'overdue_invoices',
                'label' => 'Overdue invoices',
                'description' => 'Applicants still owe money past the due date.',
                'count' => $overdueInvoices,
                'badge' => $overdueInvoices === 1 ? '1 invoice late' : "{$overdueInvoices} invoices late",
                'color' => 'rose',
                'href' => route('invoices.index'),
            ],
            [
                'key' => 'aging_qualified_leads',
                'label' => 'Qualified leads aging',
                'description' => 'Qualified leads have not been converted or updated in more than 3 days.',
                'count' => $agingQualifiedLeads,
                'badge' => $agingQualifiedLeads === 1 ? '1 lead waiting' : "{$agingQualifiedLeads} leads waiting",
                'color' => 'emerald',
                'href' => route('leads.index', ['status' => LeadStatus::Qualified->value]),
            ],
        ])->filter(fn (array $alert) => $alert['count'] > 0)->values();
    }
}
