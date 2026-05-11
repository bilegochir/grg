<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Http\Requests\StoreLeadNoteRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadNoteController extends Controller
{
    public function store(
        StoreLeadNoteRequest $request,
        Lead $lead,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertLeadAccess($request->user(), $lead);

        $lead->notes()->create([
            'body' => $request->string('body')->toString(),
            'created_by_user_id' => $request->user()->id,
        ]);

        $recordActivity->execute(
            $lead,
            'lead.note_added',
            'Lead note added.',
            $request->user(),
        );

        return back()->with('success', 'Note added.');
    }
}
