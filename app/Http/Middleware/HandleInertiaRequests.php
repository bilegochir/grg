<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
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
                'user' => $request->user(),
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
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
            ],
        ];
    }
}
