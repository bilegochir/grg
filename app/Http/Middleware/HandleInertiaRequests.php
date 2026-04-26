<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()
                    ? [
                        'id' => $request->user()->id,
                        'name' => $request->user()->name,
                        'email' => $request->user()->email,
                        'role' => $request->user()->role->value,
                        'role_label' => $request->user()->roleLabel(),
                        'email_verified_at' => $request->user()->email_verified_at?->toIso8601String(),
                        'created_at' => $request->user()->created_at?->toIso8601String(),
                        'updated_at' => $request->user()->updated_at?->toIso8601String(),
                    ]
                    : null,
                'agency' => $request->user()?->agency
                    ? [
                        'id' => $request->user()->agency->id,
                        'name' => $request->user()->agency->name,
                        'slug' => $request->user()->agency->slug,
                        'email' => $request->user()->agency->email,
                        'phone' => $request->user()->agency->phone,
                        'website' => $request->user()->agency->website,
                        'address' => $request->user()->agency->address,
                    ]
                    : null,
                'permissions' => $request->user()
                    ? [
                        'manage_company_settings' => $request->user()->canManageCompanySettings(),
                        'manage_workflow_settings' => $request->user()->canManageWorkflowSettings(),
                        'manage_team' => $request->user()->canManageTeam(),
                        'manage_clients' => $request->user()->canManageClients(),
                        'manage_visa_cases' => $request->user()->canManageVisaCases(),
                        'manage_tasks' => $request->user()->canManageTasks(),
                    ]
                    : null,
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
            'notifications' => fn (): ?array => $this->notificationData($request),
        ];
    }

    /**
     * @return array{items: list<array<string, mixed>>, unread_count: int}|null
     */
    private function notificationData(Request $request): ?array
    {
        $user = $request->user();

        if ($user === null) {
            return null;
        }

        $notifications = $user->notifications()
            ->latest()
            ->limit(8)
            ->get();

        return [
            'unread_count' => $user->unreadNotifications()->count(),
            'items' => $notifications
                ->map(fn (DatabaseNotification $notification): array => [
                    'id' => (string) $notification->id,
                    'kind' => (string) ($notification->data['kind'] ?? 'system'),
                    'title' => (string) ($notification->data['title'] ?? 'Notification'),
                    'message' => $notification->data['message'] ?? null,
                    'context' => $notification->data['context'] ?? null,
                    'action_label' => (string) ($notification->data['action_label'] ?? 'Open'),
                    'destination_url' => (string) ($notification->data['action_url'] ?? route('dashboard')),
                    'open_url' => route('notifications.open', $notification),
                    'created_at' => $notification->created_at?->toIso8601String(),
                    'read_at' => $notification->read_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }
}
