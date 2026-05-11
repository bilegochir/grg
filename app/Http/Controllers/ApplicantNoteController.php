<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Http\Requests\StoreApplicantNoteRequest;
use App\Models\Applicant;
use Illuminate\Http\RedirectResponse;

class ApplicantNoteController extends Controller
{
    public function store(
        StoreApplicantNoteRequest $request,
        Applicant $applicant,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertApplicantAccess($request->user(), $applicant);

        $applicant->notes()->create([
            'body' => $request->string('body')->toString(),
            'created_by_user_id' => $request->user()->id,
        ]);

        $recordActivity->execute(
            $applicant,
            'applicant.note_added',
            'Applicant note added.',
            $request->user(),
        );

        return back()->with('success', 'Note added.');
    }
}
