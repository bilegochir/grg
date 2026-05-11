<?php

namespace App\Http\Controllers;

use App\Actions\DispatchApplicantCaseNotificationAction;
use App\Actions\RecordActivityAction;
use App\Enums\ApplicantNotificationEvent;
use App\Models\User;
use App\Models\VisaCase;
use App\Models\VisaCaseAppointment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $appointments = $this->workspace()->scopeAppointments(VisaCaseAppointment::query(), $request->user())
            ->with(['visaCase.applicant:id,first_name,last_name', 'assignedTo:id,name'])
            ->orderBy('starts_at')
            ->get()
            ->map(fn (VisaCaseAppointment $appointment): array => [
                'id' => $appointment->id,
                'title' => $appointment->title,
                'appointment_type' => $appointment->appointment_type,
                'status' => $appointment->status,
                'starts_at' => $appointment->starts_at?->toDayDateTimeString(),
                'location' => $appointment->location,
                'agent' => $appointment->assignedTo?->name,
                'case_id' => $appointment->visa_case_id,
                'case_reference' => $appointment->visaCase->reference_code,
                'applicant_name' => $appointment->visaCase->applicant->full_name,
            ]);

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
        ]);
    }

    public function store(
        Request $request,
        VisaCase $case,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'appointment_type' => ['required', 'string', 'max:100'],
            'status' => ['required', 'string', 'max:50'],
            'location' => ['nullable', 'string', 'max:255'],
            'meeting_link' => ['nullable', 'url', 'max:255'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'assigned_to_user_id' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (! empty($data['assigned_to_user_id'])) {
            $this->workspace()->assertCanAssign($request->user());
            $this->workspace()->assertAssignableUser($request->user(), (int) $data['assigned_to_user_id'], $case->branch_id);
        }

        $appointment = $case->appointments()->create([
            ...$data,
            'applicant_id' => $case->applicant_id,
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.appointment_created',
            'Appointment scheduled.',
            $request->user(),
            ['appointment_id' => $appointment->id],
        );

        return back()->with('success', 'Appointment scheduled.');
    }

    public function remind(
        Request $request,
        VisaCase $case,
        VisaCaseAppointment $appointment,
        DispatchApplicantCaseNotificationAction $dispatchNotification,
    ): RedirectResponse {
        abort_unless($appointment->visa_case_id === $case->id, 404);
        $this->workspace()->assertCaseAccess($request->user(), $case);
        $this->workspace()->assertAppointmentAccess($request->user(), $appointment);

        $dispatchNotification->execute(
            $case->loadMissing(['applicant', 'country', 'visaType', 'currentStage']),
            ApplicantNotificationEvent::AppointmentReminders,
            data: [
                'message_body' => sprintf(
                    '%s on %s%s%s',
                    $appointment->title,
                    $appointment->starts_at->toDayDateTimeString(),
                    $appointment->location ? " at {$appointment->location}" : '',
                    $appointment->meeting_link ? ". Join here: {$appointment->meeting_link}" : '',
                ),
            ],
            user: $request->user(),
            channels: ['email', 'sms'],
        );

        $appointment->forceFill(['reminder_sent_at' => now()])->save();

        return back()->with('success', 'Appointment reminder sent.');
    }
}
