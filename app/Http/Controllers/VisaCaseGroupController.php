<?php

namespace App\Http\Controllers;

use App\Models\VisaCase;
use App\Models\VisaCaseGroup;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class VisaCaseGroupController extends Controller
{
    /**
     * Create a new group and optionally add the given case as the primary member.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'           => ['required', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:2000'],
            'primary_case_id' => ['nullable', 'exists:visa_cases,id'],
        ]);

        $group = VisaCaseGroup::create([
            'name'               => $data['name'],
            'notes'              => $data['notes'] ?? null,
            'created_by_user_id' => $request->user()->id,
        ]);

        if (!empty($data['primary_case_id'])) {
            VisaCase::whereKey($data['primary_case_id'])
                ->update(['visa_case_group_id' => $group->id, 'is_group_primary' => true]);
        }

        return back()->with('success', "Group \"{$group->name}\" created.");
    }

    /**
     * Add an existing case to a group.
     */
    public function addMember(Request $request, VisaCaseGroup $group): RedirectResponse
    {
        $data = $request->validate([
            'case_id'          => ['required', 'exists:visa_cases,id'],
            'is_group_primary' => ['boolean'],
        ]);

        $case = VisaCase::findOrFail($data['case_id']);

        // Only one primary per group
        if ($request->boolean('is_group_primary')) {
            $group->cases()->update(['is_group_primary' => false]);
        }

        $case->update([
            'visa_case_group_id' => $group->id,
            'is_group_primary'   => $request->boolean('is_group_primary'),
        ]);

        return back()->with('success', 'Member added to group.');
    }

    /**
     * Remove a case from its group.
     */
    public function removeMember(VisaCase $case): RedirectResponse
    {
        $case->update(['visa_case_group_id' => null, 'is_group_primary' => false]);

        return back()->with('success', 'Case removed from group.');
    }

    /**
     * Dissolve the group entirely.
     */
    public function destroy(VisaCaseGroup $group): RedirectResponse
    {
        $group->cases()->update(['visa_case_group_id' => null, 'is_group_primary' => false]);
        $group->delete();

        return back()->with('success', 'Group dissolved.');
    }
}
