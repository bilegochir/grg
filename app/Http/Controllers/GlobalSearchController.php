<?php

namespace App\Http\Controllers;

use App\Models\Applicant;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\VisaCase;
use App\Models\VisaCaseDocument;
use App\Models\VisaCaseTask;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $query = trim((string) $request->string('q'));
        $user = $request->user();

        if (mb_strlen($query) < 2) {
            return response()->json([
                'results' => [],
            ]);
        }

        $results = collect()
            ->concat($this->leadResults($query, $user))
            ->concat($this->applicantResults($query, $user))
            ->concat($this->caseResults($query, $user))
            ->concat($this->documentResults($query, $user))
            ->concat($this->taskResults($query, $user))
            ->concat($this->invoiceResults($query, $user))
            ->take(20)
            ->values();

        return response()->json([
            'results' => $results,
        ]);
    }

    private function leadResults(string $query, $user): array
    {
        return $this->workspace()->scopeLeads(Lead::query(), $user)
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('phone', 'like', "%{$query}%");
            })
            ->limit(4)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (Lead $lead): array => [
                'type' => 'Lead',
                'title' => $lead->full_name,
                'subtitle' => $lead->email,
                'href' => route('leads.show', $lead),
            ])
            ->all();
    }

    private function applicantResults(string $query, $user): array
    {
        return $this->workspace()->scopeApplicants(Applicant::query(), $user)
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('first_name', 'like', "%{$query}%")
                    ->orWhere('last_name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('passport_number', 'like', "%{$query}%");
            })
            ->limit(4)
            ->get(['id', 'first_name', 'last_name', 'email'])
            ->map(fn (Applicant $applicant): array => [
                'type' => 'Applicant',
                'title' => $applicant->full_name,
                'subtitle' => $applicant->email,
                'href' => route('applicants.show', $applicant),
            ])
            ->all();
    }

    private function caseResults(string $query, $user): array
    {
        return $this->workspace()->scopeCases(VisaCase::query(), $user)
            ->with(['applicant:id,first_name,last_name'])
            ->where(function (Builder $builder) use ($query): void {
                $builder
                    ->where('reference_code', 'like', "%{$query}%")
                    ->orWhereHas('applicant', fn (Builder $applicant) => $applicant
                        ->where('first_name', 'like', "%{$query}%")
                        ->orWhere('last_name', 'like', "%{$query}%"));
            })
            ->limit(4)
            ->get(['id', 'reference_code', 'applicant_id'])
            ->map(fn (VisaCase $visaCase): array => [
                'type' => 'Case',
                'title' => $visaCase->reference_code,
                'subtitle' => $visaCase->applicant?->full_name,
                'href' => route('cases.show', $visaCase),
            ])
            ->all();
    }

    private function documentResults(string $query, $user): array
    {
        return $this->workspace()->scopeDocuments(VisaCaseDocument::query(), $user)
            ->with(['visaCase.applicant:id,first_name,last_name'])
            ->where('name', 'like', "%{$query}%")
            ->limit(4)
            ->get(['id', 'visa_case_id', 'name'])
            ->map(fn (VisaCaseDocument $document): array => [
                'type' => 'Document',
                'title' => $document->name,
                'subtitle' => $document->visaCase?->applicant?->full_name,
                'href' => $document->visaCase ? route('cases.show', $document->visaCase) : route('documents.index'),
            ])
            ->all();
    }

    private function taskResults(string $query, $user): array
    {
        return $this->workspace()->scopeTasks(VisaCaseTask::query(), $user)
            ->with('visaCase.applicant:id,first_name,last_name')
            ->where('name', 'like', "%{$query}%")
            ->limit(4)
            ->get(['id', 'visa_case_id', 'name'])
            ->map(fn (VisaCaseTask $task): array => [
                'type' => 'Task',
                'title' => $task->name,
                'subtitle' => $task->visaCase?->applicant?->full_name,
                'href' => $task->visaCase ? route('cases.show', $task->visaCase) : route('tasks.index'),
            ])
            ->all();
    }

    private function invoiceResults(string $query, $user): array
    {
        return $this->workspace()->scopeInvoices(Invoice::query(), $user)
            ->with('applicant:id,first_name,last_name')
            ->where('number', 'like', "%{$query}%")
            ->limit(4)
            ->get(['id', 'number', 'applicant_id'])
            ->map(fn (Invoice $invoice): array => [
                'type' => 'Invoice',
                'title' => $invoice->number,
                'subtitle' => $invoice->applicant?->full_name,
                'href' => route('invoices.index'),
            ])
            ->all();
    }
}
