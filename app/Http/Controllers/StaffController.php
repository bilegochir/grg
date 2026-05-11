<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBranchRequest;
use App\Http\Requests\UpdateBranchRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Http\Requests\UpdateStaffMemberRequest;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Role;
use App\Models\TeamInvitation;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaWorkflowStage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class StaffController extends Controller
{
    public function index(Request $request): Response
    {
        $canManageOrganization = $request->user()?->hasPermissionTo('staff.manage');
        $scopeToSelectedBranch = $this->workspace()->hasGlobalBranchAccess($request->user())
            && $this->workspace()->selectedBranchId($request->user()) !== null;

        $staffQuery = $canManageOrganization && ! $scopeToSelectedBranch
            ? User::query()
            : $this->workspace()->scopeUsers(User::query(), $request->user());

        $staff = $staffQuery
            ->with(['branch:id,name', 'roles:id,name,slug', 'permissions:id,name,label'])
            ->withCount([
                'assignedCases as assigned_cases_count',
                'assignedCases as open_cases_count' => fn ($query) => $query->whereNull('closed_at'),
                'assignedLeads as total_leads_count',
                'assignedLeads as converted_leads_count' => fn ($query) => $query->whereNotNull('converted_at'),
            ])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($request, $canManageOrganization, $scopeToSelectedBranch): array {
                $closedCaseQuery = $canManageOrganization && ! $scopeToSelectedBranch
                    ? VisaCase::query()
                    : $this->workspace()->scopeCases(VisaCase::query(), $request->user());

                $closedCases = $closedCaseQuery
                    ->where('assigned_to_user_id', $user->id)
                    ->whereNotNull('closed_at')
                    ->get(['created_at', 'closed_at']);

                $averageProcessingDays = $closedCases->isNotEmpty()
                    ? $closedCases->avg(fn (VisaCase $visaCase) => $visaCase->created_at->diffInDays($visaCase->closed_at))
                    : null;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                    'is_active' => $user->is_active,
                    'branch' => $user->branch ? [
                        'id' => $user->branch->id,
                        'name' => $user->branch->name,
                    ] : null,
                    'roles' => $user->roles->map(fn (Role $role) => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'slug' => $role->slug,
                    ])->values(),
                    'direct_permissions' => $user->permissions->map(fn (Permission $permission) => [
                        'id' => $permission->id,
                        'label' => $permission->label,
                        'name' => $permission->name,
                    ])->values(),
                    'metrics' => [
                        'assigned_cases_count' => $user->assigned_cases_count,
                        'open_cases_count' => $user->open_cases_count,
                        'conversion_rate' => $user->total_leads_count > 0
                            ? round(($user->converted_leads_count / $user->total_leads_count) * 100)
                            : 0,
                        'avg_processing_days' => $averageProcessingDays !== null ? round((float) $averageProcessingDays, 1) : null,
                    ],
                ];
            });

        $stages = VisaWorkflowStage::query()
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        $workloadQuery = $canManageOrganization && ! $scopeToSelectedBranch
            ? User::query()
            : $this->workspace()->scopeUsers(User::query(), $request->user());

        $workload = $workloadQuery
            ->with(['branch:id,name'])
            ->orderBy('name')
            ->get()
            ->map(function (User $user) use ($request, $stages, $canManageOrganization, $scopeToSelectedBranch): array {
                $stageCounts = $stages->map(fn (VisaWorkflowStage $stage) => [
                    'stage' => $stage->name,
                    'slug' => $stage->slug,
                    'count' => (($canManageOrganization && ! $scopeToSelectedBranch) ? VisaCase::query() : $this->workspace()->scopeCases(VisaCase::query(), $request->user()))
                        ->where('assigned_to_user_id', $user->id)
                        ->where('current_stage_id', $stage->id)
                        ->count(),
                ])->values();

                return [
                    'user_id' => $user->id,
                    'name' => $user->name,
                    'branch_name' => $user->branch?->name,
                    'total_open_cases' => (($canManageOrganization && ! $scopeToSelectedBranch) ? VisaCase::query() : $this->workspace()->scopeCases(VisaCase::query(), $request->user()))
                        ->where('assigned_to_user_id', $user->id)
                        ->whereNull('closed_at')
                        ->count(),
                    'stages' => $stageCounts,
                ];
            });

        $roles = Role::query()
            ->with('permissions:id,label,name,group_name')
            ->orderBy('name')
            ->get()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'slug' => $role->slug,
                'description' => $role->description,
                'permissions' => $role->permissions
                    ->sortBy(['group_name', 'label'])
                    ->map(fn (Permission $permission) => [
                        'id' => $permission->id,
                        'name' => $permission->name,
                        'label' => $permission->label,
                        'group_name' => $permission->group_name,
                    ])->values(),
            ]);

        $permissions = Permission::query()
            ->orderBy('group_name')
            ->orderBy('label')
            ->get()
            ->groupBy('group_name')
            ->map(fn ($items, $group) => [
                'group' => $group,
                'items' => collect($items)->map(fn (Permission $permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'label' => $permission->label,
                ])->values(),
            ])
            ->values();

        $branches = ($canManageOrganization && ! $scopeToSelectedBranch
            ? Branch::query()
            : ($this->workspace()->selectedBranchId($request->user()) !== null
            ? Branch::query()->whereKey($this->workspace()->selectedBranchId($request->user()))
            : Branch::query()))
            ->withCount(['staff', 'cases'])
            ->orderBy('name')
            ->get()
            ->map(fn (Branch $branch): array => [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'is_active' => $branch->is_active,
                'staff_count' => $branch->staff_count,
                'cases_count' => $branch->cases_count,
            ]);

        $invitations = TeamInvitation::query()
            ->with(['role:id,name', 'branch:id,name', 'invitedBy:id,name'])
            ->when(
                $this->workspace()->selectedBranchId($request->user()) !== null && ($scopeToSelectedBranch || ! $canManageOrganization),
                fn ($query) => $query->where('branch_id', $this->workspace()->selectedBranchId($request->user()))
            )
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation): array => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'job_title' => $invitation->job_title,
                'role_name' => $invitation->role->name,
                'branch_name' => $invitation->branch?->name,
                'invited_by' => $invitation->invitedBy?->name,
                'accepted_at' => $invitation->accepted_at?->toDateTimeString(),
                'expires_at' => $invitation->expires_at->toDateTimeString(),
                'status' => $invitation->accepted_at
                    ? 'accepted'
                    : ($invitation->expires_at->isPast() ? 'expired' : 'pending'),
                'accept_url' => route('team-invitations.accept', $invitation->token),
            ]);

        return Inertia::render('Staff/Index', [
            'staff' => $staff,
            'workload' => $workload,
            'roles' => $roles,
            'permissions' => $permissions,
            'branches' => $branches,
            'invitations' => $invitations,
        ]);
    }

    public function updateUser(UpdateStaffMemberRequest $request, User $user): RedirectResponse
    {
        $canManageOrganization = $request->user()?->hasPermissionTo('staff.manage');

        if (! $canManageOrganization) {
            $this->workspace()->scopeUsers(User::query()->whereKey($user->id), $request->user())->firstOrFail();
        }

        $branchId = $canManageOrganization
            ? ($request->integer('branch_id') ?: null)
            : $this->workspace()->normalizeBranchId($request->user(), $request->integer('branch_id') ?: null);

        $user->update([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'job_title' => $request->string('job_title')->toString() ?: null,
            'branch_id' => $branchId,
            'is_active' => $request->boolean('is_active'),
        ]);

        $user->roles()->sync($request->input('role_ids', []));
        $user->permissions()->sync($request->input('permission_ids', []));

        return back()->with('success', 'Staff member updated.');
    }

    public function updateRole(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $role->update([
            'description' => $request->string('description')->toString() ?: null,
        ]);

        $role->permissions()->sync($request->input('permission_ids', []));

        return back()->with('success', 'Role permissions updated.');
    }

    public function storeBranch(StoreBranchRequest $request): RedirectResponse
    {
        $name = $request->string('name')->toString();

        Branch::query()->create([
            'name' => $name,
            'slug' => $this->uniqueBranchSlug($request->string('slug')->toString() ?: $name),
            'code' => $request->string('code')->toString() ?: null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Branch added.');
    }

    public function updateBranch(UpdateBranchRequest $request, Branch $branch): RedirectResponse
    {
        $name = $request->string('name')->toString();

        $branch->update([
            'name' => $name,
            'slug' => $this->uniqueBranchSlug($request->string('slug')->toString() ?: $name, $branch->id),
            'code' => $request->string('code')->toString() ?: null,
            'is_active' => $request->boolean('is_active'),
        ]);

        return back()->with('success', 'Branch updated.');
    }

    private function uniqueBranchSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $suffix = 1;

        while (
            Branch::query()
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }
}
