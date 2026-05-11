<?php

namespace App\Http\Controllers;

use App\Actions\RecordActivityAction;
use App\Models\Applicant;
use App\Models\ApplicantPortalInvite;
use App\Models\BusinessSetting;
use App\Notifications\ApplicantPortalInviteNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApplicantPortalInviteController extends Controller
{
    public function store(
        Request $request,
        Applicant $applicant,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertApplicantAccess($request->user(), $applicant);

        ApplicantPortalInvite::query()->where('applicant_id', $applicant->id)->delete();

        $invite = ApplicantPortalInvite::query()->create([
            'applicant_id' => $applicant->id,
            'created_by_user_id' => $request->user()?->id,
            'token' => Str::random(48),
            'expires_at' => now()->addDays(14),
        ]);

        $portalUrl = route('portal.accept', $invite->token);

        if ($applicant->email) {
            $applicant->notify(new ApplicantPortalInviteNotification($portalUrl, BusinessSetting::current()));
        }

        $recordActivity->execute(
            $applicant,
            'applicant.portal_invite_created',
            'Applicant portal invite created.',
            $request->user(),
            ['portal_url' => $portalUrl],
        );

        return back()
            ->with('success', $applicant->email
                ? 'Portal invite created and emailed to the applicant.'
                : 'Portal invite created. Copy the access link from the applicant profile.')
            ->with('portal_invite_url', $portalUrl);
    }
}
