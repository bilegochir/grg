<?php

namespace App\Support;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseAppointment;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseDocumentVersion;
use App\Models\VisaCaseTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class WorkspaceAccess
{
    public function selectedBranchId(?User $user): ?int
    {
        if ($user === null) {
            return null;
        }

        if (! $this->hasGlobalBranchAccess($user)) {
            return $user->branch_id;
        }

        $selected = request()?->session()->get('workspace_branch_id');

        return is_numeric($selected) ? (int) $selected : null;
    }

    public function canSwitchBranches(?User $user): bool
    {
        return $this->hasGlobalBranchAccess($user);
    }

    public function hasGlobalBranchAccess(?User $user): bool
    {
        return $user !== null && ($user->hasRole('super-admin') || $user->branch_id === null);
    }

    public function scopeLeads(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('assignedTo', fn (Builder $assignedTo) => $assignedTo->where('branch_id', $branchId));
    }

    public function scopeApplicants(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($branchId): void {
            $builder
                ->whereHas('visaCases', fn (Builder $cases) => $cases->where('branch_id', $branchId))
                ->orWhereHas('lead.assignedTo', fn (Builder $assignedTo) => $assignedTo->where('branch_id', $branchId))
                ->orWhereDoesntHave('visaCases');
        });
    }

    public function scopeCases(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($branchId, $user): void {
            $builder
                ->where('branch_id', $branchId)
                ->orWhere(function (Builder $branchless) use ($user): void {
                    $branchless
                        ->whereNull('branch_id')
                        ->where('assigned_to_user_id', $user?->id);
                });
        });
    }

    public function scopeDocuments(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('visaCase', fn (Builder $caseQuery) => $caseQuery->where('branch_id', $branchId));
    }

    public function scopeTasks(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('visaCase', fn (Builder $caseQuery) => $caseQuery->where('branch_id', $branchId));
    }

    public function scopeAppointments(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('visaCase', fn (Builder $caseQuery) => $caseQuery->where('branch_id', $branchId));
    }

    public function scopeInvoices(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->whereHas('visaCase', fn (Builder $caseQuery) => $caseQuery->where('branch_id', $branchId));
    }

    public function scopeUsers(Builder|Relation $query, ?User $user): Builder|Relation
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId === null) {
            return $query;
        }

        return $query->where('branch_id', $branchId);
    }

    public function scopeActivities(Builder|Relation $query, ?User $user): Builder|Relation
    {
        if ($this->hasGlobalBranchAccess($user) || $user?->branch_id === null) {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($user): void {
            $builder
                ->whereHasMorph('subject', [Lead::class], function (Builder $leadQuery) use ($user): void {
                    $this->scopeLeads($leadQuery, $user);
                })
                ->orWhereHasMorph('subject', [Applicant::class], function (Builder $applicantQuery) use ($user): void {
                    $this->scopeApplicants($applicantQuery, $user);
                })
                ->orWhereHasMorph('subject', [VisaCase::class], function (Builder $caseQuery) use ($user): void {
                    $this->scopeCases($caseQuery, $user);
                });
        });
    }

    public function assertLeadAccess(User $user, Lead $lead): void
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId !== null) {
            abort_unless($lead->assignedTo()->where('branch_id', $branchId)->exists(), Response::HTTP_NOT_FOUND);
        }
    }

    public function assertApplicantAccess(User $user, Applicant $applicant): void
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId !== null) {
            abort_unless(
                $applicant->visaCases()->where('branch_id', $branchId)->exists()
                || $applicant->lead()->whereHas('assignedTo', fn (Builder $assignedTo) => $assignedTo->where('branch_id', $branchId))->exists()
                || ! $applicant->visaCases()->exists(),
                Response::HTTP_NOT_FOUND,
            );
        }
    }

    public function assertCaseAccess(User $user, VisaCase $case): void
    {
        $branchId = $this->selectedBranchId($user);

        if ($branchId !== null) {
            abort_unless(
                $case->branch_id === $branchId
                || ($case->branch_id === null && $case->assigned_to_user_id === $user->id),
                Response::HTTP_NOT_FOUND,
            );
        }
    }

    public function assertDocumentAccess(User $user, VisaCaseDocument $document): void
    {
        $document->loadMissing('visaCase');
        $this->assertCaseAccess($user, $document->visaCase);
    }

    public function assertTaskAccess(User $user, VisaCaseTask $task): void
    {
        $task->loadMissing('visaCase');
        $this->assertCaseAccess($user, $task->visaCase);
    }

    public function assertAppointmentAccess(User $user, VisaCaseAppointment $appointment): void
    {
        $appointment->loadMissing('visaCase');
        $this->assertCaseAccess($user, $appointment->visaCase);
    }

    public function assertInvoiceAccess(User $user, Invoice $invoice): void
    {
        $invoice->loadMissing('visaCase');
        $this->assertCaseAccess($user, $invoice->visaCase);
    }

    public function assertDocumentVersionAccess(User $user, VisaCaseDocumentVersion $version): void
    {
        $version->loadMissing('document.visaCase');
        $this->assertCaseAccess($user, $version->document->visaCase);
    }

    public function normalizeBranchId(User $user, ?int $branchId): ?int
    {
        $selectedBranchId = $this->selectedBranchId($user);

        if (! $this->canSwitchBranches($user)) {
            abort_if($branchId !== null && $branchId !== $selectedBranchId, Response::HTTP_FORBIDDEN);

            return $selectedBranchId;
        }

        if ($selectedBranchId !== null) {
            abort_if($branchId !== null && $branchId !== $selectedBranchId, Response::HTTP_FORBIDDEN);

            return $selectedBranchId;
        }

        if ($this->hasGlobalBranchAccess($user) || $user->branch_id === null) {
            return $branchId;
        }

        return $selectedBranchId;
    }

    public function assertAssignableUser(User $user, ?int $assignedToUserId, ?int $branchId = null): void
    {
        if ($assignedToUserId === null) {
            return;
        }

        $assignedUser = User::query()->findOrFail($assignedToUserId);

        $selectedBranchId = $this->selectedBranchId($user);

        if ($selectedBranchId !== null) {
            abort_unless($assignedUser->branch_id === $selectedBranchId, Response::HTTP_FORBIDDEN);
        }

        if ($branchId !== null && $assignedUser->branch_id !== null && $assignedUser->branch_id !== $branchId) {
            throw ValidationException::withMessages([
                'assigned_to_user_id' => 'The selected team member must belong to the same branch as the case.',
            ]);
        }
    }

    public function assertCanAssign(User $user): void
    {
        abort_unless($user->hasPermissionTo('cases.assign'), Response::HTTP_FORBIDDEN);
    }
}
