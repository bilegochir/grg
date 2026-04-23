<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function open(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->ensureNotificationBelongsToCurrentUser($request, $notification);

        $notification->markAsRead();

        $destination = $notification->data['action_url'] ?? route('dashboard');

        return redirect()->to((string) $destination);
    }

    public function read(Request $request, DatabaseNotification $notification): RedirectResponse
    {
        $this->ensureNotificationBelongsToCurrentUser($request, $notification);

        $notification->markAsRead();

        return redirect()->back();
    }

    public function readAll(Request $request): RedirectResponse
    {
        $request->user()?->unreadNotifications->markAsRead();

        return redirect()->back();
    }

    private function ensureNotificationBelongsToCurrentUser(Request $request, DatabaseNotification $notification): void
    {
        $user = $request->user();

        abort_unless(
            $user instanceof User
                && $notification->notifiable_type === $user->getMorphClass()
                && (int) $notification->notifiable_id === $user->id,
            404,
        );
    }
}
