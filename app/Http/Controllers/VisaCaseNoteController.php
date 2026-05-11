<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Http\Requests\StoreVisaCaseNoteRequest;
use App\Models\VisaCase;
use Illuminate\Http\RedirectResponse;

class VisaCaseNoteController extends Controller
{
    public function store(
        StoreVisaCaseNoteRequest $request,
        VisaCase $case,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $note = $case->notes()->create([
            'body' => $request->string('body')->toString(),
            'created_by_user_id' => $request->user()->id,
            'is_client_visible' => $request->boolean('is_client_visible'),
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.note_added',
            $note->is_client_visible ? 'Client-visible case note added.' : 'Internal case note added.',
            $request->user(),
        );

        return back()->with('success', 'Case note added.');
    }
}
