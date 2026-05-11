<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NotificationController extends Controller
{
    public function markSeen(Request $request): Response
    {
        $request->user()?->forceFill([
            'notifications_seen_at' => now(),
        ])->save();

        return response()->noContent();
    }
}
