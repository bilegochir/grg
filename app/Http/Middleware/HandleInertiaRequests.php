<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use App\Models\Applicant;
use App\Models\Branch;
use App\Models\BusinessSetting;
use App\Models\Lead;
use App\Models\VisaCase;
use App\Support\WorkspaceAccess;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $workspace = app(WorkspaceAccess::class);
        $business = BusinessSetting::current();
        $recentWindowStart = now()->subDay();
        $notificationsSince = $user && $user->notifications_seen_at?->greaterThan($recentWindowStart)
            ? $user->notifications_seen_at
            : $recentWindowStart;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'job_title' => $user->job_title,
                    'branch' => $user->branch?->only(['id', 'name']),
                    'roles' => $user->roles->pluck('slug')->all(),
                    'permissions' => $user->allPermissions()->pluck('name')->all(),
                ] : null,
            ],
            'notifications' => fn () => $user
                ? [
                    'recent_count' => ActivityLog::query()
                        ->when(true, fn ($query) => $workspace->scopeActivities($query, $user))
                        ->where('created_at', '>', $notificationsSince)
                        ->count(),
                    'items' => $workspace->scopeActivities(ActivityLog::query(), $user)
                        ->with(['causer:id,name', 'subject'])
                        ->latest()
                        ->limit(6)
                        ->get()
                        ->map(function (ActivityLog $activity): array {
                            $subjectName = match ($activity->subject_type) {
                                Lead::class => $activity->subject?->full_name,
                                Applicant::class => $activity->subject?->full_name,
                                VisaCase::class => $activity->subject?->reference_code,
                                default => null,
                            };

                            $href = match ($activity->subject_type) {
                                Lead::class => $activity->subject ? route('leads.show', $activity->subject) : null,
                                Applicant::class => $activity->subject ? route('applicants.show', $activity->subject) : null,
                                VisaCase::class => $activity->subject ? route('cases.show', $activity->subject) : null,
                                default => null,
                            };

                            return [
                                'id' => $activity->id,
                                'event' => $activity->event,
                                'description' => $activity->description,
                                'causer' => $activity->causer?->name ?? 'System',
                                'subject_name' => $subjectName,
                                'href' => $href,
                                'created_at' => $activity->created_at?->diffForHumans(),
                            ];
                        })
                        ->values()
                        ->all(),
                ]
                : [
                    'recent_count' => 0,
                    'items' => [],
                ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
            'app' => [
                'locale' => app()->getLocale(),
            ],
            'workspace' => [
                'name' => $business->business_name,
                'can_switch_branches' => $workspace->canSwitchBranches($user),
                'selected_branch_id' => $workspace->selectedBranchId($user),
                'branches' => fn () => $workspace->canSwitchBranches($user)
                    ? Branch::query()->where('is_active', true)->orderBy('name')->get(['id', 'name'])
                    : [],
            ],
        ];
    }
}
