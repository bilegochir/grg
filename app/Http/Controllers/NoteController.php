<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNoteRequest;
use App\Models\Client;
use App\Models\VisaCase;
use Illuminate\Http\RedirectResponse;

class NoteController extends Controller
{
    public function storeForClient(StoreNoteRequest $request, Client $client): RedirectResponse
    {
        $this->authorize('update', $client);

        $client->crmNotes()->create([
            'agency_id' => $client->agency_id,
            'author_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return to_route('clients.show', $client)->with('success', 'Note added successfully.');
    }

    public function storeForVisaCase(StoreNoteRequest $request, VisaCase $visaCase): RedirectResponse
    {
        $this->authorize('update', $visaCase);

        $visaCase->crmNotes()->create([
            'agency_id' => $visaCase->agency_id,
            'author_id' => $request->user()->id,
            'body' => $request->validated('body'),
        ]);

        return to_route('visa-cases.show', $visaCase)->with('success', 'Note added successfully.');
    }
}
