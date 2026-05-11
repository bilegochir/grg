<?php

namespace App\Http\Middleware;

use App\Models\Applicant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicantPortalAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $applicantId = $request->session()->get('portal_applicant_id');

        if (! $applicantId || ! Applicant::query()->whereKey($applicantId)->exists()) {
            $request->session()->forget('portal_applicant_id');

            return redirect()->route('portal.login')->with('error', 'Please use your secure portal link to continue.');
        }

        return $next($request);
    }
}
