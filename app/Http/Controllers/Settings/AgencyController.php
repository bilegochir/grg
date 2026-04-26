<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateAgencyRequest;
use App\Models\Agency;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AgencyController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_if($request->user()?->agency === null || ! $request->user()?->canManageCompanySettings(), 403);

        return Inertia::render('settings/Agency');
    }

    public function update(UpdateAgencyRequest $request): RedirectResponse
    {
        $agency = $request->user()?->agency;
        abort_if($agency === null || ! $request->user()?->canManageCompanySettings(), 403);

        $validated = $request->validated();

        $agency->fill($validated);

        if ($agency->isDirty('name')) {
            $agency->slug = Agency::generateUniqueSlug($validated['name']);
        }

        $agency->save();

        return to_route('settings.agency.edit')->with('success', 'Company information updated.');
    }
}
