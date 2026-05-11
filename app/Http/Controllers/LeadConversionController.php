<?php

namespace App\Http\Controllers;

use App\Actions\ConvertLeadToApplicantAction;
use App\Http\Requests\ConvertLeadRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadConversionController extends Controller
{
    public function store(
        ConvertLeadRequest $request,
        Lead $lead,
        ConvertLeadToApplicantAction $convertLeadToApplicant,
    ): RedirectResponse {
        $this->workspace()->assertLeadAccess($request->user(), $lead);

        $applicant = $convertLeadToApplicant->execute(
            $lead,
            $request->validated(),
            $request->user(),
        );

        return to_route('applicants.show', $applicant)->with('success', 'Lead converted to applicant.');
    }
}
