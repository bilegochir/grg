<?php

namespace App\Http\Controllers;

use App\Actions\DispatchApplicantCaseNotificationAction;
use App\Actions\RecordActivityAction;
use App\Enums\ApplicantNotificationEvent;
use App\Models\Invoice;
use App\Models\VisaCase;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    public function index(Request $request): Response
    {
        $invoices = $this->workspace()->scopeInvoices(Invoice::query(), $request->user())
            ->with(['visaCase.applicant:id,first_name,last_name', 'payments'])
            ->latest()
            ->get()
            ->map(fn (Invoice $invoice): array => [
                'id' => $invoice->id,
                'case_id' => $invoice->visa_case_id,
                'number' => $invoice->number,
                'status' => $invoice->status,
                'currency' => $invoice->currency,
                'total' => number_format((float) $invoice->total, 2, '.', ''),
                'balance_due' => number_format((float) $invoice->balance_due, 2, '.', ''),
                'due_at' => $invoice->due_at?->toDateString(),
                'case_reference' => $invoice->visaCase->reference_code,
                'applicant_name' => $invoice->visaCase->applicant->full_name,
            ]);

        return Inertia::render('Invoices/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function store(
        Request $request,
        VisaCase $case,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertCaseAccess($request->user(), $case);

        $data = $request->validate([
            'status' => ['required', 'string', 'max:50'],
            'currency' => ['required', 'string', 'size:3'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.label' => ['required', 'string', 'max:255'],
            'line_items.*.amount' => ['required', 'numeric', 'min:0'],
            'issued_at' => ['nullable', 'date'],
            'due_at' => ['nullable', 'date'],
            'client_message' => ['nullable', 'string', 'max:4000'],
            'notes' => ['nullable', 'string', 'max:4000'],
        ]);

        $normalizedLineItems = collect($data['line_items'])
            ->map(fn (array $item): array => [
                'label' => $item['label'],
                'amount' => round((float) $item['amount'], 2),
            ])
            ->values()
            ->all();

        $total = collect($normalizedLineItems)->sum('amount');

        $invoice = $case->invoices()->create([
            'applicant_id' => $case->applicant_id,
            'created_by_user_id' => $request->user()?->id,
            'number' => $this->nextInvoiceNumber(),
            'status' => $data['status'],
            'currency' => strtoupper($data['currency']),
            'line_items' => $normalizedLineItems,
            'subtotal' => $total,
            'total' => $total,
            'balance_due' => $total,
            'issued_at' => $data['issued_at'] ?? now()->toDateString(),
            'due_at' => $data['due_at'] ?? null,
            'client_message' => $data['client_message'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);

        $recordActivity->execute(
            $case,
            'visa_case.invoice_created',
            'Invoice created.',
            $request->user(),
            ['invoice_id' => $invoice->id],
        );

        return back()->with('success', 'Invoice created.');
    }

    public function recordPayment(
        Request $request,
        Invoice $invoice,
        RecordActivityAction $recordActivity,
    ): RedirectResponse {
        $this->workspace()->assertInvoiceAccess($request->user(), $invoice);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:100'],
            'reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'paid_at' => ['required', 'date'],
        ]);

        $payment = $invoice->payments()->create([
            'recorded_by_user_id' => $request->user()?->id,
            ...$data,
        ]);

        $paidAmount = (float) $invoice->payments()->sum('amount');
        $balance = max((float) $invoice->total - $paidAmount, 0);

        $invoice->forceFill([
            'balance_due' => $balance,
            'status' => $balance <= 0 ? 'paid' : ($paidAmount > 0 ? 'partially_paid' : $invoice->status),
        ])->save();

        $recordActivity->execute(
            $invoice->visaCase,
            'visa_case.invoice_payment_recorded',
            'Invoice payment recorded.',
            $request->user(),
            ['invoice_id' => $invoice->id, 'payment_id' => $payment->id],
        );

        return back()->with('success', 'Payment recorded.');
    }

    public function remind(
        Request $request,
        Invoice $invoice,
        DispatchApplicantCaseNotificationAction $dispatchNotification,
    ): RedirectResponse {
        $this->workspace()->assertInvoiceAccess($request->user(), $invoice);

        $case = $invoice->visaCase()->with(['applicant', 'country', 'visaType', 'currentStage'])->firstOrFail();

        $dispatchNotification->execute(
            $case,
            ApplicantNotificationEvent::PaymentReminders,
            data: [
                'message_body' => sprintf(
                    'Invoice %s has %s %s still due%s.',
                    $invoice->number,
                    $invoice->currency,
                    number_format((float) $invoice->balance_due, 2, '.', ''),
                    $invoice->due_at ? " by {$invoice->due_at->toDateString()}" : '',
                ),
            ],
            user: $request->user(),
            channels: ['email', 'sms'],
        );

        $invoice->forceFill(['reminder_sent_at' => now()])->save();

        return back()->with('success', 'Payment reminder sent.');
    }

    private function nextInvoiceNumber(): string
    {
        $count = Invoice::query()->count() + 1;

        return sprintf('INV-%s-%04d', now()->format('Y'), $count);
    }
}
